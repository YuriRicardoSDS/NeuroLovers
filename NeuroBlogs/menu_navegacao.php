<?php
// PHP - Arquivo: menu_navegacao.php
// Este arquivo é incluído em homePage.php e usa as variáveis $userName e $userId.

// Se as variáveis não estiverem definidas (em caso de inclusão fora do homePage.php), define valores padrão.
$userName = $userName ?? 'Usuário';
$userId = $userId ?? 0;
?>
<nav class="navigation">
    <div class="logo-wrapper">
        <a href="homePage.php" class="logo-link" aria-label="Página Inicial do NeuroBlogs">
            <i data-lucide="brain-circuit" class="logo-icon"></i> 
        </a>
    </div>
    
    <ul class="nav-list">
        <li class="nav-item">
            <a href="homePage.php" class="nav-link" title="Feed (Página Inicial)">
                <i data-lucide="home"></i>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="perfil.php?id=<?= $userId ?>" class="nav-link" title="Meu Perfil">
                <i data-lucide="user"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="comunidades.php" class="nav-link" title="Comunidades">
                <i data-lucide="users-2"></i>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="config_acessibilidade.php" class="nav-link" title="Configurações de Acessibilidade">
                <i data-lucide="accessibility"></i>
            </a>
        </li>
    </ul>

    <div class="user-actions mt-auto">
        <li class="nav-item">
            <a href="logout.php" class="nav-link logout-link" title="Sair (Logout)">
                <i data-lucide="log-out"></i>
            </a>
        </li>
    </div>
</nav>