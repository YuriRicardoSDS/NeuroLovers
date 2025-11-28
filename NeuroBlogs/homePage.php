<?php
// PHP - Arquivo: homePage.php (Layout de Duas Colunas Restaurado com Funcionalidades)
session_start();

// Define o fuso horário para o de São Paulo (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

include "conexao.php"; 
include "menu_navegacao.php"; 


if (!isset($conn) || $conn->connect_error) {
    die("Erro fatal: A conexão com o banco de dados não pôde ser estabelecida. Verifique o arquivo 'conexao.php' e as credenciais. Erro: " . (isset($conn) ? $conn->connect_error : 'Variável $conn não definida.'));
}

// Verifica se a extensão GD está instalada e ativada
if (!extension_loaded('gd') || !function_exists('gd_info')) {
    // Manter como comentário para evitar parada no TCC, mas deve ser ativado se o upload for essencial.
    // die("Erro fatal: A biblioteca GD para processamento de imagens não está instalada ou ativada no seu servidor PHP.");
}

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$userName = $_SESSION["usuario"];
$userId = $_SESSION['usuario_id'];

// --- 1. FUNÇÃO PARA REDIMENSIONAR E OTIMIZAR IMAGENS ---
function resizeImage($file, $maxWidth = 800, $maxHeight = 600) {
    $info = getimagesize($file);
    if ($info === false) {
        return false;
    }
    list($originalWidth, $originalHeight) = $info;
    $mime = $info['mime'];

    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return $file;
    }

    $scale = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = ceil($scale * $originalWidth);
    $newHeight = ceil($scale * $originalHeight);
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($file);
            } else {
                return false; 
            }
            break;
        default:
            return false;
    }

    if ($image === false) {
        return false;
    }

    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($mime == 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    $temp_file_path = tempnam(sys_get_temp_dir(), 'resized_');

    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagepng($newImage, $temp_file_path, 9);
    } else {
        imagejpeg($newImage, $temp_file_path, 85); 
    }

    imagedestroy($image);
    imagedestroy($newImage);

    return $temp_file_path;
}

// --- 2. BUSCA DE PREFERÊNCIAS DE ACESSIBILIDADE ---
// CORREÇÃO: TROCANDO 'perfil_usuario' por 'perfis'
$sql_perfil = "SELECT cor_fundo_pref, cor_texto_pref, tamanho_fonte_pref, fonte_preferida FROM perfil_usuario WHERE id = ?";
$stmt_perfil = mysqli_prepare($conn, $sql_perfil);
$user_prefs = [];

if ($stmt_perfil) {
    mysqli_stmt_bind_param($stmt_perfil, "i", $userId);
    mysqli_stmt_execute($stmt_perfil);
    $res_perfil = mysqli_stmt_get_result($stmt_perfil);

    if ($res_perfil && $row = mysqli_fetch_assoc($res_perfil)) {
        $user_prefs = $row;
    }
    mysqli_stmt_close($stmt_perfil);
}

// Valores padrão se não houver preferências salvas
$default_prefs = [
    'cor_fundo_pref' => '#f5f5f5',
    'cor_texto_pref' => '#2c3e50',
    'tamanho_fonte_pref' => '16px',
    'fonte_preferida' => 'sans-serif'
];
$prefs = array_merge($default_prefs, $user_prefs);

// --- 3. LÓGICA DE AÇÃO (Post, Like, Comment) ---
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false];

// Lógica de Postar Nova Mensagem
if ($action == 'post' && isset($_POST['conteudo'])) {
    $conteudo = trim($_POST['conteudo']);
    $imagem_post = NULL;
    $id_comunidade = isset($_POST['id_comunidade']) ? intval($_POST['id_comunidade']) : 0; // Novo campo obrigatório

    if (empty($conteudo) && !isset($_FILES['imagem_post'])) {
        $response['message'] = "O post não pode ser vazio.";
    } elseif ($id_comunidade <= 0) {
        $response['message'] = "Você deve selecionar uma comunidade para postar."; // Enforce community
    } else {
        $upload_successful = false;

        // Se uma imagem foi enviada
        if (isset($_FILES['imagem_post']) && $_FILES['imagem_post']['error'] == 0) {
            $upload_dir = 'uploads/posts/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            $temp_file = $_FILES['imagem_post']['tmp_name'];
            $original_name = $_FILES['imagem_post']['name'];
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $new_filename = uniqid('post_') . '.' . $ext;
            $target_file = $upload_dir . $new_filename;

            // Tenta redimensionar/otimizar
            $optimized_temp_path = resizeImage($temp_file);

            if ($optimized_temp_path) {
                // 1. Otimização BEM SUCEDIDA: Mova o arquivo TEMPORÁRIO OTIMIZADO
                if (rename($optimized_temp_path, $target_file) || copy($optimized_temp_path, $target_file)) {
                    $imagem_post = $target_file;
                    $upload_successful = true;
                    if (file_exists($optimized_temp_path)) unlink($optimized_temp_path);
                } 
                if (is_uploaded_file($temp_file)) {
                     unlink($temp_file);
                }
            } else {
                // 2. Otimização FALHOU, faz o upload direto do arquivo original
                if (move_uploaded_file($temp_file, $target_file)) {
                    $imagem_post = $target_file;
                    $upload_successful = true;
                }
            }
        }

        // CORREÇÃO: Trocar 'postagens' para 'posts_comunidade'
        $sql = "INSERT INTO posts_comunidade (usuario_id, conteudo, imagem, id_comunidade) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issi", $userId, $conteudo, $imagem_post, $id_comunidade);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
            } else {
                $response['message'] = "Erro ao inserir post no banco: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = "Erro na preparação da query: " . mysqli_error($conn);
        }
    }
}

// Lógica de Curtir Post (AJAX)
if ($action == 'like' && isset($_POST['post_id'])) {
    $postId = intval($_POST['post_id']);
    
    // CORREÇÃO: Tabela 'curtidas' trocada para 'curtidas_comunidade'
    $sql_check = "SELECT id FROM curtidas_comunidade WHERE id_postagem = ? AND id_usuario = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    
    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "ii", $postId, $userId);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        $alreadyLiked = mysqli_stmt_num_rows($stmt_check) > 0;
        mysqli_stmt_close($stmt_check);

        if ($alreadyLiked) {
            // Descurtir
            $sql_action = "DELETE FROM curtidas_comunidade WHERE id_postagem = ? AND id_usuario = ?";
        } else {
            // Curtir
            $sql_action = "INSERT INTO curtidas_comunidade (id_postagem, id_usuario) VALUES (?, ?)";
        }
        
        $stmt_action = mysqli_prepare($conn, $sql_action);
        if ($stmt_action) {
            mysqli_stmt_bind_param($stmt_action, "ii", $postId, $userId);
            if (mysqli_stmt_execute($stmt_action)) {
                $response['success'] = true;
                $response['liked'] = !$alreadyLiked;
                
                // Recalcula a contagem
                $sql_count = "SELECT COUNT(*) FROM curtidas_comunidade WHERE id_postagem = ?";
                $stmt_count = mysqli_prepare($conn, $sql_count);
                if ($stmt_count) {
                    mysqli_stmt_bind_param($stmt_count, "i", $postId);
                    mysqli_stmt_execute($stmt_count);
                    mysqli_stmt_bind_result($stmt_count, $likeCount);
                    mysqli_stmt_fetch($stmt_count);
                    $response['new_count'] = $likeCount;
                    mysqli_stmt_close($stmt_count);
                }
            }
        }
    }
}

// Lógica de Comentar Post (AJAX)
if ($action == 'comment' && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $postId = intval($_POST['post_id']);
    $commentText = trim($_POST['comment_text']);

    if (!empty($commentText)) {
        // CORREÇÃO: Tabela 'comentarios' trocada para 'comentarios_comunidade'
        $sql = "INSERT INTO comentarios_comunidade (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $postId, $userId, $commentText);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $commentId = mysqli_insert_id($conn);
                $commentTime = time();

                // Formata o novo comentário para inserção imediata no DOM
                $response['new_comment_html'] = "
                    <div class='comment-item' id='comment-{$commentId}'>
                        <div class='comment-header'>
                            <span class='comment-author'>{$userName}</span>
                            <span class='comment-time'>agora mesmo</span>
                        </div>
                        <p class='comment-content'>{$commentText}</p>
                    </div>";

            } else {
                $response['message'] = "Erro ao inserir comentário: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Se a requisição for AJAX, retorna a resposta em JSON e encerra
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// --- FIM DA LÓGICA DE AÇÃO ---

// --- 3.5. BUSCA DE COMUNIDADES DO USUÁRIO PARA O FORMULÁRIO ---
$user_communities = [];
$sql_fetch_user_communities = "
    SELECT c.id, c.nome_comunidade 
    FROM comunidades c
    JOIN membros_comunidade mc ON c.id = mc.id_comunidade
    WHERE mc.id_usuario = ?
    ORDER BY c.nome_comunidade ASC";

$stmt_comm_form = mysqli_prepare($conn, $sql_fetch_user_communities);

if ($stmt_comm_form) {
    mysqli_stmt_bind_param($stmt_comm_form, "i", $userId);
    mysqli_stmt_execute($stmt_comm_form);
    $result_comm_form = mysqli_stmt_get_result($stmt_comm_form);

    while ($row = mysqli_fetch_assoc($result_comm_form)) {
        $user_communities[] = $row;
    }
    mysqli_stmt_close($stmt_comm_form);
}
// --- FIM DA BUSCA DE COMUNIDADES ---


// --- 4. PAGINAÇÃO E VARIÁVEIS DE EXIBIÇÃO ---
$posts_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $posts_per_page;


// --- 5. LÓGICA DO FEED: Consulta Principal ---

// 1. Define o modo de visualização (all, following)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'all'; 
$current_page_url = "homePage.php?view=$view_mode";

// Prepara as variáveis para a consulta
$where_clause = " WHERE p.id IS NOT NULL "; // Cláusula base
$bind_types = "";
$bind_params = [];

// Lista de IDs de comunidades que o usuário segue (necessário para os filtros)
$comunidades_usuario = [];
$sql_comunidades_seguidas = "SELECT id FROM comunidades c JOIN membros_comunidade mc ON c.id = mc.id_comunidade WHERE mc.id_usuario = ?";
$stmt_comunidades_seguidas = mysqli_prepare($conn, $sql_comunidades_seguidas);
if ($stmt_comunidades_seguidas) {
    mysqli_stmt_bind_param($stmt_comunidades_seguidas, "i", $userId);
    mysqli_stmt_execute($stmt_comunidades_seguidas);
    $result_comunidades = mysqli_stmt_get_result($stmt_comunidades_seguidas);
    while ($row = mysqli_fetch_assoc($result_comunidades)) {
        $comunidades_usuario[] = $row['id'];
    }
    mysqli_stmt_close($stmt_comunidades_seguidas);
}

// NOVO BLOCO DE FILTRAGEM: Foco nas Comunidades

// Todos os filtros agora SÓ BUSCAM POSTS DE COMUNIDADES
$where_clause .= " AND p.id_comunidade IS NOT NULL ";

if ($view_mode == 'following' && !empty($comunidades_usuario)) {
    // Modo: APENAS POSTS DE COMUNIDADES SEGUIDAS
    $ids_placeholder = implode(',', array_fill(0, count($comunidades_usuario), '?'));
    $where_clause .= " AND p.id_comunidade IN ({$ids_placeholder})";
    
    // Adiciona os IDs das comunidades aos parâmetros de binding
    $bind_types .= str_repeat('i', count($comunidades_usuario));
    $bind_params = array_merge($bind_params, $comunidades_usuario);

} else if ($view_mode == 'all' || ($view_mode == 'following' && empty($comunidades_usuario))) {
    // Modo: TODOS OS POSTS DE TODAS AS COMUNIDADES (Principal/Padrão)
}


// CORREÇÃO: Tabela 'postagens' trocada para 'posts_comunidade'
// CORREÇÃO: Tabelas de interações trocadas para '_comunidade'
$sql_select_posts = "SELECT p.id, p.usuario_id, u.apelido, p.conteudo, p.imagem, p.data_criacao,
                            (SELECT COUNT(*) FROM curtidas_comunidade lc WHERE lc.id_postagem = p.id) AS likes_count,
                            (SELECT COUNT(*) FROM comentarios_comunidade cc WHERE cc.id_postagem = p.id) AS comments_count,
                            c.nome_comunidade, c.id AS comunidade_id
                     FROM posts_comunidade p 
                     JOIN usuarios u ON p.usuario_id = u.id
                     LEFT JOIN comunidades c ON p.id_comunidade = c.id
                     {$where_clause}
                     GROUP BY p.id
                     ORDER BY p.data_criacao DESC
                     LIMIT ? OFFSET ?";
                     
$bind_types .= 'ii';
$bind_params[] = $posts_per_page;
$bind_params[] = $offset;


$stmt_posts = mysqli_prepare($conn, $sql_select_posts);
$result_posts = false;

if ($stmt_posts) {
    $bind_refs = array($bind_types);
    for ($i = 0; $i < count($bind_params); $i++) {
        $bind_refs[] = &$bind_params[$i];
    }
    call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt_posts), $bind_refs));

    mysqli_stmt_execute($stmt_posts);
    $result_posts = mysqli_stmt_get_result($stmt_posts);
    
    mysqli_stmt_close($stmt_posts);
}


// Lógica de Contagem Total de Posts para a Paginação (também ajustada para posts_comunidade)
$sql_count_posts = "SELECT COUNT(p.id) AS total_posts 
                    FROM posts_comunidade p
                    LEFT JOIN comunidades c ON p.id_comunidade = c.id
                    {$where_clause}"; 
                    
$count_bind_types = substr($bind_types, 0, -2); 
$count_bind_params = array_slice($bind_params, 0, -2); 


$stmt_count = mysqli_prepare($conn, $sql_count_posts);
$total_posts = 0;

if ($stmt_count) {
    if (!empty($count_bind_types)) {
        $count_bind_refs = array($count_bind_types);
        for ($i = 0; $i < count($count_bind_params); $i++) {
            $count_bind_refs[] = &$count_bind_params[$i];
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt_count), $count_bind_refs));
    }
    
    mysqli_stmt_execute($stmt_count);
    mysqli_stmt_bind_result($stmt_count, $total_posts);
    mysqli_stmt_fetch($stmt_count);
    mysqli_stmt_close($stmt_count);
}
$total_pages = ceil($total_posts / $posts_per_page);


// --- 6. FUNÇÕES AUXILIARES ---

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;

    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "agora mesmo";
    } elseif ($minutes <= 60) {
        return $minutes == 1 ? "há 1 minuto" : "há {$minutes} minutos";
    } elseif ($hours <= 24) {
        return $hours == 1 ? "há 1 hora" : "há {$hours} horas";
    } elseif ($days <= 7) {
        return $days == 1 ? "ontem" : "há {$days} dias";
    } elseif ($weeks <= 4.3) {
        return $weeks == 1 ? "há 1 semana" : "há {$weeks} semanas";
    } elseif ($months <= 12) {
        return $months == 1 ? "há 1 mês" : "há {$months} meses";
    } else {
        return $years == 1 ? "há 1 ano" : "há {$years} anos";
    }
}

function check_if_user_liked($conn, $postId, $userId) {
    // CORREÇÃO: Tabela 'curtidas' trocada para 'curtidas_comunidade'
    $sql = "SELECT COUNT(*) FROM curtidas_comunidade WHERE id_postagem = ? AND id_usuario = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $postId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $count > 0;
    }
    return false;
}

function display_post_card($post) {
    global $conn, $userId;

    $is_liked = check_if_user_liked($conn, $post['id'], $userId);
    $like_class = $is_liked ? 'liked' : '';
    $like_text = $is_liked ? 'Curtido' : 'Curtir';
    $time_ago = time_ago($post['data_criacao']);
    $post_id = $post['id'];
    $comunidade_html = '';

    if (!empty($post['nome_comunidade'])) {
        $comunidade_html = "<span class='post-community-link' data-comunidade-id='{$post['comunidade_id']}'>em <a href='comunidade.php?id={$post['comunidade_id']}'>{$post['nome_comunidade']}</a></span>";
    }

    $card_html = "<div class='card post-card' data-post-id='{$post_id}'>
        <div class='post-header'>
            <img src='imagens/default.png' alt='Avatar' class='post-avatar'>
            <div class='post-info'>
                <span class='post-author'>{$post['apelido']}</span>
                {$comunidade_html}
                <span class='post-time'>{$time_ago}</span>
            </div>
        </div>
        <div class='post-content'>
            <p>{$post['conteudo']}</p>";

    if ($post['imagem']) {
        $card_html .= "<img src='{$post['imagem']}' alt='Imagem do post' class='post-image'>";
    }

    $card_html .= "</div>
        <div class='post-actions'>
            <button class='btn-action btn-like {$like_class}' data-post-id='{$post_id}'>
                <i class='fa-solid fa-thumbs-up'></i> <span class='like-text'>{$like_text}</span> (<span class='like-count'>{$post['likes_count']}</span>)
            </button>
            <button class='btn-action btn-comment' data-post-id='{$post_id}'>
                <i class='fa-solid fa-comment'></i> Comentários (<span class='comment-count'>{$post['comments_count']}</span>)
            </button>
        </div>
        <div class='comments-section' id='comments-{$post_id}'>
            <div class='comments-list' id='comments-list-{$post_id}'>";

    // Buscar Comentários para o Post
    // CORREÇÃO: Tabela 'comentarios' trocada para 'comentarios_comunidade'
    $sql_comments = "SELECT c.conteudo, c.data_criacao, u.apelido 
                     FROM comentarios_comunidade c 
                     JOIN usuarios u ON c.id_usuario = u.id 
                     WHERE c.id_postagem = ? 
                     ORDER BY c.data_criacao ASC";
    $stmt_comments = mysqli_prepare($conn, $sql_comments);

    $comment_count = 0;
    if ($stmt_comments) {
        mysqli_stmt_bind_param($stmt_comments, "i", $post_id);
        mysqli_stmt_execute($stmt_comments);
        $result_comments = mysqli_stmt_get_result($stmt_comments);
        
        while ($comment = mysqli_fetch_assoc($result_comments)) {
            $comment_count++;
            $card_html .= "
                <div class='comment-item'>
                    <div class='comment-header'>
                        <span class='comment-author'>{$comment['apelido']}</span>
                        <span class='comment-time'>" . time_ago($comment['data_criacao']) . "</span>
                    </div>
                    <p class='comment-content'>{$comment['conteudo']}</p>
                </div>";
        }
        mysqli_stmt_close($stmt_comments);
    }

    if ($comment_count === 0) {
        $card_html .= "<div class='no-comments-message' id='no-comments-message-{$post_id}'>Nenhum comentário ainda.</div>";
    }


    $card_html .= "</div>
            <div class='new-comment-form'>
                <input type='text' class='comment-input' data-post-id='{$post_id}' placeholder='Escreva um comentário...'>
                <button class='btn-submit-comment' data-post-id='{$post_id}'>Enviar</button>
            </div>
        </div>
    </div>";

    return $card_html;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeuroBlogs - Feed</title>
    <link rel="stylesheet" href="homePage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            background-color: <?php echo htmlspecialchars($prefs['cor_fundo_pref']); ?>;
            color: <?php echo htmlspecialchars($prefs['cor_texto_pref']); ?>;
            font-size: <?php echo htmlspecialchars($prefs['tamanho_fonte_pref']); ?>;
            font-family: <?php echo htmlspecialchars($prefs['fonte_preferida']); ?>;
        }
        .card, .navigation {
             background-color: #ffffff;
        }
        .post-author, .post-community-link a {
            color: <?php echo htmlspecialchars($prefs['cor_texto_pref']); ?>;
        }
        .post-content p, .comments-list .comment-content {
            color: <?php echo htmlspecialchars($prefs['cor_texto_pref']); ?>;
        }
        /* Novo estilo para o seletor de comunidade */
        .community-select-container {
            margin-bottom: 15px;
        }
        .community-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }
    </style>
</head>
<body>

    <main class="feed-main-col">
        
        <div class="card post-form-card">
            <form action="homePage.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="post">
                
                <div class="community-select-container">
                    <select name="id_comunidade" class="community-select" required>
                        <option value="">Selecione a Comunidade para Postar *</option>
                        <?php if (!empty($user_communities)): ?>
                            <?php foreach ($user_communities as $comm): ?>
                                <option value="<?php echo $comm['id']; ?>">
                                    <?php echo htmlspecialchars($comm['nome_comunidade']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Você não é membro de nenhuma comunidade.</option>
                        <?php endif; ?>
                    </select>
                </div>
                <textarea name="conteudo" class="post-text-area" placeholder="O que você está pensando? (Apenas para posts de comunidade)"></textarea>
                
                <div class="post-image-preview-wrapper">
                    </div>

                <div class="post-form-actions">
                    <label for="imagem_post" class="btn-file-upload">
                        <i class="fa-solid fa-image"></i> Imagem
                        <input type="file" name="imagem_post" id="imagem_post" accept="image/*" style="display: none;">
                    </label>
                    <button type="submit" class="btn-primary" <?php echo empty($user_communities) ? 'disabled' : ''; ?>>Publicar</button>
                </div>
            </form>
        </div>
        
        <div class="filter-controls">
            <a href="homePage.php?view=all" class="btn-filter <?php echo ($view_mode == 'all' ? 'active' : ''); ?>">Todas as Comunidades</a>
            <a href="homePage.php?view=following" class="btn-filter <?php echo ($view_mode == 'following' ? 'active' : ''); ?>">Comunidades Seguidas</a>
        </div>

        <section class="feed-container">
            <h2 class="feed-section-title">
                <?php 
                    if ($view_mode == 'following') {
                        echo "Comunidades que Você Segue";
                    } else {
                        echo "Todas as Comunidades (Feed Principal)";
                    }
                ?>
            </h2>

            <?php 
            $post_count = 0;
            if ($result_posts && mysqli_num_rows($result_posts) > 0) {
                while ($post = mysqli_fetch_assoc($result_posts)) {
                    echo display_post_card($post);
                    $post_count++;
                }
            }
            
            // Se não houver posts
            if ($post_count == 0): ?>
                <div class='no-posts-message card'>
                    <?php if ($view_mode == 'following'): ?>
                        Você não segue nenhuma comunidade ou elas ainda não postaram.
                    <?php else: ?>
                        Nenhuma postagem de comunidade encontrada com os filtros atuais.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo htmlspecialchars($current_page_url . "&page=" . ($page - 1)); ?>" class="btn-page">Anterior</a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo htmlspecialchars($current_page_url . "&page=" . ($page + 1)); ?>" class="btn-page">Próxima</a>
                <?php endif; ?>
            </div>
            
        </section>
        
    </main>
    
    <aside class="sidebar-right">
        <div class="card sidebar-block">
            <h3>Sugestões de Comunidades</h3>
            <p>Em desenvolvimento...</p>
        </div>
        <div class="card sidebar-block">
            <h3>Tendências</h3>
            <p>Em desenvolvimento...</p>
        </div>
    </aside>

</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. Lógica de Curtir (Like)
            document.querySelectorAll('.btn-like').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const isLiked = this.classList.contains('liked');
                    const action = 'like';
                    const buttonElement = this;
                    const likeCountElement = buttonElement.querySelector('.like-count');
                    const likeTextElement = buttonElement.querySelector('.like-text');

                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('post_id', postId);

                    fetch('homePage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.liked) {
                                buttonElement.classList.add('liked');
                                likeTextElement.textContent = 'Curtido';
                            } else {
                                buttonElement.classList.remove('liked');
                                likeTextElement.textContent = 'Curtir';
                            }
                            likeCountElement.textContent = data.new_count;
                        } else {
                            alert('Erro ao processar o like. Tente novamente.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX:', error);
                        alert('Erro de conexão ao processar o like.');
                    });
                });
            });

            // 2. Lógica de Comentar (Submit)
            document.querySelectorAll('.btn-submit-comment').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentInput = document.querySelector(`.comment-input[data-post-id='${postId}']`);
                    const commentText = commentInput.value.trim();

                    if (commentText === "") return;

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

            // 3. Lógica para mostrar/esconder a seção de comentários
            document.querySelectorAll('.btn-comment').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentsSection = document.getElementById(`comments-${postId}`);
                    commentsSection.classList.toggle('active'); 
                });
            });
            
            // 4. Lógica para previsualizar a imagem antes do upload
            document.getElementById('imagem_post').addEventListener('change', function(e) {
                const formCard = document.querySelector('.post-form-card');
                let previewContainer = document.querySelector('.post-image-preview-wrapper');
                
                // Remove a pré-visualização anterior, se houver
                if(previewContainer) previewContainer.remove();
                
                const file = e.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Cria a nova estrutura
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'post-image-preview-wrapper';
                        
                        const previewImage = document.createElement('img');
                        previewImage.src = e.target.result;
                        previewImage.alt = 'Pré-visualização da imagem';
                        previewImage.className = 'post-image-preview';
                        
                        previewContainer.appendChild(previewImage);
                        
                        // Insere a pré-visualização após a textarea
                        document.querySelector('.post-text-area').insertAdjacentElement('afterend', previewContainer);
                    };
                    reader.readAsDataURL(file);
                }
            });

        });
    </script>
</body>
</html>