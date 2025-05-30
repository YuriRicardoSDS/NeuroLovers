<?php
session_start();
include 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['user'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE user = '$login'";
    $res = mysqli_query($conexao, $sql);
    $usuario = mysqli_fetch_assoc($res);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario'] = $usuario['nome'];
        header("Location: painel.php");
        exit;
    } else {
        $erro = "Login ou senha incorreto!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <header>
        <h2 class="logo">logo</h2>
        <nav class="navigation">
            <a href="#">home</a>
            <a href="#">about</a>
            <a href="#">services</a>
            <a href="#">contact</a>
            <button class="btnLogin-popup">Login</button>
        </nav>
    </header>

    <script src="script.js"></script>
  </body>

  </html>