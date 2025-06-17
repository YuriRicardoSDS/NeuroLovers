<?php 

Session_start(); 

Include 'conexao.php'; 

$erro = ''; 

If ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    $login = $_POST['user']; 
    $senha = $_POST['senha']; 

 
    $sql = "SELECT * FROM usuarios WHERE user = '$login'"; 
    $res = mysqli_query($conexao, $sql); 
    $usuario = mysqli_fetch_assoc($res); 

    If ($usuario && password_verify($senha, $usuario['senha'])) { 
        $_SESSION['usuario'] = $usuario['nome'];
        $_SESSION['nivel'] = $usuario['nivel'];

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
  <link rel="stylesheet" href="login.css"> 
</head> 

<body>

<div class="main-container"> 
    <div class="image-container"> 
      <img src="img/pixel-heart.gif" alt="Logo" /> 
    </div> 

  <div class="login-container"> 
    <h2>Login</h2> 
     
    <?php if ($erro): ?> 
      <div class="erro"><?php echo $erro; ?></div> 
    <?php endif; ?>

    <form method="POST"> 
      <input type="text" placeholder="Usuário" name="user" id="user"/> 
      <input type="password" placeholder="Senha" name="senha" id="senha"/> 
      <label><input type="checkbox" /> Lembrar-me sempre</label> 
      <button type="submit" class="bnt">Entrar</button> 
      <div class="forgot-password"> 
        <a href="#">Esqueceu a senha?</a> 
      </div> 
    </form> 
  </div> 
</body> 
</html> 