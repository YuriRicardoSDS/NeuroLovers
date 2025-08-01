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
        header("Location: homePage.php");
        exit;
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
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: url('./img/background.jpg');
      height: 100vh;
      overflow: hidden;
    }

    /* Navbar */
    .navbar {
      background-color: #333;
      padding: 10px 20px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .navbar button {
      background-color: #555;
      color: white;
      border: none;
      padding: 8px 16px;
      cursor: pointer;
      border-radius: 4px;
    }

    .navbar button:hover {
      background-color: #777;
    }

    /* Container centralizador */
    .main-container {
      position: relative;
      width: 100%;
      height: calc(100vh - 50px);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Painel e imagem agrupados */
    .login-wrapper {
      display: none;
      display: flex;
      gap: 20px;
      opacity: 0;
      transform: translateY(-50px);
      transition: all 0.5s ease;
    }

    .login-wrapper.show {
      display: flex;
      opacity: 1;
      transform: translateY(0);
    }

    /* Painel de login */
    .login-container {
      width: 400px;
      background: white;
      padding: 30px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .login-container input[type="text"],
    .login-container input[type="password"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    .login-container label {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .login-container .forgot-password {
      text-align: right;
      margin-top: 10px;
    }

    .login-container .forgot-password a {
      text-decoration: none;
      color: #007BFF;
    }

    .login-container .forgot-password a:hover {
      text-decoration: underline;
    }

    .login-container button {
      width: 100%;
      padding: 10px;
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 10px;
    }

    .login-container button:hover {
      background-color: #0056b3;
    }

    /* Imagem decorativa com mesmo tamanho do painel */
    .image-panel {
      width: 400px;
      height: auto;
      border-radius: 8px;
      overflow: hidden;
    }

    .image-panel img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    @media (max-width: 900px) {
      .image-panel {
        display: none;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <div class="navbar">
    <div>Meu Site</div>
    <button onclick="toggleLogin()">Login</button>
  </div>

  <!-- Área central -->
  <div class="main-container">
    <!-- Painel de Login com imagem -->
    <div id="loginWrapper" class="login-wrapper">
      <div class="login-container">
        <h2>Login</h2>
        <input type="text" placeholder="Usuário" />
        <input type="password" placeholder="Senha" />
        <label><input type="checkbox" /> Lembrar-me sempre</label>
        <button> <a href="homePage.php" class="btn btn-link text-center"></a>Entrar </button>
        <div class="forgot-password">
          <a href="#">Esqueceu a senha?</a>
        </div>
      </div>

      <!-- Imagem ou GIF do mesmo tamanho -->
      <div class="image-panel">
        <img src="img/background.jpg" alt="Imagem decorativa" />
      </div>
    </div>
  </div>

  <!-- Script -->
  <script>
    const wrapper = document.getElementById("loginWrapper");

    function toggleLogin() {
      if (wrapper.classList.contains("show")) {
        wrapper.classList.remove("show");
        setTimeout(() => {
          wrapper.style.display = "none";
        }, 500);
      } else {
        wrapper.style.display = "flex";
        void wrapper.offsetWidth;
        wrapper.classList.add("show");
      }
    }
  </script>

</body>
</html>
