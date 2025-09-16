<?php 
session_start(); 
include 'conexao.php'; 
$erro = ''; 
If ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    $login = $_POST['user']; 
    $senha = $_POST['senha']; 
    // A variável $conn é definida em 'conexao.php'
    $sql = "SELECT * FROM usuarios WHERE user = '$login'"; 
    $res = mysqli_query($conn, $sql);
    $usuario = mysqli_fetch_assoc($res); 
    If ($usuario && password_verify($senha, $usuario['senha'])) { 
        $_SESSION['usuario'] = $usuario['nome'];
        $_SESSION['nivel'] = $usuario['nivel'];
        $_SESSION['usuario_id'] = $usuario['id']; // Adicione esta linha para salvar o ID
        if ($usuario['nivel'] == 3) {
          header("Location: painel.php");
        exit;
        } else {
          header("Location: homePage.php"); 
          exit;
        }; 
        } else { 
          $erro = "Login ou senha incorretos!"; 
      }
} 
?> 
<!DOCTYPE html> 
<html lang="pt-br"> 
<head> 
  <meta charset="UTF-8" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
  <title>Painel de Login</title> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="login.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head> 
<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="card p-4 shadow-lg" style="width: 100%; max-width: 420px;">
    <h2 class="text-center">Login</h2> 
    <?php if ($erro): ?> 
      <div class="erro"><?php echo $erro; ?></div> 
    <?php endif; ?>
    <form method="POST"> 
      <div class="mb-3 input-group">
        <span class="input-group-text bg-light"><i data-lucide="at-sign"></i></span>
        <input type="text" placeholder="Usuário" name="user" id="user" class="form-control" required/>
      </div> 
      <div class="mb-3 input-group">
        <span class="input-group-text bg-light"><i data-lucide="lock"></i></span>
        <input type="password" placeholder="Senha" name="senha" id="senha" class="form-control" required/>
      </div> 
      <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button> 
      <div class="text-center"> 
        <a href="#" class="btn-link">Esqueceu a senha?</a> 
      </div> 
    </form> 
  </div> 
</div>
<script>
  lucide.createIcons();
</script> 
</body> 
</html>