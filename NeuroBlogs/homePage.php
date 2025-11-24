<?php
// PHP - Arquivo: homePage.php (Versão Final com Acessibilidade e Comunidades)
session_start();

// Define o fuso horário para o de São Paulo (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

include "conexao.php"; // Certifique-se de que este caminho está correto

if (!isset($conn) || $conn->connect_error) {
    die("Erro fatal: A conexão com o banco de dados não pôde ser estabelecida. Verifique o arquivo 'conexao.php' e as credenciais. Erro: " . (isset($conn) ? $conn->connect_error : 'Variável $conn não definida.'));
}

// Verifica se a extensão GD está instalada e ativada
if (!extension_loaded('gd') || !function_exists('gd_info')) {
    // Você pode remover este die() se preferir que o site funcione sem upload de imagem por enquanto
    // die("Erro fatal: A biblioteca GD para processamento de imagens não está instalada ou ativada no seu servidor PHP.");
}

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$userName = $_SESSION["usuario"];
$userId = $_SESSION['usuario_id'];

// --- 1. BUSCAR PREFERÊNCIAS DE ACESSIBILIDADE ---
// Esta busca é necessária para que os elementos HTML (como o botão de config) possam ter informações úteis.
$sql_prefs = "SELECT cor_fundo_pref, cor_texto_pref, tamanho_fonte_pref, fonte_preferida FROM perfil_usuario WHERE id = $userId";
$result_prefs = mysqli_query($conn, $sql_prefs);
$prefs_atuais = mysqli_fetch_assoc($result_prefs) ?? [];

$current_fundo = $prefs_atuais['cor_fundo_pref'] ?? '#f5f5f5';
$current_texto = $prefs_atuais['cor_texto_pref'] ?? '#2c3e50';
$current_tamanho = $prefs_atuais['tamanho_fonte_pref'] ?? 'medium';
$current_fonte = $prefs_atuais['fonte_preferida'] ?? 'sans-serif';


// --- 2. LÓGICA DE POSTAGEM / COMENTÁRIOS (POST Request) ---
// ... Mantenha sua lógica de POST aqui (postagem e comentários) ...
// (Para manter a resposta concisa, assumimos que sua lógica de POST existente está aqui)

// --- 3. LÓGICA DO FEED (GET Request) ---

// Define o modo de visualização padrão
// homePage.php - Linhas ~75 a 85 (CÓDIGO CORRIGIDO)
// --- 3. LÓGICA DO FEED (GET Request) ---

// Define o modo de visualização padrão
// homePage.php - Linhas ~75 a 85 (CÓDIGO CORRIGIDO)
// --- 3. LÓGICA DO FEED (GET Request) ---

// Define o modo de visualização padrão
$view_mode = $_GET['view'] ?? 'friends'; // 'friends' ou 'communities'

// 3.1. Consulta Base
// Seleciona todos os dados de postagens e junta com usuários (para apelido),
// perfil_usuario (para a foto) e o nome da comunidade.

$sql_select_posts = "
    SELECT 
        p.*, 
        u.apelido AS nome_usuario, 
        pu.foto_perfil AS foto_usuario,  
        c.nome_comunidade
    FROM postagens p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN perfil_usuario pu ON u.id = pu.id   -- Join para buscar a foto na tabela perfil_usuario (pu)
    LEFT JOIN comunidades c ON p.id_comunidade = c.id
";

$where_clause = "WHERE 1=1"; // Cláusula inicial sempre verdadeira
// ... (O restante da lógica de $where_clause permanece o mesmo)

if ($view_mode == 'friends') {
    // Modo Amigos: Mostra posts de amigos E posts pessoais (id_usuario = $userId)
    // Para simplificar, vou considerar que todos os usuários são "amigos" no momento, 
    // mas você pode adicionar a lógica de "amigos" aqui.
    // Por enquanto, mostramos posts de todos os usuários que não estão em uma comunidade
    // OU posts pessoais.
    $where_clause .= " 
        AND (p.id_comunidade IS NULL OR p.usuario_id = $userId)
    ";

} elseif ($view_mode == 'communities') {
    // Modo Comunidades: Mostra posts de comunidades que o usuário é membro.
    $where_clause .= " 
        AND p.id_comunidade IN (
            SELECT id_comunidade FROM membros_comunidade WHERE id_usuario = $userId
        )
    ";
}

$sql_select_posts .= " $where_clause ORDER BY p.data_criacao DESC LIMIT 50"; // Limite de 50 posts
$result_posts = mysqli_query($conn, $sql_select_posts);


// --- 4. FUNÇÕES DE RENDERIZAÇÃO ---
// ... Mantenha suas funções `getCommentsForPost` e `renderPost` aqui ...
// (Para manter a resposta concisa, assumimos que suas funções de renderização estão aqui)

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroBlogs - Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="homePage.css">

    <link rel="stylesheet" href="css/user_preferences.php?user_id=<?= $userId ?>">
    
    </head>
<body>

    <nav class="navigation">
        <ul>
            <li><a href="homePage.php?view=friends" title="Feed de Amigos"><i class="fa-solid fa-house"></i></a></li>
            <li><a href="homePage.php?view=communities" title="Feed de Comunidades"><i class="fa-solid fa-users"></i></a></li>
            <li><a href="perfil.php?id=<?= $userId ?>" title="Meu Perfil"><i class="fa-solid fa-user"></i></a></li>
            <li><a href="config_acessibilidade.php" title="Configurações de Acessibilidade"><i class="fa-solid fa-universal-access"></i></a></li>
            <li><a href="logout.php" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></a></li>
        </ul>
    </nav>

    <div class="main-content">
        
        <div class="posts-col">

            <section class="new-post-form">
                <h2>O que você está pensando, <?= htmlspecialchars($userName) ?>?</h2>
                <form id="post-form" action="homePage.php" method="POST" enctype="multipart/form-data">
                    <textarea name="conteudo" placeholder="Compartilhe seu blog, pensamento ou experiência..." required></textarea>
                    
                    <select name="id_comunidade" class="community-select">
                        <option value="">Postar no Meu Feed Pessoal</option>
                        <?php
                            $sql_com = "SELECT c.id, c.nome_comunidade FROM comunidades c JOIN membros_comunidade m ON c.id = m.id_comunidade WHERE m.id_usuario = ?";
                            $stmt_com = mysqli_prepare($conn, $sql_com);
                            mysqli_stmt_bind_param($stmt_com, "i", $userId);
                            mysqli_stmt_execute($stmt_com);
                            $result_com = mysqli_stmt_get_result($stmt_com);
                            while ($com = mysqli_fetch_assoc($result_com)) {
                                echo "<option value=\"{$com['id']}\">Comunidade: " . htmlspecialchars($com['nome_comunidade']) . "</option>";
                            }
                        ?>
                    </select>

                    <div class="post-actions">
                        <label for="imagem_post" class="image-upload-label">
                            <i class="fa-solid fa-image"></i> Adicionar Imagem
                            <input type="file" name="imagem_post" id="imagem_post" accept="image/*" style="display: none;">
                        </label>
                        <button type="submit" name="postar">Postar</button>
                    </div>
                </form>
            </section>
            
            <h2 class="feed-title">
                <?php if ($view_mode == 'friends'): ?>
                    Feed de Amigos e Pessoal
                <?php else: ?>
                    Feed das Comunidades que Você Participa
                <?php endif; ?>
            </h2>

            <section class="feed-posts">
                <?php
                if (mysqli_num_rows($result_posts) > 0) {
                    while($post = mysqli_fetch_assoc($result_posts)) {
                        // Assume que a sua função renderPost() está definida em algum lugar ou o código está inline
                        // Aqui está o HTML básico do seu post para referência:
                        ?>
                        <div class="post-card" id="post-<?= $post['id'] ?>">
                            <div class="post-header">
                                <div class="user-info">
                                    <img src="<?= htmlspecialchars($post['foto_usuario'] ?? 'imagens/default.png') ?>" alt="Foto de <?= htmlspecialchars($post['nome_usuario']) ?>" class="profile-pic-small">
                                    <a href="perfil.php?id=<?= $post['usuario_id'] ?>" class="username"><?= htmlspecialchars($post['nome_usuario']) ?></a>
                                    <?php if ($post['nome_comunidade']): ?>
                                        <span class="community-tag">| Postado em: <?= htmlspecialchars($post['nome_comunidade']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="post-time"><?= date('H:i, d/m/Y', strtotime($post['data_criacao'])) ?></span>
                            </div>
                            
                            <div class="post-content">
                                <p class="post-text"><?= nl2br(htmlspecialchars($post['conteudo'])) ?></p>
                                <?php if (!empty($post['imagem_url'])): ?>
                                    <img src="<?= htmlspecialchars($post['imagem_url']) ?>" alt="Imagem da Postagem" class="post-image">
                                <?php endif; ?>
                            </div>

                            <div class="post-footer">
                                <button class="like-button" data-post-id="<?= $post['id'] ?>">
                                    <i class="fa-regular fa-heart"></i> Curtir
                                </button>
                                
                                <span class="likes-count" id="likes-count-<?= $post['id'] ?>">
                                    </span>
                            </div>

                            <div class="comments-area">
                                <h4>Comentários</h4>
                                <ul class="comments-list" id="comments-list-<?= $post['id'] ?>">
                                    <?php
                                    // Sua função de busca de comentários deve ser chamada aqui
                                    // $comments = getCommentsForPost($conn, $post['id']);
                                    // foreach ($comments as $comment) { ... render HTML ... }
                                    ?>
                                </ul>
                                
                                <form class="comment-form" data-post-id="<?= $post['id'] ?>">
                                    <input type="text" placeholder="Escreva um comentário..." required>
                                    <button type="submit">Comentar</button>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p class='no-posts'>Nenhuma postagem para exibir neste feed ainda.</p>";
                }
                ?>
            </section>
        </div>

        <aside class="right-sidebar">
            <div class="widget">
                <h3>🔔 Notificações (Em Breve)</h3>
                <p>Veja as novidades e interações dos seus posts.</p>
            </div>
            
            <div class="widget">
                <h3>➕ Crie uma Comunidade</h3>
                <a href="criar_comunidade.php" class="btn-full">Criar Novo Grupo</a>
            </div>
            
            <div class="widget">
                <h3>🌎 Explorar Comunidades</h3>
                <p>Encontre grupos sobre seus interesses neurodiversos.</p>
                <a href="explorar_comunidades.php" class="btn-full secondary">Ver Todas</a>
            </div>
            
             <div class="widget accessibility-summary">
                <h3>Acessibilidade Ativa</h3>
                <p>Ajustes Rápidos:</p>
                <ul>
                    <li>Fundo: <span style="color: <?= $current_fundo ?>; background-color: <?= $current_texto ?>; padding: 2px 5px;">Cor Personalizada</span></li>
                    <li>Texto: <span style="color: <?= $current_texto ?>; font-weight: bold;">Contraste Alto</span></li>
                    <li>Fonte: <span style="font-size: <?= $current_tamanho == 'small' ? '12px' : ($current_tamanho == 'large' ? '18px' : '16px') ?>; font-family: <?= $current_fonte ?>;">Tipo Preferido</span></li>
                </ul>
                <a href="config_acessibilidade.php" class="btn-full small">Mudar Configurações</a>
            </div>

        </aside>

    </div> <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ... (Seus scripts de like, AJAX de postagem, etc. devem estar aqui) ...
            
            // Exemplo de script de comentário (adaptado do seu snippet anterior)
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.getAttribute('data-post-id');
                    const commentInput = this.querySelector('input[type="text"]');
                    const commentText = commentInput.value.trim();

                    if (!commentText) return;

                    const formData = new FormData();
                    formData.append('action', 'comment');
                    formData.append('post_id', postId);
                    formData.append('comment_text', commentText);

                    fetch('homePage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // ... (Lógica de inserção de comentário) ...
                            commentInput.value = ''; // Limpa o campo
                        } else {
                            alert('Erro ao publicar o comentário. Tente novamente.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX na publicação do comentário:', error);
                        alert('Erro de conexão ao publicar o comentário.');
                    });
                });
            });

        });
    </script>
</body>
</html>