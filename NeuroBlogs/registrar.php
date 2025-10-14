<?php
include 'conexao.php';

// Variável para armazenar a mensagem de erro
$erro_mensagem_login = "";
$erro_mensagem_senha = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // ALTERADO: $nome para $apelido e $_POST['nome'] para $_POST['apelido']
    $apelido = $_POST['apelido']; 
    // CORREÇÃO 1: TROCANDO 'user' POR 'email'
    $login = $_POST['email']; 
    $senha_plain = $_POST['senha'];
    $confirmacao_plain = $_POST['confirmacao'];

    if($senha_plain == $confirmacao_plain){
        // Verifica se o email já existe
        // CORREÇÃO 2: TROCANDO 'user' POR 'email' NA CONSULTA
        $sql_verificar_usuario = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_verificar = mysqli_prepare($conn, $sql_verificar_usuario);
        mysqli_stmt_bind_param($stmt_verificar, "s", $login);
        mysqli_stmt_execute($stmt_verificar);
        mysqli_stmt_store_result($stmt_verificar);

        if(mysqli_stmt_num_rows($stmt_verificar) > 0){
            // Se o email já existe, define a mensagem de erro para o campo de email
            $erro_mensagem_login = "Já existe um usuário com esse E-mail!";
        } else {
            // Se o email não existe, criptografa a senha e insere no banco
            $senha = password_hash($senha_plain, PASSWORD_DEFAULT);
            // ALTERADO: Coluna 'nome' para 'apelido' na query SQL
            $sql_inserir_usuario = "INSERT INTO usuarios (apelido, email, senha, nivel) VALUES (?, ?, ?, 0)"; 
            $stmt_inserir = mysqli_prepare($conn, $sql_inserir_usuario);
            // ALTERADO: Variável $nome para $apelido
            mysqli_stmt_bind_param($stmt_inserir, "sss", $apelido, $login, $senha); 
            
            if(mysqli_stmt_execute($stmt_inserir)){
                header("Location: login.php?cadastro_sucesso=1");
                exit;
            } else {
                $erro_mensagem_login = "Erro ao registrar. Tente novamente.";
            }
            mysqli_stmt_close($stmt_inserir);
        }
        mysqli_stmt_close($stmt_verificar);
    } else {
        $erro_mensagem_senha = "As senhas não coincidem!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="registrar.css">
    <script src="https://unpkg.com/lucide@latest"></script></head>
<body class="d-flex justify-content-center align-items-center vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card p-4 shadow-lg">
          <h2 class="text-center mb-4">Cadastro de Usuário</h2>
          <form method="POST">
            
            <div class="input-group-with-error">
              <label for="apelido" class="form-label">Apelido</label> 
              <div class="input-group">
                <span class="input-group-text bg-light"><i data-lucide="user"></i></span>
                <input type="text" name="apelido" id="apelido" class="form-control" placeholder=" Insira o seu apelido (como gostaria de ser chamado)" required value="<?= $_POST['apelido'] ?? '' ?>"> 
              </div>
            </div>

            <div class="input-group-with-error">
              <label for="email" class="form-label">E-mail</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i data-lucide="mail"></i></span>
                <input type="email" name="email" id="email" class="form-control" placeholder="Seu E-mail" required value="<?= $_POST['email'] ?? '' ?>">
              </div>
              <?php if ($erro_mensagem_login): ?>
                  <div class="text-danger mt-1 error-message"><?= $erro_mensagem_login ?></div>
              <?php endif; ?>
            </div>

            <div class="input-group-with-error">
              <label for="senha" class="form-label">Senha</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i data-lucide="lock"></i></span>
                <input type="password" name="senha" id="senha" class="form-control" placeholder="Crie uma senha" required>
                <button type="button" class="btn btn-outline-secondary" data-target="senha">
                    <i class="fa-solid fa-eye-slash eye-icon"></i>
                </button>
              </div>
            </div>

            <div class="input-group-with-error">
              <label for="confirmacao" class="form-label">Confirmar Senha</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i data-lucide="lock"></i></span>
                <input type="password" name="confirmacao" id="confirmacao" class="form-control" placeholder="Confirme sua senha" required>
                <button type="button" class="btn btn-outline-secondary" data-target="confirmacao">
                    <i class="fa-solid fa-eye-slash eye-icon"></i>
                </button>
              </div>
              <?php if ($erro_mensagem_senha): ?>
                  <div class="text-danger mt-1 error-message"><?= $erro_mensagem_senha ?></div>
              <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4">Cadastrar</button>
          </form>
          <div class="text-center mt-3">
              <p>Já tem conta? <a href="login.php" class="btn-link">Faça login aqui</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script> <script>
      // Inicializa os ícones do Lucide
      lucide.createIcons();
      
      // Script para fazer a mensagem de erro desaparecer
      document.addEventListener('DOMContentLoaded', function() {
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