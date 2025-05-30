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

        header("Location: login.php?sucesso=1");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body>
    <div class="conta mt-5">
      <div class="card mx-auto p-4 shadow-lg" style="width: 30rem; ">
        <h2>Cadastro</h2>
        <form method="POST">
        <div class="mb-3">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Login:</label>
            <input type="text" name="user" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Senha:</label>
            <input type="password" name="senha" class="form-control" required>
        </div>
            <div class="mb-3">
            <label>Confirmar Senha:</label>
            <input type="password" name="confirmacao" class="form-control" required>
        </div>
        <button class="btn btn-primary">Registrar</button>
        <a href="login.php" class="btn btn-link">Já tenho conta</a>
      </form>
      </div>
    </div>

  </body>
</html>