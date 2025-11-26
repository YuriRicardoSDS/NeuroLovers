<?php
// PHP - Arquivo: perfil.php (Perfil Público/Visualização)
session_start();
include "conexao.php"; // Inclui o arquivo de conexão

// Padrão: Usa o ID da URL (?id=X). Se não houver ID na URL, usa o ID do usuário logado.
$targetUserId = $_GET['id'] ?? ($_SESSION['usuario_id'] ?? 0);

if ($targetUserId == 0) {
    // Se não houver ID na URL e o usuário não estiver logado
    // Redireciona para o login ou mostra uma mensagem de erro
    header("Location: login.php");
    exit;
}

// Verifica se o perfil que está sendo visto pertence ao usuário logado
$isCurrentUser = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $targetUserId;

// 1. Buscar Dados do Perfil
$sql_fetch = "
    SELECT 
        u.apelido, 
        p.bio, 
        p.foto_perfil 
    FROM 
        usuarios u
    LEFT JOIN 
        perfil_usuario p ON u.id = p.id 
    WHERE 
        u.id = ?";
        
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);
mysqli_stmt_bind_param($stmt_fetch, "i", $targetUserId);
mysqli_stmt_execute($stmt_fetch);
$result_fetch = mysqli_stmt_get_result($stmt_fetch);
$perfil = mysqli_fetch_assoc($result_fetch);

if (!$perfil) {
    die("Usuário não encontrado.");
}

// Define as variáveis de exibição
$displayApelido = $perfil['apelido'] ?? 'Usuário';
$displayBio = $perfil['bio'] ?? 'Nenhuma biografia definida.';
// Verifica se há um caminho de foto válido. Se não, usa um caminho para uma imagem padrão.
$displayPhoto = ($perfil['foto_perfil'] && file_exists($perfil['foto_perfil'])) ? $perfil['foto_perfil'] : 'uploads/perfil/default_profile.png';

mysqli_stmt_close($stmt_fetch);
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?= htmlspecialchars($displayApelido) ?> | NeuroBlogs</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="homePage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .profile-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .profile-photo-wrapper {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%; 
            overflow: hidden;
            border: 4px solid #1e3c72;
            background-color: #f0f0f0;
        }
        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-header h1 {
            color: #1e3c72;
            font-size: 2.2rem;
            margin: 0;
        }
        .bio-section {
            margin-top: 20px;
            text-align: left;
            padding: 0 20px;
        }
        .bio-section h2 {
            color: #2879e4;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .bio-text {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #333;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 15px;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn-edit:hover {
            background-color: #388E3C;
        }
    </style>
</head>
<body>
    <main class="main-content-single">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-photo-wrapper">
                    <img src="<?= htmlspecialchars($displayPhoto) ?>" alt="Foto de Perfil de <?= htmlspecialchars($displayApelido) ?>" class="profile-photo">
                </div>
                <h1><?= htmlspecialchars($displayApelido) ?></h1>
                <p style="color: #666;">Membro da NeuroBlogs</p>
                
                <?php if ($isCurrentUser): ?>
                    <a href="perfil_edicao.php" class="btn-edit">
                        <i class="fas fa-user-edit"></i> Editar seu Perfil
                    </a>
                <?php endif; ?>
            </div>

            <div class="bio-section">
                <h2>Biografia</h2>
                <p class="bio-text"><?= htmlspecialchars($displayBio) ?></p>
            </div>
            
        </div>
    </main>
</body>
</html>