<?php
include 'conexao.php';

// Variável para armazenar a mensagem de erro
$erro_mensagem_login = "";
$erro_mensagem_senha = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nome = $_POST['nome'];
    $login = $_POST['user'];
    $senha_plain = $_POST['senha'];
    $confirmacao_plain = $_POST['confirmacao'];

    if($senha_plain == $confirmacao_plain){
        // Verifica se o usuário já existe
        $sql_verificar_usuario = "SELECT id FROM usuarios WHERE user = ?";
        $stmt_verificar = mysqli_prepare($conn, $sql_verificar_usuario);
        mysqli_stmt_bind_param($stmt_verificar, "s", $login);
        mysqli_stmt_execute($stmt_verificar);
        mysqli_stmt_store_result($stmt_verificar);

        if(mysqli_stmt_num_rows($stmt_verificar) > 0){
            // Se o usuário já existe, define a mensagem de erro para o campo de login
            $erro_mensagem_login = "Já existe um usuário com esse nome!";
        } else {
            // Se o usuário não existe, criptografa a senha e insere no banco
            $senha = password_hash($senha_plain, PASSWORD_DEFAULT);
            $sql_inserir_usuario = "INSERT INTO usuarios (nome, user, senha) VALUES (?, ?, ?)";
            $stmt_inserir = mysqli_prepare($conn, $sql_inserir_usuario);
            mysqli_stmt_bind_param($stmt_inserir, "sss", $nome, $login, $senha);

            if(mysqli_stmt_execute($stmt_inserir)){
                // Redireciona para a página inicial em caso de sucesso
                header("Location: homePage.php");
                exit();
            } else {
                // Em caso de erro na inserção, você pode definir uma mensagem geral
                // para fins de depuração ou exibição
                // $erro_mensagem_login = "Erro ao registrar usuário.";
            }
        }
    } else {
        // Se as senhas não conferem, define a mensagem de erro para o campo de senha
        $erro_mensagem_senha = "Senhas não conferem!";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  </head>
  <body>
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
          <?php if (!empty($erro_mensagem_login)): ?>
            <div class="text-danger error-message">Já existe um usuário com esse nome!</div>
          <?php endif; ?>
          
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="lock"></i></span>
            <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required>
            <button type="button" class="btn btn-outline-secondary" data-target="senha">
                <i class="fa-solid fa-eye eye-icon"></i>
            </button>
          </div>
          
          <div class="mb-3 input-group">
            <span class="input-group-text bg-light"><i data-lucide="shield-check"></i></span>
            <input type="password" name="confirmacao" id="confirma_senha" class="form-control" placeholder="Confirmar senha" required>
            <button type="button" class="btn btn-outline-secondary" data-target="confirma_senha">
                <i class="fa-solid fa-eye eye-icon"></i>
            </button>
          </div>
          
          <?php if (!empty($erro_mensagem_senha)): ?>
            <div class="text-danger mt-3"><?php echo htmlspecialchars($erro_mensagem_senha); ?></div>
          <?php endif; ?>
          <div class="d-grid gap-2">
            <button class="btn btn-primary">Registrar</button>
            <a href="login.php" class="btn btn-link text-center">Já tenho conta</a>
          </div>
        </form>
      </div>
    </div>

    <script>
      lucide.createIcons();
      
      // Função para desaparecer a mensagem de erro
      document.addEventListener("DOMContentLoaded", function() {
          const errorMessage = document.querySelector('.text-danger');
          if (errorMessage) {
              // Aguarda 3 segundos antes de começar a desaparecer
              setTimeout(function() {
                  errorMessage.style.transition = 'opacity 2s ease-in-out';
                  errorMessage.style.opacity = '0';
              }, 3000); // 3000 milissegundos = 3 segundos
              
              // Remove o elemento completamente da tela após a transição
              setTimeout(function() {
                  errorMessage.remove();
              }, 5000); // O tempo total (3s de espera + 2s de transição)
          }
      });

      // Script unificado para alternar a visibilidade de MÚLTIPLAS senhas
      document.querySelectorAll('button[data-target]').forEach(button => {
          button.addEventListener('click', function() {
              const targetId = this.getAttribute('data-target');
              const passwordInput = document.getElementById(targetId);
              const eyeIcon = this.querySelector('.eye-icon');
              
              // Alterna entre 'password' e 'text'
              const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
              passwordInput.setAttribute('type', type);
              
              // Alterna o ícone (olho aberto/fechado)
              eyeIcon.classList.toggle('fa-eye');
              eyeIcon.classList.toggle('fa-eye-slash');
          });
      });
    </script>
  </body>
</html>