<?php
// PHP - Arquivo: criar_comunidade.php
session_start();
include "conexao.php"; // Inclui o arquivo de conexão

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['usuario_id'];
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_comunidade = trim($_POST['nome_comunidade'] ?? ''); 
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome_comunidade)) {
        $error_message = "O nome da comunidade não pode estar vazio.";
    } else {
        
        // =======================================================================
        // 💥 CORREÇÃO 1: VERIFICA SE O NOME JÁ EXISTE 💥
        // =======================================================================
        $sql_verificar = "SELECT id FROM comunidades WHERE nome_comunidade = ?";
        $stmt_verificar = mysqli_prepare($conn, $sql_verificar);
        
        if ($stmt_verificar) {
            mysqli_stmt_bind_param($stmt_verificar, "s", $nome_comunidade);
            mysqli_stmt_execute($stmt_verificar);
            mysqli_stmt_store_result($stmt_verificar);

            if (mysqli_stmt_num_rows($stmt_verificar) > 0) {
                $error_message = "Erro: Já existe uma comunidade com o nome '{$nome_comunidade}'. Por favor, escolha outro nome.";
                mysqli_stmt_close($stmt_verificar);
            } else {
                mysqli_stmt_close($stmt_verificar);
                
                // =======================================================================
                // CORREÇÃO 2: INSERÇÃO DA COMUNIDADE (Linhas originais corrigidas)
                // =======================================================================
                $sql_insert = "INSERT INTO comunidades (nome_comunidade, descricao, id_criador) VALUES (?, ?, ?)";
                
                $stmt_insert = mysqli_prepare($conn, $sql_insert);
                
                if ($stmt_insert === false) {
                     $error_message = "Erro ao preparar a consulta de inserção: " . mysqli_error($conn);
                } else {
                    // Aqui está a linha 38 ou próxima, que antes falhava na execução
                    mysqli_stmt_bind_param($stmt_insert, "ssi", $nome_comunidade, $descricao, $userId);

                    if (mysqli_stmt_execute($stmt_insert)) {
                        $new_community_id = mysqli_insert_id($conn);
                        
                        // 💥 RESTAURADO: Adicionar o criador como membro e admin (is_admin = 1)
                        $sql_membro = "INSERT INTO membros_comunidade (id_comunidade, id_usuario, is_admin) VALUES (?, ?, 1)";
                        $stmt_membro = mysqli_prepare($conn, $sql_membro);
                        mysqli_stmt_bind_param($stmt_membro, "ii", $new_community_id, $userId);
                        
                        if (mysqli_stmt_execute($stmt_membro)) { // Linha 58 (ou próxima)
                            mysqli_stmt_close($stmt_membro);
                            
                            $success_message = "Comunidade '{$nome_comunidade}' criada com sucesso! Você foi adicionado como membro e administrador.";
                            
                            // Redirecionamento após sucesso para limpar o POST
                            header("Location: criar_comunidade.php?success=1&name=" . urlencode($nome_comunidade));
                            exit;
                        } 
                        // ...

                    } else {
                        // Isso só deve ocorrer se houver um erro de DB diferente da duplicidade
                        $error_message = "Erro ao finalizar a criação da comunidade: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
        } else {
            $error_message = "Erro ao preparar a consulta de verificação: " . mysqli_error($conn);
        }
    }
}

// =======================================================================
// LÓGICA PARA EXIBIR MENSAGEM DE SUCESSO APÓS REDIRECIONAMENTO (GET Request)
// =======================================================================
if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['name'])) {
    $nome_comunidade_sucesso = htmlspecialchars(urldecode($_GET['name']));
    $success_message = "Comunidade '{$nome_comunidade_sucesso}' criada com sucesso! Você foi adicionado como membro e administrador.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Comunidade</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <?php // include "menu_navegacao.php"; ?> 
    <main class="main-content-single">
        <h2>Criar Nova Comunidade</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="criar_comunidade.php" class="form-card">
            <div class="form-group">
                <label for="nome_comunidade">Nome da Comunidade:</label>
                <input type="text" id="nome_comunidade" name="nome_comunidade" required value="<?= htmlspecialchars($nome_comunidade ?? '') ?>"> 
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição (Opcional):</label>
                <textarea id="descricao" name="descricao" rows="5"><?= htmlspecialchars($descricao ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-full">Criar Comunidade</button>
        </form>
    </main>
</body>
</html>