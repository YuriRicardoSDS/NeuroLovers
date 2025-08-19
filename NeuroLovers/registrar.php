<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nome = $_POST['nome'];
    $login = $_POST['user'];
    $senha_plain = $_POST['senha'];
    $confirmacao_plain = $_POST['confirmacao'];

    if($senha_plain == $confirmacao_plain){
        $senha = password_hash($senha_plain, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, user, senha) VALUE ('$nome', '$login', '$senha')";
        mysqli_query($conexao, $sql);

        header("Location: homePage.php?");
        exit(); // importante para evitar execução adicional
    } else {
        echo "Senhas não conferem!";
    }
}


?>


<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="registrar.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  </head>
  <body >
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
      <div class="card p-4 shadow-lg" style="width: 100%; max-width: 420px;">
        <h2 class="text-center mb-4">Cadastro</h2>
        <form method="POST">
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="user"></i></span>
            <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
          </div>
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="at-sign"></i></span>
            <input type="text" name="user" class="form-control" placeholder="Usuário" required>
          </div>
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="lock"></i></span>
            <input type="password" name="senha" class="form-control" placeholder="Senha" required>
          </div>
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="shield-check"></i></span>
            <input type="password" name="confirmacao" class="form-control" placeholder="Confirmar senha" required>
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-primary">Registrar</button>
            <a href="login.php" class="btn btn-link text-center">Já tenho conta</a>
          </div>
        </form>
      </div>
    </div>

    <script>
      lucide.createIcons();
    </script>
  </body>
</html>
