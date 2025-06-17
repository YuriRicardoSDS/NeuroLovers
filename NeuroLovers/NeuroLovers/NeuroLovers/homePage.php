<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage do Usuário</title>
    <link rel="stylesheet" href="homePage.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="profile">
                <img src="avatar.png" alt="Avatar do Usuário">
                <h2><?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
            </div>
            <nav class="menu">
                <a href="#">
                    <i data-lucide="message-circle"></i>
                    Conversas
                </a>
                <a href="#">
                    <i data-lucide="users"></i>
                    Amigos
                </a>
                <a href="#">
                    <i data-lucide="settings"></i>
                    Configurações
                </a>
                <!-- Botão de logout funcional -->
                <form action="logout.php" method="POST" style="margin: 0;">
                    <button type="submit">
                        <i data-lucide="log-out"></i>
                        Sair
                    </button>
                </form>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h1>
                <p>Escolha uma conversa para começar a falar.</p>
            </header>
            <section class="conversations">
                <div class="conversation">
                    <h3>João</h3>
                    <p>Última mensagem...</p>
                </div>
                <div class="conversation">
                    <h3>Maria</h3>
                    <p>Última mensagem...</p>
                </div>
                <div class="conversation">
                    <h3>Pedro</h3>
                    <p>Última mensagem...</p>
                </div>
            </section>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
