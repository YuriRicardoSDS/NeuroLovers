<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
};
include "conexao.php";


$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $user = $_POST['user'];
    $nivel = $_POST['nivel'];
    $sql = "UPDATE usuarios SET nome='$nome', user='$user', nivel='$nivel' WHERE id=$id";
    mysqli_query($conexao, $sql);
    header("Location: painel.php");
} else {
    $sql = "SELECT * FROM usuarios WHERE id=$id";
    $resultado = mysqli_query($conexao, $sql);
    $usuario = mysqli_fetch_assoc($resultado);

} 

?>

<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <title>Editar Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body class="container py-5">
    <h1>Editar Usuário</h1>
    <form method="POST">
        <div class="mb-3">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" value="<?= $usuario['nome']?>" required>
        </div>
        <div class="mb-3">
            <label>Usuário:</label>
            <input type="text" name="user" class="form-control" value="<?= $usuario['user']?>"required>
        </div>
        <div class="mb-3">
            <label>Nível:</label>
            <input type="number" name="nivel" class="form-control" value="<?= $usuario['nivel']?>" required>
        </div>
        <button class="btn btn-primary">Atualizar</button>
        <a href="painel.php" class="btn btn-secondary">Voltar</a>
    </form>
  </body>
</html>