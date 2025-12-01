<?php
// PHP - Arquivo: comunidades.php
session_start();
include "conexao.php"; 

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['usuario_id'];

// ------------------------------------------------------------------------------------------------
// 1. LÓGICA DE AÇÃO (ENTRAR/SAIR/EXCLUIR - AJAX)
// ------------------------------------------------------------------------------------------------
if (isset($_POST['action']) && isset($_POST['community_id'])) {
    $action = $_POST['action'];
    $communityId = intval($_POST['community_id']);
    $response = ['success' => false, 'error' => null];

    if ($action == 'join') {
        // --- Verificação do Limite de Membros (50) ---
        $maxMembers = 50;
        $sql_check_count = "SELECT COUNT(*) FROM membros_comunidade WHERE id_comunidade = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check_count);
        mysqli_stmt_bind_param($stmt_check, "i", $communityId);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $currentCount);
        mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ($currentCount >= $maxMembers) {
            $response['success'] = false;
            $response['error'] = 'A comunidade atingiu o limite máximo de ' . $maxMembers . ' membros.';
        } else {
            // Insere o usuário na tabela membros_comunidade
            $sql = "INSERT IGNORE INTO membros_comunidade (id_comunidade, id_usuario) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $communityId, $userId);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['status'] = 'joined';
                }
                mysqli_stmt_close($stmt);
            }
        }

    } elseif ($action == 'leave') {
        // 1. Verifica se o usuário que está saindo é o DONO (Criador)
        $sql_check_creator = "SELECT id_criador FROM comunidades WHERE id = ?";
        $stmt_creator = mysqli_prepare($conn, $sql_check_creator);
        mysqli_stmt_bind_param($stmt_creator, "i", $communityId);
        mysqli_stmt_execute($stmt_creator);
        mysqli_stmt_bind_result($stmt_creator, $currentCreatorId);
        mysqli_stmt_fetch($stmt_creator);
        mysqli_stmt_close($stmt_creator);

        // 2. Conta quantos membros existem (incluindo o próprio usuário)
        $sql_count = "SELECT COUNT(*) FROM membros_comunidade WHERE id_comunidade = ?";
        $stmt_count = mysqli_prepare($conn, $sql_count);
        mysqli_stmt_bind_param($stmt_count, "i", $communityId);
        mysqli_stmt_execute($stmt_count);
        mysqli_stmt_bind_result($stmt_count, $totalMembers);
        mysqli_stmt_fetch($stmt_count);
        mysqli_stmt_close($stmt_count);

        $proceedWithLeave = true;
        
        if ($currentCreatorId == $userId) {
            // O usuário é o dono.
            
            if ($totalMembers > 1) {
                // CASO 1: Há outros membros. Transfere a liderança aleatoriamente.
                
                // Busca um membro aleatório que NÃO seja o usuário atual (dono)
                $sql_heir = "SELECT id_usuario FROM membros_comunidade WHERE id_comunidade = ? AND id_usuario != ? ORDER BY RAND() LIMIT 1";
                $stmt_heir = mysqli_prepare($conn, $sql_heir);
                mysqli_stmt_bind_param($stmt_heir, "ii", $communityId, $userId);
                mysqli_stmt_execute($stmt_heir);
                mysqli_stmt_bind_result($stmt_heir, $newOwnerId);
                $foundHeir = mysqli_stmt_fetch($stmt_heir);
                mysqli_stmt_close($stmt_heir);

                if ($foundHeir && $newOwnerId) {
                    // Atualiza a tabela comunidades definindo o novo criador
                    $sql_update_owner = "UPDATE comunidades SET id_criador = ? WHERE id = ?";
                    $stmt_update = mysqli_prepare($conn, $sql_update_owner);
                    mysqli_stmt_bind_param($stmt_update, "ii", $newOwnerId, $communityId);
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);
                } else {
                    // Isso não deveria ocorrer se totalMembers > 1, mas bloqueamos por segurança.
                    $response['error'] = 'Erro interno: Não foi possível encontrar um novo dono para a comunidade.';
                    $proceedWithLeave = false;
                }

            } else {
                // CASO 2: É o único membro (totalMembers == 1). Impede a saída.
                $response['error'] = 'Você é o único membro e criador desta comunidade. Não é possível sair sem transferir a liderança. Você pode convidar outra pessoa ou usar o botão "Excluir" para encerrá-la.';
                $proceedWithLeave = false; // Bloqueia a remoção do membro no banco
            }

        } // Fim da verificação de dono
        
        // 3. Remove o usuário da tabela membros_comunidade (se $proceedWithLeave for true)
        if ($proceedWithLeave) {
            $sql = "DELETE FROM membros_comunidade WHERE id_comunidade = ? AND id_usuario = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $communityId, $userId);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['status'] = 'left';
                    
                    // Adiciona um status extra para o front-end se a liderança foi transferida
                    if ($currentCreatorId == $userId) {
                        $response['status'] = 'owner_transferred'; 
                    }
                    
                } else {
                     $response['error'] = 'Erro ao remover o membro do banco de dados.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $response['error'] = 'Erro de preparação da query de saída.';
            }
        }
    } elseif ($action == 'delete') { // ⭐ Lógica de Exclusão (Mantida)
        // 1. Verifica se o usuário logado é o criador
        $sql_check_creator = "SELECT id_criador FROM comunidades WHERE id = ?";
        $stmt_creator = mysqli_prepare($conn, $sql_check_creator);
        mysqli_stmt_bind_param($stmt_creator, "i", $communityId);
        mysqli_stmt_execute($stmt_creator);
        mysqli_stmt_bind_result($stmt_creator, $creatorId);
        mysqli_stmt_fetch($stmt_creator);
        mysqli_stmt_close($stmt_creator);

        if ($creatorId == $userId) {
            // 2. Exclui a comunidade
            $sql_delete = "DELETE FROM comunidades WHERE id = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            if ($stmt_delete) {
                mysqli_stmt_bind_param($stmt_delete, "i", $communityId);
                if (mysqli_stmt_execute($stmt_delete)) {
                    $response['success'] = true;
                    $response['status'] = 'deleted';
                } else {
                    $response['error'] = 'Erro ao excluir a comunidade no banco de dados.';
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                 $response['error'] = 'Erro de preparação da query de exclusão.';
            }
        } else {
            $response['error'] = 'Você não tem permissão para excluir esta comunidade.';
        }
    }
    
    // Recalcula a contagem de membros para a resposta AJAX
    if (($action == 'join' || $action == 'leave') && ($response['success'] || $response['error'])) { 
         $sql_count = "SELECT COUNT(*) FROM membros_comunidade WHERE id_comunidade = ?";
         $stmt_count = mysqli_prepare($conn, $sql_count);
         mysqli_stmt_bind_param($stmt_count, "i", $communityId);
         mysqli_stmt_execute($stmt_count);
         mysqli_stmt_bind_result($stmt_count, $newCount);
         mysqli_stmt_fetch($stmt_count);
         $response['new_count'] = $newCount;
         mysqli_stmt_close($stmt_count);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ------------------------------------------------------------------------------------------------
// 2. BUSCA, FILTRO E PAGINAÇÃO (NOVO)
// ------------------------------------------------------------------------------------------------

// Configuração da Paginação
$limit = 18; // Comunidades por página
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// -- Passo A: Contar o total de registros (para saber quantas páginas existem) --
$sql_count_query = "SELECT COUNT(*) FROM comunidades c";
$params_count = [];
$types_count = "";

if (!empty($searchTerm)) {
    // Adiciona filtro de busca se houver
    $sql_count_query .= " WHERE c.nome_comunidade LIKE ?"; 
    $searchTermWild = "%" . $searchTerm . "%";
    $params_count[] = $searchTermWild;
    $types_count .= "s";
}

$stmt_count_exec = mysqli_prepare($conn, $sql_count_query);
if (!empty($params_count)) {
    mysqli_stmt_bind_param($stmt_count_exec, $types_count, ...$params_count);
}
mysqli_stmt_execute($stmt_count_exec);
mysqli_stmt_bind_result($stmt_count_exec, $total_records);
mysqli_stmt_fetch($stmt_count_exec);
mysqli_stmt_close($stmt_count_exec);

$total_pages = ceil($total_records / $limit);

// -- Passo B: Buscar os dados com Limite e Offset --
$sql = "
    SELECT 
        c.id, 
        c.nome_comunidade, 
        c.descricao,
        c.id_criador, 
        (SELECT COUNT(*) FROM membros_comunidade m WHERE m.id_comunidade = c.id) AS total_membros,
        EXISTS(SELECT 1 FROM membros_comunidade m2 WHERE m2.id_comunidade = c.id AND m2.id_usuario = ?) AS is_member
    FROM 
        comunidades c
";

// Adiciona filtro WHERE se houver busca
if (!empty($searchTerm)) {
    $sql .= " WHERE c.nome_comunidade LIKE ? ";
}

$sql .= " ORDER BY c.nome_comunidade ASC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);

// Bind dos parâmetros dinamicamente
if (!empty($searchTerm)) {
    // Se tem busca: bind (UserId, SearchTerm, Limit, Offset)
    $searchTermWild = "%" . $searchTerm . "%";
    mysqli_stmt_bind_param($stmt, "isii", $userId, $searchTermWild, $limit, $offset);
} else {
    // Se não tem busca: bind (UserId, Limit, Offset)
    mysqli_stmt_bind_param($stmt, "iii", $userId, $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Erro ao buscar comunidades: " . mysqli_error($conn));
}

$comunidades = [];
while ($row = mysqli_fetch_assoc($result)) {
    $comunidades[] = $row;
}
mysqli_stmt_close($stmt);

// ------------------------------------------------------------------------------------------------
// PREFERÊNCIAS DO USUÁRIO (MANTIDO)
// ------------------------------------------------------------------------------------------------
$sql_prefs = "SELECT cor_fundo_pref, cor_texto_pref, tamanho_fonte_pref, fonte_preferida FROM perfil_usuario WHERE id = ?";
$stmt_prefs = mysqli_prepare($conn, $sql_prefs);
mysqli_stmt_bind_param($stmt_prefs, "i", $userId);
mysqli_stmt_execute($stmt_prefs);
$result_prefs = mysqli_stmt_get_result($stmt_prefs);
$prefs = mysqli_fetch_assoc($result_prefs) ?? [];
mysqli_stmt_close($stmt_prefs);

$prefs = [
    'cor_fundo_pref' => $prefs['cor_fundo_pref'] ?? '#f5f5f5',
    'cor_texto_pref' => $prefs['cor_texto_pref'] ?? '#2c3e50',
    'tamanho_fonte_pref' => $prefs['tamanho_fonte_pref'] ?? '16px',
    'fonte_preferida' => $prefs['fonte_preferida'] ?? 'sans-serif'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades | NeuroBlogs</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="homePage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Aplica as preferências de acessibilidade */
        body {
            background-color: <?php echo htmlspecialchars($prefs['cor_fundo_pref']); ?>;
            color: <?php echo htmlspecialchars($prefs['cor_texto_pref']); ?>;
            font-size: <?php echo htmlspecialchars($prefs['tamanho_fonte_pref']); ?>;
            font-family: <?php echo htmlspecialchars($prefs['fonte_preferida']); ?>;
        }
        .main-content-communities {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
            flex-wrap: wrap; /* Permite quebrar linha em telas menores */
        }
        .header-section h1 {
            color: #1e3c72;
            font-size: 2.5rem;
            flex-grow: 1;
            text-align: center;
        }
        .btn-back-link {
            background-color: transparent !important;
            color: #2879e4;
            padding: 10px 0;
            border: none;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        .btn-back-link:hover { opacity: 0.8; }
        
        .btn-create-community {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .btn-create-community:hover { background-color: #388E3C; }
        
        /* --- ESTILO DA BARRA DE PESQUISA (NOVO) --- */
        .search-container {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
        }
        .search-form {
            display: flex;
            width: 100%;
            max-width: 600px;
            gap: 10px;
        }
        .search-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn-search {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-search:hover { background-color: #0056b3; }
        .btn-clear {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .btn-clear:hover { background-color: #a71d2a; }

        /* --- GRID E CARDS (MANTIDO) --- */
        .community-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .community-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        /* Estilo para alinhar título e botão de exclusão */
        .community-header {
            display: flex;
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 10px; 
        }
        .community-title {
            color: #2879e4;
            margin-top: 0;
            font-size: 1.5rem;
            margin-bottom: 0;
        }
        .community-description {
            color: #666;
            margin: 10px 0 20px 0;
            flex-grow: 1;
        }
        .community-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .member-count {
            color: #1e3c72;
            font-weight: 500;
        }
        .btn-action-community {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-join { background-color: #28a745; color: white; }
        .btn-join:hover { background-color: #1e7e34; }
        .btn-leave { background-color: #dc3545; color: white; }
        .btn-leave:hover { background-color: #c82333; }
        .btn-view { background-color: #007bff; color: white; }
        .btn-view:hover { background-color: #0056b3; }
        
        .btn-delete {
            background-color: #f44336; 
            color: white;
            padding: 8px 15px; 
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-delete:hover { background-color: #d32f2f; }

        /* --- ESTILOS PAGINAÇÃO (NOVO) --- */
        .pagination-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background-color: white;
            color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .pagination-btn:hover {
            background-color: #f0f8ff;
            border-color: #007bff;
        }
        .pagination-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination-btn.disabled {
            color: #aaa;
            pointer-events: none;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <div class="main-content-communities">
        <div class="header-section">
            <a href="homePage.php" class="btn-back-link" title="Voltar para o Feed Principal">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            
            <h1>Descubra Comunidades</h1>
            
            <a href="criar_comunidade.php" class="btn-create-community">
                <i class="fas fa-plus-circle"></i> Criar Nova Comunidade
            </a>
        </div>

        <div class="search-container">
            <form action="comunidades.php" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Pesquisar comunidade pelo nome..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="comunidades.php" class="btn-clear" title="Limpar pesquisa"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($comunidades) > 0): ?>
            <div class="community-grid">
                <?php foreach ($comunidades as $comunidade): 
                    $btnClass = $comunidade['is_member'] ? 'btn-leave' : 'btn-join';
                    $btnText = $comunidade['is_member'] ? 'Sair' : 'Entrar';
                    $btnAction = $comunidade['is_member'] ? 'leave' : 'join';
                    $isCreator = ($comunidade['id_criador'] == $userId); // Verifica se o usuário é o criador
                ?>
                    <div class="community-card" data-id="<?= $comunidade['id'] ?>">
                        
                        <div class="community-header">
                            <h3 class="community-title"><?= htmlspecialchars($comunidade['nome_comunidade']) ?></h3>
                            <?php if ($isCreator): ?>
                                <button class="btn-action-community btn-delete" 
                                        data-community-id="<?= $comunidade['id'] ?>" 
                                        data-action="delete"
                                        title="Excluir Comunidade (Apenas para o criador)">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            <?php endif; ?>
                        </div>

                        <p class="community-description">
                            <?= empty($comunidade['descricao']) ? "Nenhuma descrição fornecida." : htmlspecialchars(substr($comunidade['descricao'], 0, 100)) . (strlen($comunidade['descricao']) > 100 ? '...' : '') ?>
                        </p>
                        
                        <div class="community-meta">
                            <span class="member-count">
                                <i class="fas fa-users"></i> 
                                <span class="member-count-value"><?= $comunidade['total_membros'] ?></span> / 50 membros
                            </span>
                            <div>
                                <a href="comunidade.php?id=<?= $comunidade['id'] ?>" class="btn-action-community btn-view">Ver</a>
                                <button class="btn-action-community <?= $btnClass ?>" 
                                        data-community-id="<?= $comunidade['id'] ?>" 
                                        data-action="<?= $btnAction ?>">
                                    <?= $btnText ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <?php 
                    // Mantém o termo de busca nos links da paginação
                    $searchParam = !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '';
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="?page=<?= ($page - 1) . $searchParam ?>" class="pagination-btn">&laquo; Anterior</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">&laquo; Anterior</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i . $searchParam ?>" class="pagination-btn <?= ($i == $page) ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= ($page + 1) . $searchParam ?>" class="pagination-btn">Próximo &raquo;</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">Próximo &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-communities">
                <?php if (!empty($searchTerm)): ?>
                    <p>Nenhuma comunidade encontrada para "<strong><?= htmlspecialchars($searchTerm) ?></strong>".</p>
                    <p><a href="comunidades.php">Ver todas as comunidades</a></p>
                <?php else: ?>
                    <p>Nenhuma comunidade foi encontrada no momento.</p>
                    <p>Que tal ser o primeiro a <a href="criar_comunidade.php">criar uma</a>?</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-action-community').forEach(button => {
                // Filtra apenas os botões de Entrar/Sair/Excluir
                if (!button.classList.contains('btn-view')) {
                    button.addEventListener('click', function() {
                        const communityId = this.getAttribute('data-community-id');
                        let action = this.getAttribute('data-action');
                        const buttonElement = this;
                        const card = buttonElement.closest('.community-card');
                        const countSpan = card.querySelector('.member-count-value');

                        // Confirmação para a ação de Excluir
                        if (action === 'delete') {
                            if (!confirm('Tem certeza que deseja EXCLUIR esta comunidade? Esta ação é irreversível e removerá todos os posts, comentários e membros.')) {
                                return; 
                            }
                        }

                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('community_id', communityId);

                        fetch('comunidades.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            
                            // Tratamento para a exclusão bem-sucedida
                            if (data.success && data.status === 'deleted') {
                                card.remove(); 
                                alert('Comunidade excluída com sucesso!');
                                return; 
                            }
                            
                            if (data.new_count !== undefined) {
                                countSpan.textContent = data.new_count;
                            }
                            
                            if (data.success) {
                                // Alterna a ação e o estilo do botão
                                if (data.status === 'joined') {
                                    buttonElement.textContent = 'Sair';
                                    buttonElement.classList.remove('btn-join');
                                    buttonElement.classList.add('btn-leave');
                                    buttonElement.setAttribute('data-action', 'leave');
                                } else if (data.status === 'left' || data.status === 'owner_transferred') { // Adiciona a checagem
                                    buttonElement.textContent = 'Entrar';
                                    buttonElement.classList.remove('btn-leave');
                                    buttonElement.classList.add('btn-join');
                                    buttonElement.setAttribute('data-action', 'join');
                                }
                            } else {
                                if (data.error) {
                                    alert(data.error); // Vai exibir a mensagem "Você é o único membro e criador desta comunidade..."
                                } else {
                                    alert('Erro ao processar a ação. Tente novamente.');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Erro de rede/AJAX:', error);
                            alert('Erro de conexão ao processar a ação.');
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>