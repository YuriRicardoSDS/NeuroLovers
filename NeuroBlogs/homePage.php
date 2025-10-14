<?php
session_start();

// Define o fuso horário para o de São Paulo (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

include "conexao.php";

if (!isset($conn) || $conn->connect_error) {
    die("Erro fatal: A conexão com o banco de dados não pôde ser estabelecida. Verifique o arquivo 'conexao.php' e as credenciais. Erro: " . (isset($conn) ? $conn->connect_error : 'Variável \$conn não definida.'));
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
    $response = ['success' => false, 'new_post_html' => ''];

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $response['message'] = "Erro fatal: Não foi possível criar a pasta de uploads. Verifique as permissões do diretório.";
            echo json_encode($response);
            exit;
        }
    }

    $postText = trim($_POST['post_text'] ?? '');
    $tipoAnalise = $_POST['post_analise'] ?? 'analise-aprofundada'; // Novo campo
    $avisoSensibilidade = $_POST['post_sensibilidade'] ?? 'sem-spoiler'; // Novo campo
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
                        unlink($targetFilePath);
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    echo json_encode($response);
                    exit;
                }
            } else {
                echo json_encode($response);
                exit;
            }
        } elseif ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
            echo json_encode($response);
            exit;
        } else {
            echo json_encode($response);
            exit;
        }
    }

    // Define o formato automaticamente baseado na presença de imagem
    $postFormato = !empty($postImage) ? 'texto-imagem' : 'somente-texto';
    if (strpos(strtolower($postText), 'youtube') !== false || strpos(strtolower($postText), 'video') !== false) {
        $postFormato = 'video-curto'; // Prioriza como vídeo se a palavra-chave estiver no texto
    }


    if (!empty($postText) || !empty($postImage)) {
        // Prepare a query para incluir as novas colunas
        $stmt_insert = $conn->prepare("INSERT INTO postagens (usuario_id, conteudo, imagem, formato, tipo_analise, aviso_sensibilidade) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_insert === false) {
             echo json_encode($response);
             exit;
        }
        // Bind parameters
        $stmt_insert->bind_param("isssss", $userId, $postText, $postImage, $postFormato, $tipoAnalise, $avisoSensibilidade);
        
        if ($stmt_insert->execute()) {
            $newPostId = $conn->insert_id;
            
            ob_start();
            ?>
            <div class="post-card mb-6" data-post-id="<?php echo $newPostId; ?>" 
                 data-formato="<?php echo $postFormato; ?>"
                 data-analise="<?php echo $tipoAnalise; ?>" 
                 data-sensibilidade="<?php echo $avisoSensibilidade; ?>">
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
        } else {
        }
        $stmt_insert->close();
    } else {
    }

    echo json_encode($response);
    exit;
}

// --- Lida com a criação de comentários (via AJAX) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'new_comment_html' => ''];
    $post_id = (int)$_POST['post_id'];
    $comment_text = trim($_POST['comment_text']);

    if (!empty($comment_text)) {
        $stmt_comment = $conn->prepare("INSERT INTO comentarios (id_postagem, id_usuario, conteudo) VALUES (?, ?, ?)");
        if ($stmt_comment === false) {
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
                <?php // O botão de exclusão é exibido porque o usuário atual é o autor do comentário recém-criado. ?>
                    <button type="button" onclick="showDeleteConfirmation('comment', <?php echo $newCommentId; ?>);" class="text-gray-400 hover:text-red-500 transition-colors duration-200 ml-2" title="Excluir comentário">
                        <i class="fa-solid fa-trash"></i>
                    </button>
            </div>
            <?php
            $response['new_comment_html'] = ob_get_clean();
            $response['success'] = true;
            $response['post_id'] = $post_id;
        } else {
        }
        $stmt_comment->close();
    } else {
    }

    echo json_encode($response);
    exit;
}

// --- Lida com a funcionalidade de curtir/descurtir via AJAX ---
if (isset($_GET['action']) && $_GET['action'] == 'like' && isset($_GET['post_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'count' => 0, 'liked' => false];
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
    }
    
    echo json_encode($response);
    exit;
}

// --- Lógica para excluir um post (via AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_post' && isset($_GET['post_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
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
        } else {
        }
        $stmt_delete->close();
    } else {
    }

    echo json_encode($response);
    exit;
}

// --- Lógica para excluir um comentário (via AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_comment' && isset($_GET['comment_id'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
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
        } else {
        }
        $stmt_delete->close();
    } else {
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
    <link rel="stylesheet" href="homePage.css">
    <style>
        /* Estilos adicionais para o Preview de Imagem (ajuste se precisar de um arquivo CSS dedicado) */
        .image-preview-container {
            display: none; /* Escondido por padrão */
            margin-top: 10px;
        }
        .image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* Estilos para o botão de like */
        .like-icon {
            color: #ccc; /* Cor padrão */
            cursor: pointer;
            transition: color 0.2s;
        }
        .like-icon.liked {
            color: #e31b23; /* Cor vermelha quando curtido */
        }
        /* Estilos para o Modal de Confirmação */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
                <button type="button" onclick="showLogoutConfirmation();" class="flex flex-col items-center justify-center text-lg rounded-xl transition-all duration-300 w-11/12 h-16 hover:bg-red-50 gap-1">
                    <i class="fa-solid fa-right-from-bracket text-3xl text-red-500 hover:text-red-700"></i>
                    <span>Sair</span>
                </button>
            </li>
        </ul>
    </nav>
    <main class="content-area ml-20 p-8 flex-grow overflow-y-auto h-screen custom-scrollbar">
        <?php
        if ($pagina == 'home') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Bem-vindo à Página Inicial!</h2>';
            echo '<p class="text-gray-600 text-lg">Explore as novas postagens ou crie a sua.</p>';
        } elseif ($pagina == 'posts') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Posts</h2>';
            ?>
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="lg:w-1/4">
                    <div class="filter-panel bg-white p-4 rounded-xl shadow-lg sticky top-8">
                        <h4 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2"><i class="fa-solid fa-filter mr-2"></i> Filtros Avançados</h4>
                        
                        <div class="mb-5">
                            <h5 class="text-md font-semibold text-blue-500 mb-2">Formato de Conteúdo</h5>
                            <div class="space-y-1">
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="formato-filter mr-2" type="checkbox" value="somente-texto"> Somente Texto
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="formato-filter mr-2" type="checkbox" value="texto-imagem"> Texto e Imagem
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="formato-filter mr-2" type="checkbox" value="video-curto"> Vídeo Curto
                                </label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h5 class="text-md font-semibold text-blue-500 mb-2">Tipo de Análise</h5>
                            <div class="space-y-1">
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="analise-filter mr-2" type="checkbox" value="detalhes-tecnicos"> Detalhes Técnicos
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="analise-filter mr-2" type="checkbox" value="resumo-rapido"> Resumos Rápidos
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="analise-filter mr-2" type="checkbox" value="analise-aprofundada"> Análises Aprofundadas
                                </label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h5 class="text-md font-semibold text-blue-500 mb-2">Avisos de Sensibilidade</h5>
                            <div class="space-y-1">
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="sensibilidade-filter mr-2" type="checkbox" value="sem-spoiler"> Sem Spoilers
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="sensibilidade-filter mr-2" type="checkbox" value="alerta-luzes"> Alerta: Luzes Piscando
                                </label>
                                <label class="flex items-center text-sm text-gray-600 cursor-pointer hover:text-gray-800 transition-colors">
                                    <input class="sensibilidade-filter mr-2" type="checkbox" value="conteudo-sensivel"> Conteúdo Sensível
                                </label>
                            </div>
                        </div>
                        
                        <button onclick="aplicarFiltros()" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 rounded-lg transition-colors duration-200 mt-2">Aplicar Filtros</button>
                    </div>
                </div>

                <div class="lg:w-3/4">
                    
                    <div class="post-card mb-6">
                        <form id="post-form" action="homePage.php" method="POST" enctype="multipart/form-data">
                            <div class="post-form">
                                <textarea name="post_text" placeholder="O que você está pensando, <?php echo htmlspecialchars($userName); ?>? (Marque abaixo o tipo de postagem)" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 mb-3" rows="3"></textarea>
                                
                                <div class="flex flex-col sm:flex-row gap-4 mb-3">
                                    <div class="w-full sm:w-1/2">
                                        <label for="post_analise" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Análise:</label>
                                        <select id="post_analise" name="post_analise" class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                            <option value="analise-aprofundada">Análise Aprofundada</option>
                                            <option value="resumo-rapido">Resumo Rápido</option>
                                            <option value="detalhes-tecnicos">Detalhes Técnicos</option>
                                        </select>
                                    </div>
                                    <div class="w-full sm:w-1/2">
                                        <label for="post_sensibilidade" class="block text-sm font-medium text-gray-700 mb-1">Aviso de Sensibilidade:</label>
                                        <select id="post_sensibilidade" name="post_sensibilidade" class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                            <option value="sem-spoiler">Sem Spoilers</option>
                                            <option value="alerta-luzes">Alerta: Luzes Piscando</option>
                                            <option value="conteudo-sensivel">Conteúdo Sensível</option>
                                        </select>
                                    </div>
                                </div>
                                
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
                    // ATUALIZAÇÃO: Adicionadas as novas colunas à query de seleção
                    $sql_posts = "SELECT p.*, u.apelido AS autor_nome, p.formato, p.tipo_analise, p.aviso_sensibilidade,
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
                            // REMOÇÃO: As variáveis simuladas foram removidas. Os dados são lidos do banco.
                            $postFormato = $post['formato'];
                            $postAnalise = $post['tipo_analise'];
                            $postSensibilidade = $post['aviso_sensibilidade'];
                            
                            ?>
                            <div class="post-card mb-6" 
                                data-post-id="<?php echo $post['id']; ?>"
                                data-formato="<?php echo $postFormato; ?>"
                                data-analise="<?php echo $postAnalise; ?>"
                                data-sensibilidade="<?php echo $postSensibilidade; ?>">
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
                                        $sql_comments = "SELECT c.*, u.apelido AS autor_comentario_nome FROM comentarios c JOIN usuarios u ON c.id_usuario = u.id WHERE c.id_postagem = ? ORDER BY c.data_criacao ASC";
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
                                            $stmt_comments->close();
                                        } else {
                                            echo '<p id="no-comments-message-' . $post['id'] . '" class="text-sm text-gray-500">Nenhum comentário ainda.</p>';
                                        }
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
                        echo '<p class="text-gray-500 p-4 bg-white rounded-lg shadow-md">Nenhuma postagem encontrada.</p>';
                    }
                    $stmt_posts->close();
                    ?>
                    </div>
                </div>
            </div>
            <?php
        } elseif ($pagina == 'profile') {
            
            // 1. FETCH DE DADOS DO USUÁRIO (AGORA USANDO LEFT JOIN NA TABELA 'perfil_usuario')
            $sql_profile = "SELECT u.apelido, u.email, 
                                   p.pronoun, p.neurotipos, p.bio_pessoal, 
                                   p.cor_fundo_pref, p.cor_texto_pref 
                            FROM usuarios u
                            LEFT JOIN perfil_usuario p ON u.id = p.id 
                            WHERE u.id = ?";
            $stmt_profile = $conn->prepare($sql_profile);
            $stmt_profile->bind_param("i", $userId);
            $stmt_profile->execute();
            $user_data = $stmt_profile->get_result()->fetch_assoc();
            $stmt_profile->close();

            // 2. CONTAGEM DE POSTS 
            $sql_contagem = "SELECT COUNT(*) AS total_posts, 
                                SUM(CASE WHEN tipo_analise = 'resumo-rapido' THEN 1 ELSE 0 END) AS total_apoio
                            FROM postagens WHERE usuario_id = ?";
            $stmt_contagem = $conn->prepare($sql_contagem);
            $stmt_contagem->bind_param("i", $userId);
            $stmt_contagem->execute();
            $contagem_data = $stmt_contagem->get_result()->fetch_assoc();
            $stmt_contagem->close();
            
            $total_posts = $contagem_data['total_posts'];
            $total_apoio = $contagem_data['total_apoio']; 

            // 3. APLICAÇÃO DAS CORES DE ACESSIBILIDADE
            // Se o join falhar por algum motivo, ele usará os valores padrão da tabela perfil_usuario, mas fazemos um fallback caso o JOIN não encontre nada.
            $fundo_pref = htmlspecialchars($user_data['cor_fundo_pref'] ?? '#FFFFFF'); 
            $texto_pref = htmlspecialchars($user_data['cor_texto_pref'] ?? '#374151'); 
            
            $feed_style = "background-color: {$fundo_pref}; color: {$texto_pref};";
            ?>
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="w-full lg:w-1/3">
                    <div class="post-card sticky top-8 p-6">
                        <div class="flex flex-col items-center">
                            <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-4 border-4 border-purple-500">
                                <i class="fa-solid fa-seedling text-4xl text-purple-600"></i>
                            </div>
                            
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user_data['apelido']); ?></h3>
                            
                            <p class="text-sm font-medium text-gray-500 mb-2">
                                <?php echo htmlspecialchars($user_data['pronoun'] ?: "Pronome não definido"); ?>
                            </p>
                            
                            <p class="text-md font-semibold text-purple-700 p-1 rounded-md bg-purple-100 border border-purple-300 mb-4 text-center">
                                <i class="fa-solid fa-mask mr-1"></i> **Neurotipos:** <?php echo htmlspecialchars($user_data['neurotipos'] ?: "Ainda não informado"); ?>
                            </p>
                            
                            <h4 class="text-sm font-bold text-gray-600 mb-2 border-b w-full text-left">Sobre Minha Jornada:</h4>
                            <p class="text-gray-700 w-full mb-6 text-sm italic p-2 rounded-lg border">
                                <?php echo nl2br(htmlspecialchars($user_data['bio_pessoal'] ?: "Compartilhando minha jornada neurodivergente e buscando conexões significativas.")); ?>
                            </p>
                            
                            <div class="w-full mb-6 p-3 border border-gray-200 rounded-lg">
                                <h4 class="text-sm font-bold text-gray-600 mb-2">Preferências de Acessibilidade:</h4>
                                <div class="flex items-center text-xs text-gray-700 gap-2">
                                    <i class="fa-solid fa-palette text-purple-500"></i>
                                    <span class="font-semibold">Cores do Perfil:</span>
                                    <div class="w-4 h-4 rounded-full border border-gray-500" style="background-color: <?php echo $fundo_pref; ?>;"></div>
                                    /
                                    <div class="w-4 h-4 rounded-full border border-gray-500" style="background-color: <?php echo $texto_pref; ?>;"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Este feed está ajustado para o meu conforto visual.</p>
                            </div>

                            <div class="flex justify-around w-full mt-2 text-center">
                                <div>
                                    <p class="text-2xl font-bold text-gray-800"><?php echo $total_posts; ?></p>
                                    <p class="text-sm text-gray-500">Publicações</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-purple-600"><?php echo $total_apoio; ?></p>
                                    <p class="text-sm text-purple-500 font-medium">Posts de Apoio</p>
                                </div>
                            </div>
                            
                            <button onclick="window.location.href='?pagina=settings'" class="mt-6 w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded-lg transition-colors duration-200">
                                Ajustar Preferências e Bio
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="w-full lg:w-2/3">
                    <h2 class="text-2xl font-bold text-gray-700 mb-4 border-b-2 border-purple-500 pb-2">Minha Atividade Recente</h2>
                    
                    <div id="user-posts-feed">
                        <?php
                        // Query para buscar SOMENTE os posts do usuário logado ($userId)
                        $sql_user_posts = "SELECT p.*, u.apelido AS autor_nome, p.formato, p.tipo_analise, p.aviso_sensibilidade,
                                                    (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id) AS total_curtidas,
                                                    (SELECT COUNT(*) FROM curtidas WHERE id_postagem = p.id AND id_usuario = ?) AS curtiu_usuario
                                                FROM postagens p 
                                                JOIN usuarios u ON p.usuario_id = u.id 
                                                WHERE p.usuario_id = ?
                                                ORDER BY p.data_criacao DESC";
                        $stmt_user_posts = $conn->prepare($sql_user_posts);
                        $stmt_user_posts->bind_param("ii", $userId, $userId); 
                        $stmt_user_posts->execute();
                        $result_user_posts = $stmt_user_posts->get_result();
                        
                        if ($result_user_posts->num_rows > 0) {
                            while ($post = $result_user_posts->fetch_assoc()) {
                                
                                // O CARD DE POSTAGEM RECEBE O ESTILO INLINE DE ACESSIBILIDADE
                                ?>
                                <div class="post-card mb-6" data-post-id="<?php echo $post['id']; ?>" style="<?php echo $feed_style; ?>">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-user-circle text-2xl mr-2" style="color: <?php echo $texto_pref; ?>;"></i>
                                            <div>
                                                <p class="font-semibold" style="color: <?php echo $texto_pref; ?>;"><?php echo htmlspecialchars($post['autor_nome']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo date("d/m/Y H:i", strtotime($post['data_criacao'])); ?> 
                                                    <span class="ml-2 text-xs font-medium text-purple-600">[<?php echo ucfirst(str_replace('-', ' ', $post['tipo_analise'])); ?>]</span>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if ($post['usuario_id'] == $userId): ?>
                                            <button type="button" onclick="showDeleteConfirmation('post', <?php echo $post['id']; ?>);" class="text-gray-500 hover:text-red-500 transition-colors duration-200" title="Excluir postagem">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($post['conteudo'])): ?>
                                        <p class="mb-3" style="color: <?php echo $texto_pref; ?>;"><?php echo htmlspecialchars($post['conteudo']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($post['imagem'])): ?>
                                        <div class="my-3">
                                            <img src="<?php echo htmlspecialchars($post['imagem']); ?>" alt="Imagem da postagem" class="rounded-lg max-w-full h-auto">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-300">
                                        <span onclick="toggleLike(<?php echo $post['id']; ?>)" class="flex items-center transition-colors duration-200 like-button" style="color: <?php echo $texto_pref; ?>;">
                                            <i id="like-icon-<?php echo $post['id']; ?>" class="fa-solid fa-heart mr-1 like-icon <?php echo $post['curtiu_usuario'] > 0 ? 'liked' : ''; ?>"></i>
                                            <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['total_curtidas']; ?></span>
                                        </span>
                                    </div>
                                    
                                    <div class="comment-container">
                                        <h6 class="font-semibold mb-2" style="color: <?php echo $texto_pref; ?>;">Comentários</h6>
                                        <p class="text-sm text-gray-500">A lógica de comentários completa deve ser inserida aqui, garantindo que o `style` seja aplicado também.</p>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="text-gray-500 p-4 bg-white rounded-lg shadow-md">Você ainda não publicou nenhuma análise ou postagem.</p>';
                        }
                        $stmt_user_posts->close();
                        ?>
                    </div>
                </div>
            </div>
            <?php
        } elseif ($pagina == 'settings') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Configurações</h2>';
            echo '<p class="text-gray-600 text-lg">Aqui você pode ajustar sua biografia, neurotipos e preferências de acessibilidade. (Próxima etapa!)</p>';
        }
        ?>
    </main>

    <div id="logout-confirmation-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Confirmar Saída</h3>
            <p class="mb-6">Tem certeza de que deseja sair da sua conta?</p>
            <div class="flex justify-end gap-3">
                <button onclick="hideLogoutConfirmation();" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition duration-200">Cancelar</button>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">Sair</a>
            </div>
        </div>
    </div>
    
    <script>
        // Funções de Modal (Sair)
        function showLogoutConfirmation() {
            document.getElementById('logout-confirmation-modal').classList.add('show');
        }
        function hideLogoutConfirmation() {
            document.getElementById('logout-confirmation-modal').classList.remove('show');
        }

        // Função de Confirmação de Exclusão (Geral)
        function showDeleteConfirmation(type, id) {
            if (confirm(`Tem certeza que deseja excluir este ${type}? Esta ação é irreversível.`)) {
                if (type === 'post') {
                    deletePost(id);
                } else if (type === 'comment') {
                    deleteComment(id);
                }
            }
        }
        
        // Função de Exclusão de Post (AJAX)
        function deletePost(postId) {
            fetch(`homePage.php?action=delete_post&post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                        if (postElement) {
                            postElement.remove();
                        }
                    } else {
                        alert('Erro ao excluir a postagem.');
                    }
                })
                .catch(error => console.error('Erro de rede/AJAX na exclusão do post:', error));
        }

        // Função de Exclusão de Comentário (AJAX)
        function deleteComment(commentId) {
            fetch(`homePage.php?action=delete_comment&comment_id=${commentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
                        if (commentElement) {
                            const postCard = commentElement.closest('.post-card');
                            const postId = postCard ? postCard.dataset.postId : null;
                            const commentsList = commentElement.closest(`#comments-list-${postId}`);
                            
                            commentElement.remove();
                            
                            // Se não houver mais comentários, mostra a mensagem "Nenhum comentário ainda"
                            if (commentsList && commentsList.children.length === 0 && postId) {
                                commentsList.innerHTML = `<p id="no-comments-message-${postId}" class="text-sm text-gray-500">Nenhum comentário ainda.</p>`;
                            }
                        }
                    } else {
                        alert('Erro ao excluir o comentário.');
                    }
                })
                .catch(error => console.error('Erro de rede/AJAX na exclusão do comentário:', error));
        }

        // Função de Curtir/Descurtir (AJAX)
        function toggleLike(postId) {
            fetch(`homePage.php?action=like&post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const icon = document.getElementById(`like-icon-${postId}`);
                        const count = document.getElementById(`like-count-${postId}`);
                        
                        count.textContent = data.count;

                        if (data.liked) {
                            icon.classList.add('liked');
                        } else {
                            icon.classList.remove('liked');
                        }
                    }
                })
                .catch(error => console.error('Erro de rede/AJAX no like:', error));
        }

        // Função para Aplicar Filtros
        function aplicarFiltros() {
            const posts = document.querySelectorAll('#posts-container .post-card');
            // Obtém os valores dos filtros ativos (checked) em cada grupo
            const formatoFilters = Array.from(document.querySelectorAll('.formato-filter:checked')).map(cb => cb.value);
            const analiseFilters = Array.from(document.querySelectorAll('.analise-filter:checked')).map(cb => cb.value);
            const sensibilidadeFilters = Array.from(document.querySelectorAll('.sensibilidade-filter:checked')).map(cb => cb.value);

            posts.forEach(post => {
                const postFormato = post.getAttribute('data-formato');
                const postAnalise = post.getAttribute('data-analise');
                const postSensibilidade = post.getAttribute('data-sensibilidade');

                let passesFormato = true;
                let passesAnalise = true;
                let passesSensibilidade = true;

                // Lógica de Filtragem: O post deve satisfazer TODOS os grupos de filtros que estão ativos (AND LÓGICO).

                // A. Verifica o grupo Formato (OR lógico dentro do grupo: se filtros ativos, precisa bater com pelo menos um)
                if (formatoFilters.length > 0) {
                    passesFormato = formatoFilters.includes(postFormato);
                }

                // B. Verifica o grupo Tipo de Análise
                if (analiseFilters.length > 0) {
                    passesAnalise = analiseFilters.includes(postAnalise);
                }

                // C. Verifica o grupo Sensibilidade
                if (sensibilidadeFilters.length > 0) {
                    passesSensibilidade = sensibilidadeFilters.includes(postSensibilidade);
                }

                // Resultado Final: O post só é visível se passar em TODOS os filtros ativos (AND LÓGICO)
                if (passesFormato && passesAnalise && passesSensibilidade) {
                    post.style.display = 'block'; // Mostra o post
                } else {
                    post.style.display = 'none'; // Esconde o post
                }
            });
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para fechar o modal ao clicar fora
            const modalOverlay = document.getElementById('logout-confirmation-modal');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) {
                        hideLogoutConfirmation();
                    }
                });
            }
            
            // --- 1. Lógica de Preview de Imagem (Upload) ---
            const imageInput = document.getElementById('post_image');
            const imagePreview = document.getElementById('image-preview');
            const previewContainer = document.getElementById('image-preview-container');

            if (imageInput && imagePreview && previewContainer) {
                imageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            previewContainer.style.display = 'block';
                        }
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        imagePreview.src = '#';
                        previewContainer.style.display = 'none';
                    }
                });
            }
            
            // --- 2. Lógica de Submissão do Formulário de Post (AJAX) ---
            const postForm = document.getElementById('post-form');
            if (postForm) {
                postForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const postButton = document.getElementById('post-button');
                    postButton.disabled = true;
                    postButton.textContent = 'Postando...';

                    const formData = new FormData(this);
                    
                    fetch('homePage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const postsContainer = document.getElementById('posts-container');
                            postsContainer.insertAdjacentHTML('afterbegin', data.new_post_html); // Adiciona no topo
                            postForm.reset();
                            imagePreview.src = '#';
                            previewContainer.style.display = 'none';
                        } else {
                            alert('Erro ao publicar o post. Tente novamente.');
                        }
                        postButton.disabled = false;
                        postButton.textContent = 'Postar';
                    })
                    .catch(error => {
                        console.error('Erro de rede/AJAX na publicação do post:', error);
                        alert('Erro de conexão ao publicar o post.');
                        postButton.disabled = false;
                        postButton.textContent = 'Postar';
                    });
                });
            }
            
            // --- 3. Lógica de Submissão do Formulário de Comentário (AJAX) ---
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const commentInput = this.querySelector('input[name="comment_text"]');
                    const commentText = commentInput.value.trim();
                    const postId = this.dataset.postId;
                    
                    if (commentText === '') return;
                    
                    const formData = new FormData();
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

        });
    </script>
</body>
</html>