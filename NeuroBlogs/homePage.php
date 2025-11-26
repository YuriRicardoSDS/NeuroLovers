<?php
// PHP - Arquivo: homePage.php (Layout de Duas Colunas Restaurado com Funcionalidades)
session_start();

// Define o fuso horário para o de São Paulo (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

include "conexao.php"; 

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

// --- Função para Redimensionar e Otimizar Imagens ---
// [Manter o conteúdo completo da função resizeImage aqui]
function resizeImage($file, $maxWidth = 800, $maxHeight = 600) {
    $info = getimagesize($file);
    if ($info === false) { return false; }
    list($originalWidth, $originalHeight) = $info;
    $mime = $info['mime'];

    if ($originalWidth <= $maxWidth) { $width = $originalWidth; $height = $originalHeight; } 
    else { $ratio = $maxWidth / $originalWidth; $width = $maxWidth; $height = $originalHeight * $ratio; }
    
    if ($height > $maxHeight) { $ratio = $maxHeight / $originalHeight; $height = $maxHeight; $width = $originalWidth * $ratio; }
    
    $image = imagecreatetruecolor($width, $height);

    if ($mime == 'image/jpeg' || $mime == 'image/jpg') { $source = imagecreatefromjpeg($file); } 
    elseif ($mime == 'image/png') { $source = imagecreatefrompng($file); } 
    elseif ($mime == 'image/gif') { $source = imagecreatefromgif($file); } 
    else { return false; }

    imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
    
    $temp_path = tempnam(sys_get_temp_dir(), 'img');
    imagejpeg($image, $temp_path, 90); 
    imagedestroy($image);
    imagedestroy($source);

    return $temp_path;
}


// --- 1. BUSCAR PREFERÊNCIAS DE ACESSIBILIDADE ---
$sql_prefs = "SELECT cor_fundo_pref, cor_texto_pref, tamanho_fonte_pref, fonte_preferida FROM perfil_usuario WHERE id = $userId";
$result_prefs = mysqli_query($conn, $sql_prefs);
$prefs_atuais = mysqli_fetch_assoc($result_prefs) ?? [];


// --- 2. LÓGICA DE POST/LIKE/COMMENT (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Lógica de Postar Nova Mensagem
        if ($action == 'post' && isset($_POST['conteudo'])) {
            $conteudo = trim($_POST['conteudo']);
            // O campo 'id_comunidade' agora aceita 'NULL' se for uma postagem pessoal
            $id_comunidade = empty($_POST['id_comunidade']) ? NULL : (int)$_POST['id_comunidade'];
            $imagem_post = NULL;

            if (empty($conteudo) && empty($_FILES['imagem_post']['tmp_name'])) {
                // Não faz nada se a postagem estiver vazia
            } else {
                if (isset($_FILES['imagem_post']) && $_FILES['imagem_post']['error'] == 0) {
                    $upload_dir = 'uploads/posts/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                    
                    $temp_file = $_FILES['imagem_post']['tmp_name'];
                    $original_name = $_FILES['imagem_post']['name'];
                    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    $new_filename = uniqid('post_') . '.' . $ext;
                    $target_file = $upload_dir . $new_filename;

                    $optimized_temp_path = resizeImage($temp_file);

                    if ($optimized_temp_path && move_uploaded_file($optimized_temp_path, $target_file)) {
                        $imagem_post = $target_file;
                    } elseif ($optimized_temp_path) {
                        if (rename($optimized_temp_path, $target_file)) {
                            $imagem_post = $target_file;
                        } else {
                            if (move_uploaded_file($temp_file, $target_file)) {
                                $imagem_post = $target_file;
                            }
                        }
                    } else {
                        if (move_uploaded_file($temp_file, $target_file)) {
                            $imagem_post = $target_file;
                        }
                    }
                }
                
                // Usando bind_param para lidar com o NULL na comunidade
                $sql = "INSERT INTO postagens (usuario_id, conteudo, imagem_path, id_comunidade) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                
                // Tipo de parâmetro ajustado para 's' se id_comunidade for NULL
                if ($id_comunidade === NULL) {
                    $null_param = NULL;
                    mysqli_stmt_bind_param($stmt, "issb", $userId, $conteudo, $imagem_post, $null_param);
                } else {
                    mysqli_stmt_bind_param($stmt, "issi", $userId, $conteudo, $imagem_post, $id_comunidade);
                }

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                header("Location: homePage.php");
                exit;
            }
        }
        
        // Lógica de Curtir (AJAX) - MANTIDA
        if ($action == 'like' && isset($_POST['post_id'])) {
            $postId = (int)$_POST['post_id'];
            
            $sql_check = "SELECT id FROM curtidas WHERE id_postagem = ? AND id_usuario = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "ii", $postId, $userId);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            $json_response = ['success' => false, 'is_liked' => false, 'likes_count' => 0];

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                // DESCURTE
                $sql_action = "DELETE FROM curtidas WHERE id_postagem = ? AND id_usuario = ?";
                $is_liked = false;
            } else {
                // CURTE
                $sql_action = "INSERT INTO curtidas (id_postagem, id_usuario) VALUES (?, ?)";
                $is_liked = true;
            }
            mysqli_stmt_close($stmt_check);

            $stmt_action = mysqli_prepare($conn, $sql_action);
            mysqli_stmt_bind_param($stmt_action, "ii", $postId, $userId);
            mysqli_stmt_execute($stmt_action);
            mysqli_stmt_close($stmt_action);

            // Obtém o novo total de curtidas
            $sql_count = "SELECT COUNT(id) as total_likes FROM curtidas WHERE id_postagem = ?";
            $stmt_count = mysqli_prepare($conn, $sql_count);
            mysqli_stmt_bind_param($stmt_count, "i", $postId);
            mysqli_stmt_execute($stmt_count);
            $result_count = mysqli_stmt_get_result($stmt_count);
            $total_likes = mysqli_fetch_assoc($result_count)['total_likes'];
            mysqli_stmt_close($stmt_count);

            $json_response['success'] = true;
            $json_response['is_liked'] = $is_liked;
            $json_response['likes_count'] = $total_likes;
            
            header('Content-Type: application/json');
            echo json_encode($json_response);
            exit;
        }

        // Lógica de Comentar (AJAX) - MANTIDA
        if ($action == 'comment' && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
            $postId = (int)$_POST['post_id'];
            $commentText = trim($_POST['comment_text']);

            $json_response = ['success' => false];

            if (!empty($commentText)) {
                $sql = "INSERT INTO comentarios (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iis", $postId, $userId, $commentText);
                
                if (mysqli_stmt_execute($stmt)) {
                    $json_response['success'] = true;
                    // Montar o HTML do novo comentário para inserção via AJAX
                    $json_response['new_comment_html'] = "
                        <div class='comment'>
                            <p><strong>" . htmlspecialchars($userName) . ":</strong> " . nl2br(htmlspecialchars($commentText)) . "</p>
                            <span class='comment-time'>Agora</span>
                        </div>
                    ";
                }
                mysqli_stmt_close($stmt);
            }

            header('Content-Type: application/json');
            echo json_encode($json_response);
            exit;
        }
    }
}


// --- 3. COMUNIDADES: Busca Comunidades do Usuário ---
$comunidades_usuario = [];
// Buscamos SOMENTE as comunidades que o usuário é MEMBRO para a lista de filtros e o SELECT de postagem
$sql_comunidades_usuario = "
    SELECT c.id, c.nome_comunidade AS nome 
    FROM comunidades c
    JOIN membros_comunidade mc ON c.id = mc.id_comunidade
    WHERE mc.id_usuario = ?
    ORDER BY c.nome_comunidade ASC
";
$stmt_comunidades = mysqli_prepare($conn, $sql_comunidades_usuario);
if ($stmt_comunidades) {
    mysqli_stmt_bind_param($stmt_comunidades, "i", $userId);
    mysqli_stmt_execute($stmt_comunidades);
    $result_comunidades = mysqli_stmt_get_result($stmt_comunidades);
    $comunidades_usuario = mysqli_fetch_all($result_comunidades, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_comunidades);
}

// --- 4. LÓGICA DE FILTRO (GET Request) ---
$view_mode = $_GET['view_mode'] ?? 'all'; 
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;


// --- 5. LÓGICA DO FEED: Consulta Principal ---
$sql_select_posts = "
    SELECT 
        p.*, 
        u.apelido AS nome_usuario, 
        pu.foto AS foto_usuario,  
        c.nome_comunidade AS nome_comunidade,
        COUNT(l.id) AS total_curtidas,
        (SELECT COUNT(id) FROM comentarios WHERE id_postagem = p.id) AS total_comentarios,
        (SELECT COUNT(id) FROM curtidas WHERE id_postagem = p.id AND id_usuario = ?) AS curtiu_usuario
    FROM postagens p
    JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN perfil_usuario pu ON u.id = pu.id 
    LEFT JOIN comunidades c ON p.id_comunidade = c.id
    LEFT JOIN curtidas l ON p.id = l.id_postagem
";

$where_clause = "WHERE 1=1"; 
$bind_types = "i";
$bind_params = [$userId];

// Filtro: Apenas posts de comunidades que o usuário segue
if ($view_mode == 'following' && !empty($comunidades_usuario)) {
    $comunidade_ids = array_column($comunidades_usuario, 'id');
    $ids_placeholder = implode(',', array_fill(0, count($comunidade_ids), '?'));
    
    // Filtra posts que têm um id_comunidade E esse ID está na lista de IDs que o usuário segue
    $where_clause .= " AND p.id_comunidade IS NOT NULL AND p.id_comunidade IN ({$ids_placeholder})";
    
    $bind_types .= str_repeat('i', count($comunidade_ids));
    $bind_params = array_merge($bind_params, $comunidade_ids);

// Filtro: Apenas posts pessoais (não ligados a nenhuma comunidade)
} elseif ($view_mode == 'friends') {
    $where_clause .= " AND p.id_comunidade IS NULL";
    
// Filtro: Todos os posts (pessoal + comunidades que o usuário segue)
} elseif ($view_mode == 'all' && !empty($comunidades_usuario)) {
    $comunidade_ids = array_column($comunidades_usuario, 'id');
    $ids_placeholder = implode(',', array_fill(0, count($comunidade_ids), '?'));
    
    // Inclui posts pessoais (id_comunidade IS NULL) OU posts de comunidades que o usuário segue
    $where_clause .= " AND (p.id_comunidade IS NULL OR p.id_comunidade IN ({$ids_placeholder}))";
    
    $bind_types .= str_repeat('i', count($comunidade_ids));
    $bind_params = array_merge($bind_params, $comunidade_ids);
}


$sql_select_posts .= " 
    {$where_clause}
    GROUP BY p.id
    ORDER BY p.data_criacao DESC
    LIMIT ? OFFSET ?
";

$bind_types .= "ii";
$bind_params[] = $limit;
$bind_params[] = $offset;


$stmt_posts = mysqli_prepare($conn, $sql_select_posts);

if ($stmt_posts) {
    mysqli_stmt_bind_param($stmt_posts, $bind_types, ...$bind_params);
    mysqli_stmt_execute($stmt_posts);
    $result_posts = mysqli_stmt_get_result($stmt_posts);
}


// --- 6. FUNÇÕES AUXILIARES ---
function display_post_card($post) {
    global $userId;
    // Esta função foi mantida igual para manter a funcionalidade do post card
    $time_ago = time_ago($post['data_criacao']); 
    $is_liked = $post['curtiu_usuario'] > 0;
    $profile_link = "perfil.php?id=" . $post['usuario_id'];
    $comunidade_html = '';
    if (!empty($post['nome_comunidade'])) {
        $comunidade_html = "<span class='post-community'> em <a href='comunidade.php?id={$post['id_comunidade']}'>" . htmlspecialchars($post['nome_comunidade']) . "</a></span>";
    }

    $like_class = $is_liked ? 'liked' : '';
    $like_icon = $is_liked ? 'fa-solid' : 'fa-regular';

    $foto_perfil = !empty($post['foto_usuario']) ? htmlspecialchars($post['foto_usuario']) : 'default_profile.png';
    $foto_url = 'uploads/perfil/' . $foto_perfil;
    
    $html = "<article class='post-card' data-post-id='{$post['id']}'>
        <div class='post-header'>
            <a href='{$profile_link}' class='user-link'>
                <img src='{$foto_url}' alt='Foto de Perfil' class='profile-photo small'>
                <strong>" . htmlspecialchars($post['nome_usuario']) . "</strong>
            </a>
            <span class='post-time'>{$time_ago} {$comunidade_html}</span>
        </div>

        <p class='post-text'>" . nl2br(htmlspecialchars($post['conteudo'])) . "</p>";

    if (!empty($post['imagem_path'])) {
        $html .= "<div class='post-image-wrapper'><img src='" . htmlspecialchars($post['imagem_path']) . "' alt='Imagem do post' class='post-image'></div>";
    }

    $html .= "
        <div class='post-footer'>
            <div class='post-actions'>
                <button class='btn-like {$like_class}' data-post-id='{$post['id']}'>
                    <i class='{$like_icon} fa-heart'></i>
                    <span class='likes-count'>{$post['total_curtidas']}</span> Curtidas
                </button>
                <button class='btn-comment' data-post-id='{$post['id']}'>
                    <i class='fa-regular fa-comment'></i>
                    <span class='comments-count'>{$post['total_comentarios']}</span> Comentários
                </button>
            </div>
            
            <div class='comments-section' id='comments-{$post['id']}'>
                <form class='comment-form' data-post-id='{$post['id']}'>
                    <input type='text' placeholder='Escreva um comentário...' required>
                    <button type='submit' class='btn-comment-submit'><i class='fa-solid fa-paper-plane'></i></button>
                </form>

                <div class='comments-list' id='comments-list-{$post['id']}'>
                </div>
            </div>
        </div>
    </article>";
    return $html;
}

function time_ago($datetime) {
    $tempo = time() - strtotime($datetime); 
    if ($tempo < 60) return "agora";
    $minutos = floor($tempo / 60);
    if ($minutos < 60) return "há {$minutos} min";
    $horas = floor($minutos / 60);
    if ($horas < 24) return "há {$horas} h";
    $dias = floor($horas / 24);
    if ($dias < 30) return "há {$dias} d";
    return date('d/m/Y', strtotime($datetime));
}

// Fim da Lógica PHP
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroBlogs - Feed</title>
    <link rel="stylesheet" href="homePage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script> 
    <link rel="stylesheet" href="user_preferences.php?user_id=<?= $userId ?>" type="text/css">
</head>
<body>

    <?php include "menu_navegacao.php"; ?> 

    <div class="main-content-wrapper-two-col"> 
        
        <main class="feed-main-col"> 
            
            <section class="new-post-form card">
                <form action="homePage.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="post">
                    
                    <div class="form-header">
                        <h3 class="form-title">O que você está pensando, <?= htmlspecialchars($userName) ?>?</h3>
                        <select name="id_comunidade" class="community-select" aria-label="Selecione uma comunidade">
                            <option value="" selected>Postar Pessoalmente</option>
                            <?php 
                            if (!empty($comunidades_usuario)) {
                                foreach ($comunidades_usuario as $comunidade) {
                                    echo "<option value='{$comunidade['id']}'>" . htmlspecialchars($comunidade['nome']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <textarea name="conteudo" rows="3" placeholder="Compartilhe seus pensamentos, ideias ou sentimentos..." class="post-text-area"></textarea>
                    
                    <div class="post-actions">
                        <label for="imagem_post" class="image-upload-label">
                            <i class="fa-solid fa-image"></i> Adicionar Imagem
                            <input type="file" name="imagem_post" id="imagem_post" accept="image/*" style="display: none;">
                        </label>
                        <button type="submit" name="postar" class="btn-full small">Publicar</button>
                    </div>
                </form>
            </section>


            <section class="feed-container">
                <?php 
                if ($result_posts && mysqli_num_rows($result_posts) > 0) {
                    while ($post = mysqli_fetch_assoc($result_posts)) {
                        echo display_post_card($post); 
                    }
                } elseif ($result_posts) {
                    echo "<div class='no-posts-message card'>Nenhuma postagem encontrada com os filtros atuais.</div>";
                } else {
                    echo "<div class='no-posts-message card error-message'>Erro ao carregar as postagens.</div>";
                }
                if (isset($stmt_posts)) {
                    mysqli_stmt_close($stmt_posts);
                }
                ?>
            </section>
        </main> 

        <aside class="sidebar-right">
            <div class="filter-panel card">
                <h4>Filtros de Visualização</h4>
                <form action="homePage.php" method="GET" class="filter-form">
                    <label>
                        <input type="radio" name="view_mode" value="all" <?= ($view_mode == 'all') ? 'checked' : '' ?>> 
                        Todas as Publicações (Comunidades + Pessoal)
                    </label>
                    <label>
                        <input type="radio" name="view_mode" value="following" <?= ($view_mode == 'following') ? 'checked' : '' ?>> 
                        Apenas Comunidades que Sigo
                    </label>
                    <label>
                        <input type="radio" name="view_mode" value="friends" <?= ($view_mode == 'friends') ? 'checked' : '' ?>> 
                        Apenas Posts Pessoais
                    </label>
                    <button type="submit" class="btn-full small mt-3">Aplicar Filtro</button>
                </form>
            </div>
            
            <div class="suggestions-panel card mt-4">
                <h4>Comunidades</h4>
                <p class="mb-3">Encontre espaços de interesse, dúvidas e experiências compartilhadas.</p>
                <a href="comunidades.php" class="btn-full small">Ver Todas as Comunidades</a>
                
                <h5 class="mt-4">Minhas Comunidades (<?= count($comunidades_usuario) ?>)</h5>
                <ul class="suggestion-list">
                    <?php 
                    if (!empty($comunidades_usuario)) {
                        foreach ($comunidades_usuario as $comunidade) {
                            echo "<li><a href='comunidade.php?id={$comunidade['id']}'>" . htmlspecialchars($comunidade['nome']) . "</a></li>";
                        }
                    } else {
                        echo "<li><small>Você ainda não faz parte de nenhuma comunidade.</small></li>";
                    }
                    ?>
                </ul>
                <a href="criar_comunidade.php" class="btn-full small mt-3 btn-secondary">Criar Nova Comunidade</a>
            </div>
        </aside>

    </div> 
    <script>
        // Inicializa os ícones do Lucide
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', function() {
            // Lógica de Curtir (AJAX) - MANTIDA
            document.querySelectorAll('.btn-like').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const isLiked = this.classList.contains('liked');
                    const icon = this.querySelector('i');
                    const countSpan = this.querySelector('.likes-count');

                    const formData = new FormData();
                    formData.append('action', 'like');
                    formData.append('post_id', postId);

                    fetch('homePage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            countSpan.textContent = data.likes_count;
                            if (data.is_liked) {
                                button.classList.add('liked');
                                icon.classList.remove('fa-regular');
                                icon.classList.add('fa-solid');
                            } else {
                                button.classList.remove('liked');
                                icon.classList.remove('fa-solid');
                                icon.classList.add('fa-regular');
                            }
                        } else {
                            alert('Erro ao processar a curtida.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX na curtida:', error);
                        alert('Erro de conexão ao curtir.');
                    });
                });
            });

            // Lógica de Comentário (AJAX) - MANTIDA
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.closest('.post-card').getAttribute('data-post-id');
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
                            const commentsList = document.getElementById(`comments-list-${postId}`);
                            const noCommentsMessage = document.getElementById(`no-comments-message-${postId}`);
                            
                            if (noCommentsMessage) {
                                noCommentsMessage.remove();
                            }
                            
                            commentsList.insertAdjacentHTML('beforeend', data.new_comment_html);
                            // Atualiza a contagem de comentários no botão
                            const commentsCountSpan = document.querySelector(`.btn-comment[data-post-id="${postId}"] .comments-count`);
                            commentsCountSpan.textContent = parseInt(commentsCountSpan.textContent) + 1;

                            commentInput.value = ''; 
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
            
            // Lógica para Abrir/Fechar Comentários - MANTIDA
            document.querySelectorAll('.btn-comment').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentsSection = document.getElementById(`comments-${postId}`);
                    commentsSection.classList.toggle('active'); 
                });
            });
            
            // Lógica para previsualizar a imagem antes do upload
            document.getElementById('imagem_post').addEventListener('change', function(e) {
                const previewContainer = document.querySelector('.post-image-preview-wrapper');
                const file = e.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let previewHtml = `<div class="post-image-preview-wrapper"><img src="${e.target.result}" alt="Pré-visualização da imagem" class="post-image-preview"></div>`;
                        // Insere a pré-visualização após a textarea. Se for um layout antigo, pode ser um local diferente
                        document.querySelector('.post-text-area').insertAdjacentHTML('afterend', previewHtml);
                    };
                    reader.readAsDataURL(file);
                } else {
                    if(previewContainer) previewContainer.remove();
                }
            });

        });
    </script>
</body>
</html>