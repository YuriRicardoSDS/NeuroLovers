<?php
// PHP - Arquivo: comunidade.php
session_start();
include "conexao.php"; 

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['usuario_id'];
$comunidadeId = $_GET['id'] ?? 0;

if ($comunidadeId == 0) {
    // Redireciona se o ID for inválido ou não estiver presente na URL
    header("Location: comunidades.php");
    exit;
}

// ====================================================================
// 1. BUSCAR DETALHES DA COMUNIDADE
// ====================================================================
$sql_comunidade = "SELECT nome_comunidade, descricao, id_criador FROM comunidades WHERE id = ?";
$stmt_comunidade = mysqli_prepare($conn, $sql_comunidade);
mysqli_stmt_bind_param($stmt_comunidade, "i", $comunidadeId);
mysqli_stmt_execute($stmt_comunidade);
$result_comunidade = mysqli_stmt_get_result($stmt_comunidade);
$comunidade = mysqli_fetch_assoc($result_comunidade);
mysqli_stmt_close($stmt_comunidade);

if (!$comunidade) {
    die("Comunidade não encontrada.");
}

$nomeComunidade = $comunidade['nome_comunidade'];
$isCreator = ($comunidade['id_criador'] == $userId);


// ====================================================================
// 2. VERIFICAR STATUS DE MEMBRO
// ====================================================================
$isMember = false;
$isAdmin = false;
$sql_membro = "SELECT is_admin FROM membros_comunidade WHERE id_comunidade = ? AND id_usuario = ?";
$stmt_membro = mysqli_prepare($conn, $sql_membro);
mysqli_stmt_bind_param($stmt_membro, "ii", $comunidadeId, $userId);
mysqli_stmt_execute($stmt_membro);
$result_membro = mysqli_stmt_get_result($stmt_membro);

if ($membro = mysqli_fetch_assoc($result_membro)) {
    $isMember = true;
    // Verifica se é admin (depende de você ter adicionado a coluna 'is_admin' ao DB)
    $isAdmin = $membro['is_admin'] ?? false; 
}
mysqli_stmt_close($stmt_membro);


// ====================================================================
// 3. LÓGICA DE ENTRAR/SAIR DA COMUNIDADE (POST)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'join' && !$isMember) {
        $sql_join = "INSERT INTO membros_comunidade (id_comunidade, id_usuario) VALUES (?, ?)";
        $stmt_join = mysqli_prepare($conn, $sql_join);
        mysqli_stmt_bind_param($stmt_join, "ii", $comunidadeId, $userId);
        mysqli_stmt_execute($stmt_join);
        mysqli_stmt_close($stmt_join);
        // Redireciona para atualizar a página e o status
        header("Location: comunidade.php?id={$comunidadeId}");
        exit;
    } 
    
    if ($_POST['action'] == 'leave' && $isMember) {
        $sql_leave = "DELETE FROM membros_comunidade WHERE id_comunidade = ? AND id_usuario = ?";
        $stmt_leave = mysqli_prepare($conn, $sql_leave);
        mysqli_stmt_bind_param($stmt_leave, "ii", $comunidadeId, $userId);
        mysqli_stmt_execute($stmt_leave);
        mysqli_stmt_close($stmt_leave);
        // Redireciona para atualizar a página e o status
        header("Location: comunidade.php?id={$comunidadeId}");
        exit;
    }
}


// ====================================================================
// 4. BUSCAR POSTS DA COMUNIDADE
// ====================================================================
// Esta consulta assume que a tabela 'postagens' tem uma coluna 'id_comunidade'
$sql_posts = "
    SELECT 
        p.id, p.titulo, p.conteudo, p.data_criacao,
        u.apelido AS autor_apelido
    FROM 
        postagens p
    JOIN 
        usuarios u ON p.usuario_id = u.id
    WHERE 
        p.id_comunidade = ?
    ORDER BY 
        p.data_criacao DESC
";
$stmt_posts = mysqli_prepare($conn, $sql_posts);
mysqli_stmt_bind_param($stmt_posts, "i", $comunidadeId);
mysqli_stmt_execute($stmt_posts);
$result_posts = mysqli_stmt_get_result($stmt_posts);
$posts = mysqli_fetch_all($result_posts, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_posts);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nomeComunidade) ?> | NeuroBlogs</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="homePage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos específicos para a página da comunidade */
        .community-header {
            background-color: #f8f8f8;
            padding: 30px;
            border-bottom: 2px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .community-info h1 {
            color: #1e3c72;
            margin-bottom: 5px;
            font-size: 2rem;
        }
        .community-info p {
            color: #555;
            font-size: 1rem;
            max-width: 800px;
        }
        .action-area button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-join {
            background-color: #28a745; /* Verde para entrar */
            color: white;
        }
        .btn-leave {
            background-color: #dc3545; /* Vermelho para sair */
            color: white;
        }
        .btn-join:hover { background-color: #218838; }
        .btn-leave:hover { background-color: #c82333; }
        
        /* Estilos de postagens herdados de homePage.css */
        .posts-list {
            padding: 20px;
        }
        .post-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php // include "menu_navegacao.php"; ?>

    <div class="community-header">
        <div class="community-info">
            <h1><?= htmlspecialchars($nomeComunidade) ?> 
                <?php if ($isAdmin): ?>
                    <i class="fas fa-crown" style="color:#FFD700; font-size: 1.2rem;" title="Administrador"></i>
                <?php endif; ?>
            </h1>
            <p><?= empty($comunidade['descricao']) ? "Nenhuma descrição fornecida." : htmlspecialchars($comunidade['descricao']) ?></p>
        </div>
        
        <div class="action-area">
            <form method="POST">
                <?php if ($isMember): ?>
                    <input type="hidden" name="action" value="leave">
                    <button type="submit" class="btn-leave">
                        <i class="fas fa-sign-out-alt"></i> Sair da Comunidade
                    </button>
                <?php else: ?>
                    <input type="hidden" name="action" value="join">
                    <button type="submit" class="btn-join">
                        <i class="fas fa-user-plus"></i> Entrar na Comunidade
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <main class="posts-list">
        <h2>Posts da Comunidade</h2>
        
        <?php if ($isMember): ?>
            <a href="postar.php?comunidade_id=<?= $comunidadeId ?>" class="btn-full" style="margin-bottom: 20px; display: block; background-color: #1067d8;">
                Criar Novo Post nesta Comunidade
            </a>
        <?php endif; ?>

        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <h3 class="post-title"><?= htmlspecialchars($post['titulo']) ?></h3>
                    <p class="post-meta">
                        Postado por **<?= htmlspecialchars($post['autor_apelido']) ?>** em 
                        <?= date('d/m/Y H:i', strtotime($post['data_criacao'])) ?>
                    </p>
                    <p class="post-content">
                        <?= nl2br(htmlspecialchars(substr($post['conteudo'], 0, 300))) ?>
                        <?php if (strlen($post['conteudo']) > 300): ?>
                            ... <a href="post_detalhe.php?id=<?= $post['id'] ?>">Ler mais</a>
                        <?php endif; ?>
                    </p>
                    </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum post foi feito nesta comunidade ainda.</p>
        <?php endif; ?>
    </main>
</body>
</html>