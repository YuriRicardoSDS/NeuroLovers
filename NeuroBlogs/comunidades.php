<?php
// PHP - Arquivo: comunidades.php
session_start();
include "conexao.php"; // Inclui o arquivo de conexão

// Garante que o usuário esteja logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

// 1. Consulta SQL para buscar todas as comunidades e a contagem de membros
// Usamos LEFT JOIN para garantir que comunidades sem membros (recém-criadas) também apareçam.
$sql = "
    SELECT 
        c.id, 
        c.nome_comunidade, 
        c.descricao,
        COUNT(m.id_usuario) AS total_membros
    FROM 
        comunidades c
    LEFT JOIN 
        membros_comunidade m ON c.id = m.id_comunidade
    GROUP BY 
        c.id, c.nome_comunidade, c.descricao
    ORDER BY 
        c.nome_comunidade ASC
";

$result = mysqli_query($conn, $sql);

// Verifica se houve erro na consulta
if (!$result) {
    die("Erro ao buscar comunidades: " . mysqli_error($conn));
}

$comunidades = [];
while ($row = mysqli_fetch_assoc($result)) {
    $comunidades[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades NeuroBlogs</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="homePage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Estilos específicos para a listagem de comunidades */
        .community-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .community-card {
            background-color: #ffffff; /* Fundo branco, similar a post-card */
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .community-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .community-title {
            color: #1e3c72; /* Cor mais escura para o título */
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .community-description {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 15px;
            flex-grow: 1; /* Permite que ocupe o espaço necessário */
        }

        .community-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
        }

        .member-count {
            color: #666;
            font-size: 0.9rem;
        }

        .member-count i {
            margin-right: 5px;
            color: #2879e4; /* Cor azul primária */
        }

        .btn-join {
            background-color: #2879e4;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn-join:hover {
            background-color: #1e3c72;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Ajuste para o botão de criar comunidade */
        .btn-create {
            background-color: #10d832;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-create:hover {
            background-color: #0c9f28;
        }

        /* Estilo para quando não há comunidades */
        .no-communities {
            text-align: center;
            padding: 50px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php 
        // Se você tiver um menu de navegação, inclua-o aqui
        // include "menu_navegacao.php"; 
    ?> 

    <main class="main-content-single">
        <div class="header-section">
            <h2>Todas as Comunidades</h2>
            <a href="criar_comunidade.php" class="btn-create">
                <i class="fas fa-plus-circle"></i> Criar Nova Comunidade
            </a>
        </div>

        <?php if (count($comunidades) > 0): ?>
            <div class="community-grid">
                <?php foreach ($comunidades as $comunidade): ?>
                    <div class="community-card">
                        <div>
                            <h3 class="community-title"><?= htmlspecialchars($comunidade['nome_comunidade']) ?></h3>
                            <p class="community-description">
                                <?= empty($comunidade['descricao']) ? "Nenhuma descrição fornecida." : htmlspecialchars($comunidade['descricao']) ?>
                            </p>
                        </div>
                        
                        <div class="community-meta">
                            <span class="member-count">
                                <i class="fas fa-users"></i> 
                                <?= $comunidade['total_membros'] ?> membros
                            </span>
                            <a href="comunidade.php?id=<?= $comunidade['id'] ?>" class="btn-join">Ver Comunidade</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-communities">
                <p>Nenhuma comunidade foi encontrada no momento.</p>
                <p>Que tal ser o primeiro a <a href="criar_comunidade.php">criar uma</a>?</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>