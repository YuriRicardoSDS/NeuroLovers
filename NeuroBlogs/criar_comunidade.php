<?php
session_start();
include "conexao.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['usuario_id'];
$mensagem = '';
$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome_comunidade']);
    $descricao = trim($_POST['descricao_comunidade']);

    // Validação básica
    if (empty($nome) || empty($descricao)) {
        $erro = "Todos os campos são obrigatórios.";
    } elseif (strlen($nome) > 100) {
        $erro = "O nome da comunidade não pode ter mais de 100 caracteres.";
    } else {
        // 1. Inserir a nova comunidade na tabela 'comunidades'
        $sql_comunidade = "INSERT INTO comunidades (nome, descricao, id_criador) VALUES (?, ?, ?)";
        $stmt_comunidade = mysqli_prepare($conn, $sql_comunidade);
        mysqli_stmt_bind_param($stmt_comunidade, "ssi", $nome, $descricao, $userId);

        if (mysqli_stmt_execute($stmt_comunidade)) {
            $nova_comunidade_id = mysqli_insert_id($conn);

            // 2. Inserir o criador como o primeiro membro na tabela 'membros_comunidade'
            $sql_membro = "INSERT INTO membros_comunidade (id_comunidade, id_usuario) VALUES (?, ?)";
            $stmt_membro = mysqli_prepare($conn, $sql_membro);
            mysqli_stmt_bind_param($stmt_membro, "ii", $nova_comunidade_id, $userId);
            mysqli_stmt_execute($stmt_membro);
            mysqli_stmt_close($stmt_membro);

            $mensagem = "Comunidade '$nome' criada e você já é um membro!";
            // Redireciona após o sucesso para evitar reenvio do formulário
            header("Location: homePage.php?msg=" . urlencode($mensagem));
            exit;
        } else {
            $erro = "Erro ao criar comunidade: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_comunidade);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Comunidade | NeuroBlogs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="homePage.css"> <style>
        .main-content {
            padding-left: 5rem; /* Espaço para o menu lateral fixo */
            padding-top: 20px;
            max-width: 800px;
            margin: 0 auto; /* Centraliza o conteúdo principal */
        }
        .form-card {
            background-color: var(--color-card-background, #fff);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
    </head>
<body>
    <?php include 'menu_navegacao.php'; // Inclua seu menu lateral aqui, se existir ?>

    <div class="main-content">
        <div class="form-card">
            <h2 class="mb-4"><i class="fas fa-users"></i> Criar Nova Comunidade</h2>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger" role="alert"><?= $erro ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="nome_comunidade" class="form-label">Nome da Comunidade</label>
                    <input type="text" class="form-control" id="nome_comunidade" name="nome_comunidade" required maxlength="100" value="<?= $_POST['nome_comunidade'] ?? '' ?>">
                </div>
                <div class="mb-3">
                    <label for="descricao_comunidade" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao_comunidade" name="descricao_comunidade" rows="5" required><?= $_POST['descricao_comunidade'] ?? '' ?></textarea>
                    <div class="form-text">Descreva o objetivo e as regras da sua comunidade.</div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Criar Comunidade</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script> lucide.createIcons(); </script>
</body>
</html>