<?php
session_start();

// Define o fuso horário para o de São Paulo (UTC-3)
// Isso garante que todas as datas e horas manipuladas pelo PHP
// sejam exibidas corretamente no seu fuso horário local.
date_default_timezone_set('America/Sao_Paulo');

include "conexao.php";

if (!isset($conn) || $conn->connect_error) {
    die("Erro fatal: A conexão com o banco de dados não pôde ser estabelecida. Verifique o arquivo 'conexao.php' e as credenciais. Erro: " . (isset($conn) ? $conn->connect_error : 'Variável $conn não definida.'));
}

// Verifica se a extensão GD está instalada e ativada
if (!extension_loaded('gd') || !function_exists('gd_info')) {
    die("Erro fatal: A biblioteca GD para processamento de imagens não está instalada ou ativada no seu servidor PHP.");
}

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$userName = $_SESSION["usuario"];
$userId = $_SESSION['usuario_id'];

// --- Função para Redimensionar e Otimizar Imagens ---
function resizeImage($file, $maxWidth = 800, $maxHeight = 600) {
    $info = getimagesize($file);
    if ($info === false) {
        return false;
    }
    list($originalWidth, $originalHeight) = $info;
    $mime = $info['mime'];

    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return true;
    }

    $ratio = $originalWidth / $originalHeight;
    $newWidth = $originalWidth;
    $newHeight = $originalHeight;

    if ($originalWidth > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $ratio;
    }

    if ($newHeight > $maxHeight) {
        $newHeight = $maxHeight;
        $newWidth = $maxHeight * $ratio;
    }

    $image_p = imagecreatetruecolor($newWidth, $newHeight);
    if ($image_p === false) {
        return false;
    }

    $image = null;
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file);
            imagealphablending($image_p, false);
            imagesavealpha($image_p, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file);
            break;
        default:
            return false;
    }

    if ($image === false) {
        imagedestroy($image_p);
        return false;
    }

    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($image_p, $file, 85);
            break;
        case 'image/png':
            imagepng($image_p, $file);
            break;
        case 'image/gif':
            imagegif($image_p, $file);
            break;
        case 'image/webp':
            imagewebp($image_p, $file, 85);
            break;
    }

    imagedestroy($image);
    imagedestroy($image_p);
    return true;
}

// --- Lida com o envio de uma nova postagem via AJAX ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['post_text']) || isset($_FILES['post_image']))) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_post_html' => ''];

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $response['message'] = "Erro fatal: Não foi possível criar a pasta de uploads. Verifique as permissões do diretório.";
            echo json_encode($response);
            exit;
        }
    }

    $postText = trim($_POST['post_text'] ?? '');
    $postImage = null;

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileError = $_FILES['post_image']['error'];

        if ($fileError === UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileMimeType = mime_content_type($_FILES['post_image']['tmp_name']);
            $validMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (in_array($imageFileType, $validExtensions) && in_array($fileMimeType, $validMimeTypes)) {
                $fileName = uniqid('post_') . '.' . $imageFileType;
                $targetFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $targetFilePath)) {
                    if (resizeImage($targetFilePath, 800, 600)) {
                         $postImage = $targetFilePath;
                    } else {
                        $response['message'] = "Erro ao redimensionar a imagem. Verifique se a biblioteca GD está instalada e tente uma imagem diferente.";
                        unlink($targetFilePath);
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response['message'] = "Erro ao fazer upload da imagem. Verifique as permissões da pasta 'uploads'.";
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['message'] = "Apenas JPG, JPEG, PNG, GIF e WEBP são permitidos.";
                echo json_encode($response);
                exit;
            }
        } elseif ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
            $response['message'] = "O arquivo é muito grande. O limite de tamanho de upload foi excedido.";
            echo json_encode($response);
            exit;
        } else {
            $response['message'] = "Ocorreu um erro desconhecido durante o upload. Código do erro: " . $fileError;
            echo json_encode($response);
            exit;
        }
    }

    if (!empty($postText) || !empty($postImage)) {
        $stmt_insert = $conn->prepare("INSERT INTO postagens (usuario_id, conteudo, imagem) VALUES (?, ?, ?)");
        if ($stmt_insert === false) {
             $response['message'] = "Erro na preparação da consulta de inserção: " . $conn->error;
             echo json_encode($response);
             exit;
        }
        $stmt_insert->bind_param("iss", $userId, $postText, $postImage);
        
        if ($stmt_insert->execute()) {
            $newPostId = $conn->insert_id;
            
            ob_start();
            ?>
            <div class="post-card mb-6" data-post-id="<?php echo $newPostId; ?>">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fa-solid fa-user-circle text-2xl text-gray-500 mr-2"></i>
                        <div>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date("d/m/Y H:i"); ?></p>
                        </div>
                    </div>
                    <button type="button" onclick="showDeleteConfirmation('post', <?php echo $newPostId; ?>);" class="text-gray-500 hover:text-red-500 transition-colors duration-200" title="Excluir postagem">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <?php if (!empty($postText)): ?>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($postText); ?></p>
                <?php endif; ?>
                <?php if (!empty($postImage)): ?>
                    <div class="my-3">
                        <img src="<?php echo htmlspecialchars($postImage); ?>" alt="Imagem da postagem" class="rounded-lg max-w-full h-auto">
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-200">
                    <span onclick="toggleLike(<?php echo $newPostId; ?>)" class="flex items-center transition-colors duration-200 like-button">
                        <i id="like-icon-<?php echo $newPostId; ?>" class="fa-solid fa-heart mr-1 like-icon"></i>
                        <span id="like-count-<?php echo $newPostId; ?>">0</span>
                    </span>
                </div>
                <div class="comment-container">
                    <h6 class="font-semibold text-gray-700 mb-2">Comentários</h6>
                    <div id="comments-list-<?php echo $newPostId; ?>">
                        <p id="no-comments-message-<?php echo $newPostId; ?>" class="text-sm text-gray-500">Nenhum comentário ainda.</p>
                    </div>
                    <form class="comment-form mt-3" data-post-id="<?php echo $newPostId; ?>">
                        <input type="hidden" name="post_id" value="<?php echo $newPostId; ?>">
                        <input type="text" name="comment_text" placeholder="Adicionar um comentário..." required class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">Comentar</button>
                    </form>
                </div>
            </div>
            <?php
            $response['new_post_html'] = ob_get_clean();

            $response['success'] = true;
            $response['message'] = "Postagem criada com sucesso!";
        } else {
            $response['message'] = "Erro ao salvar a postagem: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    } else {
        $response['message'] = "A postagem não pode ser vazia.";
    }

    echo json_encode($response);
    exit;
}

// --- Lida com a criação de comentários (via AJAX) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_comment_html' => ''];
    $post_id = (int)$_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text)) {
        $stmt_comment = $conn->prepare("INSERT INTO comentarios (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)");
        if ($stmt_comment === false) {
             $response['message'] = "Erro na preparação da consulta de inserção de comentário: " . $conn->error;
             echo json_encode($response);
             exit;
        }
        $stmt_comment->bind_param("iis", $post_id, $userId, $comment_text);
        
        if ($stmt_comment->execute()) {
            $newCommentId = $conn->insert_id;

            ob_start();
            ?>
            <div class="comment flex justify-between items-center" data-comment-id="<?php echo $newCommentId; ?>">
                <div class="flex-grow">
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?>:</span>
                    <?php echo htmlspecialchars($comment_text); ?>
                    <p class="text-xs text-gray-400"><?php echo date("d/m/Y", strtotime('now')); ?></p>
                </div>
                <button type="button" onclick="showDeleteConfirmation('comment', <?php echo $newCommentId; ?>);" class="text-gray-400 hover:text-red-500 transition-colors duration-200 ml-2" title="Excluir comentário">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            <?php
            $response['new_comment_html'] = ob_get_clean();
            $response['success'] = true;
            $response['message'] = "Comentário adicionado com sucesso!";
            $response['post_id'] = $post_id;
        } else {
            $response['message'] = "Erro ao adicionar comentário: " . $stmt_comment->error;
        }
        $stmt_comment->close();
    } else {
        $response['message'] = "O comentário não pode ser vazio.";
    }

    echo json_encode($response);
    exit;
}

// --- Lida com a funcionalidade de curtir/descurtir via AJAX ---
if (isset($_GET['action']) && $_GET['action'] == 'like' && isset($_GET['post_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'count' => 0, 'liked' => false];
    $postId = (int)$_GET['post_id'];

    if ($postId > 0) {
        $stmt_check = $conn->prepare("SELECT id FROM curtidas WHERE id_postagem = ? AND id_usuario = ?");
        $stmt_check->bind_param("ii", $postId, $userId);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // Se já curtiu, descurte
            $stmt_delete = $conn->prepare("DELETE FROM curtidas WHERE id_postagem = ? AND id_usuario = ?");
            $stmt_delete->bind_param("ii", $postId, $userId);
            if ($stmt_delete->execute()) {
                $response['success'] = true;
                $response['liked'] = false;
            } else {
                $response['message'] = "Erro ao descurtir a postagem.";
            }
            $stmt_delete->close();
        } else {
            // Se não curtiu, curte
            $stmt_insert = $conn->prepare("INSERT INTO curtidas (id_postagem, id_usuario) VALUES (?, ?)");
            $stmt_insert->bind_param("ii", $postId, $userId);
            if ($stmt_insert->execute()) {
                $response['success'] = true;
                $response['liked'] = true;
            } else {
                $response['message'] = "Erro ao curtir a postagem.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();

        // Obtém a contagem de curtidas atualizada
        $stmt_count = $conn->prepare("SELECT COUNT(*) AS total_likes FROM curtidas WHERE id_postagem = ?");
        $stmt_count->bind_param("i", $postId);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result()->fetch_assoc();
        $response['count'] = $result_count['total_likes'];
        $stmt_count->close();
    } else {
        $response['message'] = "ID da postagem inválido.";
    }
    
    echo json_encode($response);
    exit;
}

// --- Lógica para excluir um post (via AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_post' && isset($_GET['post_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    $postIdToDelete = (int)$_GET['post_id'];
    
    $stmt_check_owner = $conn->prepare("SELECT usuario_id, imagem FROM postagens WHERE id = ?");
    $stmt_check_owner->bind_param("i", $postIdToDelete);
    $stmt_check_owner->execute();
    $result_owner = $stmt_check_owner->get_result();
    $post_owner = $result_owner->fetch_assoc();
    $stmt_check_owner->close();
    
    if ($post_owner && $post_owner['usuario_id'] == $userId) {
        if (!empty($post_owner['imagem']) && file_exists($post_owner['imagem'])) {
            unlink($post_owner['imagem']);
        }
        
        $stmt_delete = $conn->prepare("DELETE FROM postagens WHERE id = ?");
        $stmt_delete->bind_param("i", $postIdToDelete);
        
        if ($stmt_delete->execute()) {
            $response['success'] = true;
            $response['message'] = "Postagem excluída com sucesso.";
        } else {
            $response['message'] = "Erro ao excluir a postagem: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $response['message'] = "Você não tem permissão para excluir esta postagem.";
    }

    echo json_encode($response);
    exit;
}

// --- Lógica para excluir um comentário (via AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_comment' && isset($_GET['comment_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    $commentIdToDelete = (int)$_GET['comment_id'];
    
    $stmt_check_owner = $conn->prepare("SELECT id_usuario FROM comentarios WHERE id = ?");
    $stmt_check_owner->bind_param("i", $commentIdToDelete);
    $stmt_check_owner->execute();
    $result_owner = $stmt_check_owner->get_result();
    $comment_owner = $result_owner->fetch_assoc();
    $stmt_check_owner->close();
    
    if ($comment_owner && $comment_owner['id_usuario'] == $userId) {
        $stmt_delete = $conn->prepare("DELETE FROM comentarios WHERE id = ?");
        $stmt_delete->bind_param("i", $commentIdToDelete);
        
        if ($stmt_delete->execute()) {
            $response['success'] = true;
            $response['message'] = "Comentário excluído com sucesso.";
        } else {
            $response['message'] = "Erro ao excluir o comentário: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $response['message'] = "Você não tem permissão para excluir este comentário.";
    }
    echo json_encode($response);
    exit;
}

$pagina = $_GET['pagina'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HomePage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #eef3f8; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .navigation ul li button {
            transition: all 0.3s ease;
        }
        .navigation ul li button:hover {
            transform: translateY(-2px);
        }

        .navigation ul li.active button {
            color: #1da1f2;
            background-color: rgba(29, 161, 242, 0.1);
        }
        .post-form input[type="file"] { cursor: pointer; }
        .clear-both { clear: both; }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center;
            z-index: 1000; opacity: 0; visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content {
            background-color: white; padding: 2rem; border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 400px; width: 90%; text-align: center;
            transform: translateY(-20px); transition: transform 0.3s ease;
        }
        .modal-overlay.show .modal-content { transform: translateY(0); }
        .message {
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .message.hide {
            opacity: 0;
        }
        .logout-button {
            margin-top: auto;
            margin-bottom: 1.5rem;
        }
        .logout-button button {
            background-color: #fef2f2;
            color: #ef4444;
            font-weight: 600;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 4rem;
            width: 11/12;
            border-radius: 0.75rem;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .logout-button button:hover {
            background-color: #fee2e2;
            color: #dc2626;
            transform: translateY(-2px);
        }
        .logout-button i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }
        .logout-button span {
            display: none;
        }
        @media (min-width: 768px) {
            .logout-button span {
                display: inline;
            }
            .navigation {
                width: 15rem;
            }
            .content-area {
                margin-left: 15rem;
            }
        }
        .post-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }
        .comment-container {
            border-top: 1px solid #eef3f8;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .comment {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }
        .comment .user-name {
            font-weight: 600;
            color: #2d3748;
        }
        .comment-form {
            display: flex;
            gap: 0.5rem;
        }
        .comment-form input[type="text"] {
            flex-grow: 1;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #cbd5e1;
        }
        .comment-form button {
            background-color: #4299e1;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
        }
        .like-button {
            cursor: pointer;
        }
        .like-icon {
            color: #4a5568;
            transition: color 0.2s ease-in-out;
        }
        .like-icon:hover {
            color: #4299e1;
        }
        .like-icon.liked {
            color: #ef4444 !important;
        }
        .image-preview-container {
            margin-top: 0.5rem;
            display: none;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .post-form .post-button:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="h-screen flex bg-[#eef3f8]">
    <nav class="navigation w-20 bg-white rounded-r-xl shadow-lg flex flex-col pt-6 fixed top-0 left-0 h-screen z-10">
        <ul class="list-none flex flex-col gap-5 items-center w-full h-full">
            <li class="list w-full flex justify-center <?php if($pagina == 'home') echo 'active'; ?>" title="Início">
                <button type="button" onclick="window.location.href='?pagina=home'" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
                    <i class="fa-solid fa-house"></i>
                </button>
            </li>
            <li class="list w-full flex justify-center <?php if($pagina == 'posts') echo 'active'; ?>" title="Posts">
                <button type="button" onclick="window.location.href='?pagina=posts'" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
                    <i class="fa-solid fa-note-sticky"></i>
                </button>
            </li>
            <li class="list w-full flex justify-center <?php if($pagina == 'profile') echo 'active'; ?>" title="Perfil">
                <button type="button" onclick="window.location.href='?pagina=profile'" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
                    <i class="fa-solid fa-user"></i>
                </button>
            </li>
            <li class="list w-full flex justify-center <?php if($pagina == 'settings') echo 'active'; ?>" title="Configurações">
                <button type="button" onclick="window.location.href='?pagina=settings'" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
                    <i class="fa-solid fa-gear"></i>
                </button>
            </li>
            <li class="list w-full flex justify-center logout-button">
                <button type="button" onclick="showLogoutConfirmation();" class="flex items-center justify-center text-lg rounded-xl transition-all duration-300">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sair</span>
                </button>
            </li>
        </ul>
    </nav>
    <main class="content-area ml-20 p-8 flex-grow overflow-y-auto h-screen custom-scrollbar">
        <div id="ajax-message-container"></div>
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div id="message-alert" class="message bg-green-100 text-green-700 p-4 mb-6 rounded-lg font-semibold max-w-2xl mx-auto text-center border border-green-200">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }

        if ($pagina == 'home') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Bem-vindo à Página Inicial!</h2>';
            echo '<p class="text-gray-600 text-lg">Explore as novas postagens ou crie a sua.</p>';
        } elseif ($pagina == 'posts') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Posts</h2>';
            ?>
            <div class="post-card mb-6">
                <form id="post-form" action="homePage.php" method="POST" enctype="multipart/form-data">
                    <div class="post-form">
                        <textarea name="post_text" placeholder="O que você está pensando, <?php echo htmlspecialchars($userName); ?>?" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 mb-3" rows="3"></textarea>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <label for="post_image" class="cursor-pointer text-gray-500 hover:text-blue-500 transition-colors duration-200" title="Fazer upload de imagem">
                                    <i class="fa-solid fa-image text-2xl"></i>
                                </label>
                                <input type="file" id="post_image" name="post_image" class="hidden" accept="image/*">
                            </div>
                            <div id="image-preview-container" class="image-preview-container mb-2">
                                <img id="image-preview" src="#" alt="Pré-visualização da imagem" class="image-preview">
                            </div>
                            <div class="flex justify-end items-center">
                                <button type="submit" name="submit_post" id="post-button" class="post-button bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-full transition-colors duration-200">Postar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div id="posts-container">
            <?php
            $sql_posts = "SELECT p.*, u.nome AS autor_nome, 
                            (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id) AS total_curtidas,
                            (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id AND id_usuario = ?) AS curtiu_usuario
                          FROM postagens p 
                          JOIN usuarios u ON p.usuario_id = u.id 
                          ORDER BY p.data_criacao DESC";
            $stmt_posts = $conn->prepare($sql_posts);
            $stmt_posts->bind_param("i", $userId);
            $stmt_posts->execute();
            $result_posts = $stmt_posts->get_result();

            if ($result_posts->num_rows > 0) {
                while ($post = $result_posts->fetch_assoc()) {
                    ?>
                    <div class="post-card mb-6" data-post-id="<?php echo $post['id']; ?>">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <i class="fa-solid fa-user-circle text-2xl text-gray-500 mr-2"></i>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($post['autor_nome']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo date("d/m/Y H:i", strtotime($post['data_criacao'])); ?></p>
                                </div>
                            </div>
                            <?php if ($post['usuario_id'] == $userId): ?>
                                <button type="button" onclick="showDeleteConfirmation('post', <?php echo $post['id']; ?>);" class="text-gray-500 hover:text-red-500 transition-colors duration-200" title="Excluir postagem">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($post['conteudo'])): ?>
                            <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($post['conteudo']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($post['imagem'])): ?>
                            <div class="my-3">
                                <img src="<?php echo htmlspecialchars($post['imagem']); ?>" alt="Imagem da postagem" class="rounded-lg max-w-full h-auto">
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-200">
                            <span onclick="toggleLike(<?php echo $post['id']; ?>)" class="flex items-center transition-colors duration-200 like-button">
                                <i id="like-icon-<?php echo $post['id']; ?>" class="fa-solid fa-heart mr-1 like-icon <?php echo $post['curtiu_usuario'] > 0 ? 'liked' : ''; ?>"></i>
                                <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['total_curtidas']; ?></span>
                            </span>
                        </div>

                        <div class="comment-container">
                            <h6 class="font-semibold text-gray-700 mb-2">Comentários</h6>
                            <div id="comments-list-<?php echo $post['id']; ?>">
                                <?php
                                $sql_comments = "SELECT c.*, u.nome AS autor_comentario_nome FROM comentarios c JOIN usuarios u ON c.id_usuario = u.id WHERE c.id_postagem = ? ORDER BY c.data_criacao ASC";
                                $stmt_comments = $conn->prepare($sql_comments);
                                $stmt_comments->bind_param("i", $post['id']);
                                $stmt_comments->execute();
                                $result_comments = $stmt_comments->get_result();

                                if ($result_comments->num_rows > 0) {
                                    while ($comment = $result_comments->fetch_assoc()) {
                                        ?>
                                        <div class="comment flex justify-between items-center" data-comment-id="<?php echo $comment['id']; ?>">
                                            <div class="flex-grow">
                                                <span class="user-name"><?php echo htmlspecialchars($comment['autor_comentario_nome']); ?>:</span>
                                                <?php echo htmlspecialchars($comment['conteudo']); ?>
                                                <p class="text-xs text-gray-400"><?php echo date("d/m/Y", strtotime($comment['data_criacao'])); ?></p>
                                            </div>
                                            <?php if ($comment['id_usuario'] == $userId): ?>
                                                <button type="button" onclick="showDeleteConfirmation('comment', <?php echo $comment['id']; ?>);" class="text-gray-400 hover:text-red-500 transition-colors duration-200 ml-2" title="Excluir comentário">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo '<p id="no-comments-message-' . $post['id'] . '" class="text-sm text-gray-500">Nenhum comentário ainda.</p>';
                                }
                                $stmt_comments->close();
                                ?>
                            </div>
                            <form class="comment-form mt-3" data-post-id="<?php echo $post['id']; ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="text" name="comment_text" placeholder="Adicionar um comentário..." required class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">Comentar</button>
                            </form>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div id="no-posts-message" class="text-center text-gray-500 mt-10">Nenhuma postagem encontrada. Seja o primeiro a postar!</div>';
            }
            $stmt_posts->close();
            ?>
            </div>
        <?php
        }
        ?>
    </main>
    
    <div id="logout-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-xl font-semibold mb-4">Confirmar Saída</h3>
            <p class="text-gray-600 mb-6">Tem certeza de que deseja sair da sua conta?</p>
            <div class="flex justify-around gap-4">
                <button onclick="hideLogoutConfirmation();" class="w-full bg-gray-200 text-gray-800 font-bold py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">Cancelar</button>
                <a href="logout.php" class="w-full bg-red-500 text-white font-bold py-2 rounded-lg text-center hover:bg-red-600 transition-colors duration-200">Sair</a>
            </div>
        </div>
    </div>

    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-xl font-semibold mb-4 text-red-600">Confirmar Exclusão</h3>
            <p id="delete-modal-message" class="text-gray-600 mb-6">Tem certeza que deseja excluir este item?</p>
            <div class="flex justify-around gap-4">
                <button onclick="hideDeleteConfirmation();" class="w-full bg-gray-200 text-gray-800 font-bold py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">Cancelar</button>
                <button onclick="deleteItemAJAX()" id="delete-modal-button" class="w-full bg-red-500 text-white font-bold py-2 rounded-lg text-center hover:bg-red-600 transition-colors duration-200">Excluir</button>
            </div>
        </div>
    </div>

    <script>
        // Variáveis globais para armazenar o tipo e ID do item a ser excluído
        let itemToDeleteType;
        let itemToDeleteId;

        function showLogoutConfirmation() {
            document.getElementById('logout-modal').classList.add('show');
        }
        function hideLogoutConfirmation() {
            document.getElementById('logout-modal').classList.remove('show');
        }

        function showDeleteConfirmation(type, id) {
            const deleteModal = document.getElementById('delete-modal');
            const modalMessage = document.getElementById('delete-modal-message');
            
            // Armazena o tipo e ID nas variáveis globais
            itemToDeleteType = type;
            itemToDeleteId = id;

            if (type === 'post') {
                modalMessage.innerText = 'Tem certeza que deseja excluir esta postagem? Todos os comentários e curtidas relacionados também serão apagados.';
            } else if (type === 'comment') {
                modalMessage.innerText = 'Tem certeza que deseja excluir este comentário? Esta ação é irreversível.';
            }
            
            deleteModal.classList.add('show');
        }
        
        function hideDeleteConfirmation() {
            document.getElementById('delete-modal').classList.remove('show');
        }
        
        // --- NOVA FUNÇÃO: Lida com a exclusão via AJAX ---
        async function deleteItemAJAX() {
            hideDeleteConfirmation();
            
            const deleteButton = document.getElementById('delete-modal-button');
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');

            let url = '';
            if (itemToDeleteType === 'post') {
                url = `homePage.php?action=delete_post&post_id=${itemToDeleteId}`;
            } else if (itemToDeleteType === 'comment') {
                url = `homePage.php?action=delete_comment&comment_id=${itemToDeleteId}`;
            }

            try {
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    showMessage(result.message);
                    
                    if (itemToDeleteType === 'post') {
                        const postElement = document.querySelector(`.post-card[data-post-id="${itemToDeleteId}"]`);
                        if (postElement) {
                            postElement.remove();
                        }
                    } else if (itemToDeleteType === 'comment') {
                        const commentElement = document.querySelector(`.comment[data-comment-id="${itemToDeleteId}"]`);
                        if (commentElement) {
                            commentElement.remove();
                            const postCard = commentElement.closest('.post-card');
                            if (postCard) {
                                const commentsList = postCard.querySelector('.comment-container > div');
                                if (commentsList.children.length === 0) {
                                    commentsList.innerHTML = `<p id="no-comments-message-${itemToDeleteId}" class="text-sm text-gray-500">Nenhum comentário ainda.</p>`;
                                }
                            }
                        }
                    }
                } else {
                    showMessage(result.message, 'error');
                }

            } catch (error) {
                console.error('Erro de rede:', error);
                showMessage('Ocorreu um erro ao excluir o item. Por favor, tente novamente.', 'error');
            } finally {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                itemToDeleteType = null;
                itemToDeleteId = null;
            }
        }


        // --- NOVA FUNÇÃO PARA CURTIR/DESCURTIR ---
        function toggleLike(postId) {
            fetch(`homePage.php?action=like&post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const likeCountElement = document.getElementById(`like-count-${postId}`);
                        const likeIconElement = document.getElementById(`like-icon-${postId}`);

                        likeCountElement.innerText = data.count;
                        
                        if (data.liked) {
                            likeIconElement.classList.add('liked');
                        } else {
                            likeIconElement.classList.remove('liked');
                        }
                    } else {
                        console.error('Erro ao curtir a postagem.');
                    }
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                });
        }

        function showMessage(message, type = 'success') {
            const container = document.getElementById('ajax-message-container');
            const alertClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200';
            
            const alertDiv = document.createElement('div');
            alertDiv.id = 'message-alert';
            alertDiv.className = `message p-4 mb-6 rounded-lg font-semibold max-w-2xl mx-auto text-center border ${alertClass}`;
            alertDiv.innerText = message;
            
            container.innerHTML = '';
            container.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.classList.add('hide');
                setTimeout(() => {
                    alertDiv.remove();
                }, 500);
            }, 3000);
        }

        const postImageInput = document.getElementById('post_image');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');

        postImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '#';
                imagePreviewContainer.style.display = 'none';
            }
        });

        // Nova função para anexar o event listener aos formulários de comentário
        function attachCommentFormListener(form) {
            form.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const commentInput = this.querySelector('input[name="comment_text"]');
                const postId = this.dataset.postId;
                const commentText = commentInput.value.trim();
                const submitButton = this.querySelector('button[type="submit"]');

                if (!commentText) {
                    showMessage("O comentário não pode ser vazio.", 'error');
                    return;
                }

                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('comment_text', commentText);

                try {
                    const response = await fetch('homePage.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        showMessage(result.message);
                        commentInput.value = '';
                        
                        const commentsList = document.getElementById(`comments-list-${result.post_id}`);
                        const newCommentElement = document.createElement('div');
                        newCommentElement.innerHTML = result.new_comment_html;
                        
                        const noCommentsMessage = document.getElementById(`no-comments-message-${result.post_id}`);
                        if (noCommentsMessage) {
                            noCommentsMessage.remove();
                        }
                        
                        commentsList.appendChild(newCommentElement.firstElementChild);

                    } else {
                        showMessage(result.message, 'error');
                    }

                } catch (error) {
                    console.error('Erro:', error);
                    showMessage('Ocorreu um erro ao enviar o comentário. Por favor, tente novamente.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }


        document.getElementById('post-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const postButton = document.getElementById('post-button');
            const postTextarea = document.querySelector('textarea[name="post_text"]');
            
            if (postTextarea.value.trim() === '' && postImageInput.files.length === 0) {
                showMessage("A postagem não pode ser vazia.", 'error');
                return;
            }

            postButton.disabled = true;
            postButton.classList.add('opacity-50', 'cursor-not-allowed');

            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Erro de rede: ${response.statusText}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    showMessage(result.message);
                    postTextarea.value = '';
                    postImageInput.value = '';
                    document.getElementById('image-preview-container').style.display = 'none';
                    
                    const postsContainer = document.getElementById('posts-container');
                    const newPostElement = document.createElement('div');
                    newPostElement.innerHTML = result.new_post_html;

                    const noPostsMessage = document.getElementById('no-posts-message');
                    if (noPostsMessage) {
                        noPostsMessage.remove();
                    }

                    const newPostNode = newPostElement.firstElementChild;
                    postsContainer.prepend(newPostNode);

                    // Anexar o listener de comentário ao novo post
                    const newCommentForm = newPostNode.querySelector('.comment-form');
                    if (newCommentForm) {
                        attachCommentFormListener(newCommentForm);
                    }

                } else {
                    showMessage(result.message, 'error');
                }

            } catch (error) {
                console.error('Erro:', error);
                showMessage('Ocorreu um erro ao enviar a postagem. Por favor, tente novamente.', 'error');
            } finally {
                postButton.disabled = false;
                postButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });

        // Event Listener para os formulários de comentários existentes (no carregamento da página)
        document.addEventListener('DOMContentLoaded', () => {
            const commentForms = document.querySelectorAll('.comment-form');
            commentForms.forEach(form => {
                attachCommentFormListener(form);
            });
        });

        setTimeout(function() {
            var message = document.getElementById('message-alert');
            if (message) {
                message.classList.add('hide');
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            }
        }, 2000);
    </script>
</body>
</html>