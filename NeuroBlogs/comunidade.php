<?php
// PHP - Arquivo: comunidade.php (Página Individual da Comunidade e Feed)
session_start();
include "conexao.php"; 

// CORREÇÃO 1: Definição segura de $userId e $userName
$userId = $_SESSION['usuario_id'] ?? 0; 
$userName = $_SESSION['usuario'] ?? '';

// O redirecionamento verifica o valor seguro
if ($userId === 0) {
    header("Location: login.php");
    exit;
}
$comunidadeId = $_GET['id'] ?? 0;

if ($comunidadeId == 0) {
    header("Location: comunidades.php");
    exit;
}

// CORREÇÃO 2: Função resizeImage incluída para garantir funcionalidade de upload
// Esta função é crítica para otimizar as imagens antes de salvar no servidor
if (!function_exists('resizeImage')) {
    function resizeImage($file, $maxWidth = 800, $maxHeight = 600) {
        list($width, $height) = getimagesize($file);
        $ratio = $width / $height;
        if ($maxWidth / $maxHeight > $ratio) {
            $maxWidth = $maxHeight * $ratio;
        } else {
            $maxHeight = $maxWidth / $ratio;
        }
        
        $src = imagecreatefromstring(file_get_contents($file));
        $dst = imagecreatetruecolor($maxWidth, $maxHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $maxHeight, $width, $height);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'img');
        imagejpeg($dst, $tempFile, 80); // 80 é a qualidade
        return $tempFile;
    }
}

// --- FUNÇÃO time_ago para datas ---
if (!function_exists('time_ago')) {
    function time_ago($timestamp) {
        date_default_timezone_set('America/Sao_Paulo');
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
        
        if ($seconds <= 60) return "agora";
        else if ($minutes <= 60) return ($minutes == 1) ? "há 1 minuto" : "há {$minutes} minutos";
        else if ($hours <= 24) return ($hours == 1) ? "há 1 hora" : "há {$hours} horas";
        else if ($days <= 7) return ($days == 1) ? "há 1 dia" : "há {$days} dias";
        else if ($weeks <= 4.3) return ($weeks == 1) ? "há 1 semana" : "há {$weeks} semanas";
        else if ($months <= 12) return ($months == 1) ? "há 1 mês" : "há {$months} meses";
        else return ($years == 1) ? "há 1 ano" : "há {$years} anos";
    }
}

// --- LÓGICA DE POSTAGEM DE IMAGEM E TEXTO (Formulário) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_community'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $imagem_post = null;
    $error = false;

    if (empty($conteudo) && empty($_FILES['imagem_post_comunidade']['name'])) {
        $error = true; // Não permite post vazio
    }

    if (!$error && isset($_FILES['imagem_post_comunidade']) && $_FILES['imagem_post_comunidade']['error'] == 0) {
        $file = $_FILES['imagem_post_comunidade'];
        $uploadDir = 'uploads/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $error = true;
            $postMessage = "Erro: Apenas JPEG, PNG e GIF são permitidos.";
        }

        if (!$error) {
            // Redimensiona a imagem para otimização
            $temp_file = resizeImage($file['tmp_name']);
            
            if ($temp_file) {
                $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uniqueName = 'post_' . $userId . '_' . time() . '.' . $fileExt;
                $target_file = $uploadDir . $uniqueName;

                if (move_uploaded_file($temp_file, $target_file)) {
                    $imagem_post = $target_file;
                } else {
                    $error = true;
                    $postMessage = "Erro ao mover o arquivo redimensionado.";
                }
                unlink($temp_file); // Limpa o arquivo temporário
            } else {
                // Caso não consiga redimensionar, tenta mover o original (menos otimizado)
                $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uniqueName = 'post_' . $userId . '_' . time() . '.' . $fileExt;
                $target_file = $uploadDir . $uniqueName;
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $imagem_post = $target_file;
                } else {
                    $error = true;
                    $postMessage = "Erro ao mover o arquivo original.";
                }
            }
        }
    }

    if (!$error) {
        // Tabela de posts GERAL (posts_comunidade)
        $sql_insert = "INSERT INTO posts_comunidade (id_comunidade, usuario_id, titulo, conteudo, imagem) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql_insert);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisss", $comunidadeId, $userId, $titulo, $conteudo, $imagem_post);
            if (mysqli_stmt_execute($stmt)) {
                // Postagem bem-sucedida
                $postMessage = "Postagem realizada com sucesso!";
            } else {
                $postMessage = "Erro ao inserir postagem no banco de dados: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $postMessage = "Erro de preparação da query: " . mysqli_error($conn);
        }
    } else if (empty($postMessage)) {
        $postMessage = "O conteúdo ou a imagem da postagem são obrigatórios.";
    }
}

// --- LÓGICA DE COMENTÁRIO (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_community'])) {
    $postId = intval($_POST['post_id']);
    $commentText = trim($_POST['comment_text']);

    if ($postId > 0 && !empty($commentText)) {
        $sql = "INSERT INTO comentarios_comunidade (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $postId, $userId, $commentText);
            if (mysqli_stmt_execute($stmt)) {
                // Buscar o comentário recém-inserido para retornar via AJAX
                $newCommentId = mysqli_insert_id($conn);
                $sql_fetch_new = "
                    SELECT c.id, c.conteudo, c.data_criacao, u.apelido, pu.foto_perfil
                    FROM comentarios_comunidade c
                    JOIN usuarios u ON c.id_usuario = u.id
                    LEFT JOIN perfil_usuario pu ON u.id = pu.id
                    WHERE c.id = ?
                ";
                $stmt_fetch_new = mysqli_prepare($conn, $sql_fetch_new);
                mysqli_stmt_bind_param($stmt_fetch_new, "i", $newCommentId);
                mysqli_stmt_execute($stmt_fetch_new);
                $result_new = mysqli_stmt_get_result($stmt_fetch_new);
                $newComment = mysqli_fetch_assoc($result_new);

                if ($newComment) {
                    $commentHTML = "
                        <div class='comment-item'>
                            <img src='" . ($newComment['foto_perfil'] ?? 'uploads/perfil/default.png') . "' alt='Avatar' class='comment-user-photo'>
                            <div class='comment-content'>
                                <span class='comment-user-name'>" . htmlspecialchars($newComment['apelido']) . "</span>
                                <p>" . htmlspecialchars($newComment['conteudo']) . "</p>
                                <span class='comment-time'>" . time_ago($newComment['data_criacao']) . "</span>
                            </div>
                        </div>";
                    echo json_encode(['success' => true, 'new_comment_html' => $commentHTML]);
                } else {
                    echo json_encode(['success' => true, 'new_comment_html' => '<p>Comentário adicionado, mas falha ao carregar.</p>']);
                }
                mysqli_stmt_close($stmt_fetch_new);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário.']);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro de preparação da query.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Conteúdo do comentário inválido.']);
    }
    exit;
}

// --- LÓGICA DE CURTIR (AJAX/POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_community'])) {
    $postId = intval($_POST['post_id']);
    $action = $_POST['action'];

    if ($postId > 0) {
        if ($action == 'like') {
            $sql = "INSERT IGNORE INTO curtidas_comunidade (id_postagem, id_usuario) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $postId, $userId);
            mysqli_stmt_execute($stmt);
        } elseif ($action == 'unlike') {
            $sql = "DELETE FROM curtidas_comunidade WHERE id_postagem = ? AND id_usuario = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $postId, $userId);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);

        // Recarregar contagem de curtidas
        $sql_count = "SELECT COUNT(*) AS count FROM curtidas_comunidade WHERE id_postagem = ?";
        $stmt_count = mysqli_prepare($conn, $sql_count);
        mysqli_stmt_bind_param($stmt_count, "i", $postId);
        mysqli_stmt_execute($stmt_count);
        $result_count = mysqli_stmt_get_result($stmt_count);
        $count = mysqli_fetch_assoc($result_count)['count'];
        mysqli_stmt_close($stmt_count);

        // Checar status atual de curtida
        $sql_status = "SELECT COUNT(*) AS is_liked FROM curtidas_comunidade WHERE id_postagem = ? AND id_usuario = ?";
        $stmt_status = mysqli_prepare($conn, $sql_status);
        mysqli_stmt_bind_param($stmt_status, "ii", $postId, $userId);
        mysqli_stmt_execute($stmt_status);
        $result_status = mysqli_stmt_get_result($stmt_status);
        $is_liked = mysqli_fetch_assoc($result_status)['is_liked'] > 0;
        mysqli_stmt_close($stmt_status);

        echo json_encode(['success' => true, 'like_count' => $count, 'is_liked' => $is_liked]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Post ID inválido.']);
    }
    exit;
}

// ------------------------------------------------------------------------------------------------
// BUSCA DE DADOS DA COMUNIDADE
// ------------------------------------------------------------------------------------------------
// CORREÇÃO: Removendo 'c.imagem' pois a coluna não existe na sua tabela 'comunidades'.
// Se você quiser imagens para a comunidade, precisará adicionar uma coluna 'imagem' na tabela 'comunidades'.
$sql_select_community = "
    SELECT c.nome_comunidade, c.descricao, COUNT(m.id_usuario) AS total_membros
    FROM comunidades c
    LEFT JOIN membros_comunidade m ON c.id = m.id_comunidade
    WHERE c.id = ?
    GROUP BY c.id
";
$stmt_community = mysqli_prepare($conn, $sql_select_community);
// Linha 255 (aproximada no código original)
mysqli_stmt_bind_param($stmt_community, "i", $comunidadeId);
mysqli_stmt_execute($stmt_community);
$result_community = mysqli_stmt_get_result($stmt_community);
$community = mysqli_fetch_assoc($result_community);
mysqli_stmt_close($stmt_community);

if (!$community) {
    header("Location: comunidades.php");
    exit;
}

// Verifica se o usuário é membro
$isMember = false;
$sql_check_member = "SELECT 1 FROM membros_comunidade WHERE id_comunidade = ? AND id_usuario = ?";
$stmt_check = mysqli_prepare($conn, $sql_check_member);
mysqli_stmt_bind_param($stmt_check, "ii", $comunidadeId, $userId);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    $isMember = true;
}
mysqli_stmt_close($stmt_check);

// ------------------------------------------------------------------------------------------------
// BUSCA DOS POSTS DA COMUNIDADE
// ------------------------------------------------------------------------------------------------
$sql_select_posts = "
    SELECT 
        p.id, p.titulo, p.conteudo, p.imagem, p.data_criacao,
        u.apelido, 
        pu.foto_perfil, 
        (SELECT COUNT(*) FROM curtidas_comunidade cc WHERE cc.id_postagem = p.id) AS like_count,
        (SELECT COUNT(*) FROM curtidas_comunidade cc WHERE cc.id_postagem = p.id AND cc.id_usuario = ?) AS is_liked
    FROM posts_comunidade p
    JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN perfil_usuario pu ON u.id = pu.id
    WHERE p.id_comunidade = ?
    ORDER BY p.data_criacao DESC
";
$stmt_posts = mysqli_prepare($conn, $sql_select_posts);
mysqli_stmt_bind_param($stmt_posts, "ii", $userId, $comunidadeId);
mysqli_stmt_execute($stmt_posts);
$result_posts = mysqli_stmt_get_result($stmt_posts);

// ------------------------------------------------------------------------------------------------
// BUSCA DE COMENTÁRIOS (Será feita dentro do loop de posts ou via AJAX)
// ------------------------------------------------------------------------------------------------
function fetch_comments($conn, $postId) {
    $sql_comments = "
        SELECT c.conteudo, c.data_criacao, u.apelido, pu.foto_perfil
        FROM comentarios_comunidade c
        JOIN usuarios u ON c.id_usuario = u.id
        LEFT JOIN perfil_usuario pu ON u.id = pu.id
        WHERE c.id_postagem = ?
        ORDER BY c.data_criacao ASC
    ";
    $stmt_comments = mysqli_prepare($conn, $sql_comments);
    mysqli_stmt_bind_param($stmt_comments, "i", $postId);
    mysqli_stmt_execute($stmt_comments);
    $result_comments = mysqli_stmt_get_result($stmt_comments);
    return mysqli_fetch_all($result_comments, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['nome_comunidade']); ?> | NeuroBlogs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="homePage.css"> <style>
        /* Estilos específicos para a página da Comunidade */
        
        /* CORREÇÃO 3: Ajustando o container principal */
        .main-content-single {
            margin-left: 5rem; /* Espaço para a navbar (se estiver usando 5rem - 80px) */
            padding: 20px;
            max-width: 900px;
            margin-right: auto;
            margin-left: auto;
        }

        /* HEADER DA COMUNIDADE */
        .community-header {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: flex-start;
        }

        .community-image-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 25px;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .community-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .community-info {
            flex-grow: 1;
        }

        .community-header h1 {
            font-size: 2.2rem;
            color: #1e3c72;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .community-header p {
            color: #555;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .community-meta {
            font-size: 0.9rem;
            color: #888;
        }
        
        .community-meta .member-count {
            font-weight: 600;
            color: #2879e4;
        }

        /* -------------------------------------- */
        /* POSTAGEM DE NOVOS TÓPICOS */
        /* -------------------------------------- */
        .new-post-area {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }

        .new-post-area h4 {
            color: #1e3c72;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .post-text-area {
            resize: none;
            height: 100px;
        }

        /* Estilo para a pré-visualização da imagem no formulário de postagem */
        .post-image-preview-wrapper {
            margin-top: 15px;
            border: 1px dashed #ccc;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .post-image-preview {
            max-width: 100%;
            max-height: 250px;
            display: block;
            margin: 0 auto;
            border-radius: 6px;
        }
        
        /* -------------------------------------- */
        /* 📌 ESTILOS DE POSTAGEM NA COMUNIDADE (AJUSTADOS) */
        /* -------------------------------------- */

        .post-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #2879e4; /* Borda lateral azul para destacar */
            margin-bottom: 25px; /* Adicionando margem para separar os posts */
        }

        /* 1. Ajuste do Avatar (Foto do Usuário) */
        .post-user-photo {
            width: 40px; /* Reduzido */
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 15px;
        }

        .post-info {
            display: flex;
            flex-direction: column;
        }

        .post-user-name {
            font-weight: 600;
            color: #1e3c72;
            font-size: 0.95rem;
        }

        .post-time, .post-community {
            font-size: 0.8rem;
            color: #888;
        }
        
        .post-content h3 {
            font-size: 1.2rem;
            color: #1e3c72;
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* 2. Estilo para a Imagem da Postagem */
        .post-image-wrapper {
            max-height: 400px;
            overflow: hidden;
            border-radius: 6px;
            margin-top: 15px;
            border: 1px solid #eee;
        }

        .post-image {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        /* ESTILOS DE BOTÕES E COMENTÁRIOS */
        .post-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 15px;
        }

        .btn-like, .btn-comment {
            background: none;
            border: none;
            color: #6c757d;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-like:hover { color: #dc3545; }
        .btn-comment:hover { color: #007bff; }

        .btn-like i, .btn-comment i {
            margin-right: 8px;
        }

        .liked {
            color: #dc3545 !important;
        }

        /* COMENTÁRIOS */
        .comments-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
        .comments-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .comment-item {
            display: flex;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            background-color: #fff;
            border: 1px solid #eee;
        }
        .comment-user-photo {
            width: 30px; /* Reduzido */
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        .comment-content {
            flex-grow: 1;
        }
        .comment-user-name {
            font-weight: 600;
            color: #1e3c72;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        .comment-time {
            font-size: 0.75rem;
            color: #888;
            display: block;
            margin-top: 2px;
        }
        .comment-content p {
            margin: 0;
            font-size: 0.9rem;
        }
        .comment-form-container {
            padding-top: 10px;
            border-top: 1px solid #eee;
            margin-top: 10px;
        }

        /* BOTÃO DE ENTRAR/SAIR */
        .btn-join {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-leave {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-join:hover { background-color: #218838; }
        .btn-leave:hover { background-color: #c82333; }
    </style>
</head>
<body>

    <?php 
    // Por favor, inclua seu arquivo de navegação aqui.
    // Se o nome for 'navbar.php', descomente a linha. Se for outro nome, use o correto.
    // include "navbar.php"; 
    ?> 

    <main class="main-content-single">
        
        <div class="community-header">
            <div class="community-image-container">
                <img src="<?php echo htmlspecialchars($community['imagem'] ?? 'uploads/comunidade/default.png'); ?>" 
                     alt="Imagem da Comunidade" class="community-image">
            </div>
            <div class="community-info">
                <h1><?php echo htmlspecialchars($community['nome_comunidade']); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($community['descricao'])); ?></p>
                <div class="community-meta">
                    <span class="member-count"><?php echo $community['total_membros']; ?> membros</span>
                    <?php if (!$isMember): ?>
                        <button class="btn-join ms-3" data-community-id="<?php echo $comunidadeId; ?>" data-action="join">
                            Entrar
                        </button>
                    <?php else: ?>
                        <button class="btn-leave ms-3" data-community-id="<?php echo $comunidadeId; ?>" data-action="leave">
                            Sair
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($isMember): ?>
            <div class="new-post-area">
                <h4>Novo Tópico na Comunidade</h4>
                <?php if (isset($postMessage)): ?>
                    <div class="alert alert-info"><?php echo $postMessage; ?></div>
                <?php endif; ?>
                <form id="postCommunityForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="post_community" value="1">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="titulo" placeholder="Título (Opcional)">
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control post-text-area" name="conteudo" rows="3" placeholder="O que você está pensando?" required></textarea>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="imagem_post_comunidade" class="form-label">Adicionar Imagem (Opcional)</label>
                        <input class="form-control" type="file" id="imagem_post_comunidade" name="imagem_post_comunidade" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Postar</button>
                </form>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 20px; margin-bottom: 20px;">
                <p>Você precisa ser membro para visualizar e participar do feed desta comunidade.</p>
                <button class="btn-join" data-community-id="<?php echo $comunidadeId; ?>" data-action="join">
                    Entrar na Comunidade
                </button>
            </div>
        <?php endif; ?>


        <div class="posts-feed">
            <?php if (mysqli_num_rows($result_posts) > 0): ?>
                <?php while ($post = mysqli_fetch_assoc($result_posts)): 
                    $post_id = $post['id'];
                    $is_liked = $post['is_liked'] > 0;
                    $like_count = $post['like_count'];
                    $comments = fetch_comments($conn, $post_id);
                ?>
                    <div class="post-card" data-post-id="<?php echo $post_id; ?>">
                        <div class="post-header">
                            <img src="<?php echo $post['foto_perfil'] ?? 'uploads/perfil/default.png'; ?>" alt="Foto de Perfil" class="post-user-photo">
                            <div class="post-info">
                                <span class="post-user-name"><?php echo htmlspecialchars($post['apelido']); ?></span>
                                <span class="post-time"><?php echo time_ago($post['data_criacao']); ?></span>
                            </div>
                        </div>
                        <div class="post-content">
                            <?php if (!empty($post['titulo'])): ?>
                                <h3><?php echo htmlspecialchars($post['titulo']); ?></h3>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>

                            <?php if (!empty($post['imagem'])): ?>
                                <div class="post-image-wrapper">
                                    <img src="<?php echo htmlspecialchars($post['imagem']); ?>" alt="Imagem da Postagem" class="post-image">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-actions">
                            <button class='btn-like <?php echo $is_liked ? "liked" : ""; ?>' data-post-id='<?php echo $post_id; ?>' data-action='<?php echo $is_liked ? "unlike" : "like"; ?>'>
                                <i class='fas fa-heart'></i> <span class='like-count'><?php echo $like_count; ?></span> Curtidas
                            </button>
                            <button class='btn-comment' data-post-id='<?php echo $post_id; ?>' data-type='community' data-bs-toggle='collapse' data-bs-target='#comments-section-<?php echo $post_id; ?>' aria-expanded='false'>
                                <i class='fas fa-comment'></i> Comentários
                            </button>
                        </div>

                        <div class="collapse comments-section" id="comments-section-<?php echo $post_id; ?>">
                            <div class="comments-list" id="comments-list-<?php echo $post_id; ?>">
                                <?php if (!empty($comments)): ?>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class='comment-item'>
                                            <img src="<?php echo $comment['foto_perfil'] ?? 'uploads/perfil/default.png'; ?>" alt='Avatar' class='comment-user-photo'>
                                            <div class='comment-content'>
                                                <span class='comment-user-name'><?php echo htmlspecialchars($comment['apelido']); ?></span>
                                                <p><?php echo htmlspecialchars($comment['conteudo']); ?></p>
                                                <span class='comment-time'><?php echo time_ago($comment['data_criacao']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p id="no-comments-message-<?php echo $post_id; ?>">Nenhum comentário ainda.</p>
                                <?php endif; ?>
                            </div>

                            <?php if ($isMember): // Só permite comentar se for membro ?>
                            <div class="comment-form-container">
                                <form class="comment-form d-flex align-items-center" data-post-id="<?php echo $post_id; ?>">
                                    <input type="text" class="form-control me-2 comment-input" placeholder="Escreva um comentário..." required>
                                    <button type="submit" class="btn btn-sm btn-primary">Enviar</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 20px;">
                    <p>Ainda não há postagens nesta comunidade.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userId = <?php echo $userId; ?>;

            // --- LÓGICA DE ENTRAR/SAIR DA COMUNIDADE ---
            document.querySelectorAll('.btn-join, .btn-leave').forEach(button => {
                button.addEventListener('click', function() {
                    const communityId = this.getAttribute('data-community-id');
                    const action = this.getAttribute('data-action');
                    const buttonElement = this;

                    fetch('comunidades.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=${action}&community_id=${communityId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Recarrega a página para atualizar o estado do feed e dos botões
                            window.location.reload(); 
                        } else {
                            alert('Erro ao processar a ação. Tente novamente.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX:', error);
                        alert('Erro de conexão ao processar a ação.');
                    });
                });
            });


            // --- LÓGICA DE CURTIR/DESCURTIR (AJAX) ---
            document.querySelectorAll('.btn-like').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    let action = this.getAttribute('data-action');
                    const likeCountSpan = this.querySelector('.like-count');

                    fetch('comunidade.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `like_community=1&post_id=${postId}&action=${action}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            likeCountSpan.textContent = data.like_count;
                            if (data.is_liked) {
                                button.classList.add('liked');
                                button.setAttribute('data-action', 'unlike');
                            } else {
                                button.classList.remove('liked');
                                button.setAttribute('data-action', 'like');
                            }
                        } else {
                            alert('Erro ao processar a curtida. Tente novamente.');
                        }
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX na curtida:', error);
                        alert('Erro de conexão ao curtir.');
                    });
                });
            });

            // --- LÓGICA DE COMENTÁRIOS (AJAX) ---
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.getAttribute('data-post-id');
                    const commentInput = this.querySelector('.comment-input');
                    const commentText = commentInput.value.trim();

                    if (!commentText) return;

                    const formData = new FormData();
                    formData.append('comment_community', '1');
                    formData.append('post_id', postId);
                    formData.append('comment_text', commentText);

                    fetch('comunidade.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const commentsList = document.getElementById(`comments-list-${postId}`);
                            const noCommentsMessage = document.getElementById(`no-comments-message-${postId}`);
                            
                            // Remove a mensagem 'Nenhum comentário ainda.' se ela existir
                            if (noCommentsMessage) {
                                noCommentsMessage.remove();
                            }
                            
                            commentsList.insertAdjacentHTML('beforeend', data.new_comment_html);
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

            // --- PRÉ-VISUALIZAÇÃO DA IMAGEM NO FORMULÁRIO DE POSTAGEM ---
            const imgInput = document.getElementById('imagem_post_comunidade');
            if(imgInput) {
                imgInput.addEventListener('change', function(e) {
                    let previewContainer = document.querySelector('.post-image-preview-wrapper');
                    
                    // Remove a pré-visualização anterior
                    if(previewContainer) previewContainer.remove();
                    
                    const file = e.target.files[0];
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewContainer = document.createElement('div');
                            previewContainer.className = 'post-image-preview-wrapper';
                            
                            const previewImage = document.createElement('img');
                            previewImage.src = e.target.result;
                            previewImage.alt = 'Pré-visualização da imagem';
                            previewImage.className = 'post-image-preview';
                            
                            previewContainer.appendChild(previewImage);
                            
                            // Insere a pré-visualização após o campo de input file
                            document.querySelector('#postCommunityForm .form-group').insertAdjacentElement('afterend', previewContainer);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>