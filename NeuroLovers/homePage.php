<?php 
session_start(); 

include "conexao.php";

if (!isset($_SESSION["usuario"])) { 
    header("Location: login.php"); 
    exit; 
}; 

// Dados simulados 
$usuarios_seguidos = ['João', 'Maria', 'Pedro']; 
if (!isset($_SESSION['posts'])) { 
    $_SESSION['posts'] = [ 
        ['autor' => 'João', 'foto' => 'foto1.jpg', 'descricao' => 'Foto da praia', 'curtidas' => 0, 'comentarios' => []], 
        ['autor' => 'Maria', 'foto' => 'foto2.jpg', 'descricao' => 'Meu cachorro fofo', 'curtidas' => 0, 'comentarios' => []], 
        ['autor' => 'Pedro', 'foto' => 'foto3.jpg', 'descricao' => 'Pôr do sol incrível', 'curtidas' => 0, 'comentarios' => []], 
    ]; 
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (isset($_POST['curtir'])) { 
        $idx = (int)$_POST['curtir']; 
        if (isset($_SESSION['posts'][$idx])) { 
            $_SESSION['posts'][$idx]['curtidas']++; 
        } 
    } 

    if (isset($_POST['comentar'])) { 
        $idx = (int)$_POST['post_comentario']; 
        $texto = trim($_POST['comentario_texto'] ?? ''); 
        if ($texto !== '' && isset($_SESSION['posts'][$idx])) { 
            $_SESSION['posts'][$idx]['comentarios'][] = [ 
                'usuario' => $_SESSION['usuario'], 
                'texto' => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'), 
                'hora' => date('H:i'), 
            ]; 
        } 
    } 
} 

// Pega qual página foi clicada 
$pagina = $_GET['pagina'] ?? 'home'; 
?> 

<!DOCTYPE html> 
<html lang="pt-BR"> 
<head> 
  <meta charset="UTF-8" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1" /> 
  <title>Menu + Área Conteúdo Conversas</title> 
  <link rel="stylesheet" href="homePage.css" /> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" /> 
</head> 
<body> 

  <nav class="navigation"> 
    <ul> 
      <li class="list <?php if($pagina == 'home') echo 'active'; ?>" title="Início"> 
        <a href="?pagina=home"><i class="fa-solid fa-house"></i></a> 
      </li> 
      <li class="list <?php if($pagina == 'profile') echo 'active'; ?>" title="Perfil"> 
        <a href="?pagina=profile"><i class="fa-solid fa-user"></i></a> 
      </li> 
      <li class="list <?php if($pagina == 'messages') echo 'active'; ?>" title="Mensagens"> 
        <a href="?pagina=messages"><i class="fa-solid fa-envelope"></i></a> 
      </li> 
      <li class="list <?php if($pagina == 'photos') echo 'active'; ?>" title="Fotos"> 
        <a href="?pagina=photos"><i class="fa-solid fa-camera"></i></a> 
      </li> 
      <li class="list <?php if($pagina == 'fire') echo 'active'; ?>" title="Match"> 
        <a href="?pagina=fire"><i class="fa-solid fa-fire"></i></a> 
      </li> 
      <li class="list <?php if($pagina == 'settings') echo 'active'; ?>" title="Configurações"> 
        <a href="?pagina=settings"><i class="fa-solid fa-gear"></i></a> 
      </li> 
    </ul> 
  </nav> 

  <main class="content-area" id="content-area"> 
    <?php
        if ($pagina == 'home') { 
            echo "<h2>Bem-vindo à Página Inicial!</h2>"; 
            echo "<p>Aqui você vê as últimas postagens.</p>"; 
            foreach ($_SESSION['posts'] as $idx => $post) { 
                echo "<div>"; 
                echo "<h4>{$post['autor']}</h4>"; 
                echo "<img src='{$post['foto']}' alt='foto' width='200'/>"; 
                echo "<p>{$post['descricao']}</p>"; 
                echo "<p>{$post['curtidas']} curtidas</p>"; 
                echo "</div><hr>"; 
            } 
        } 
        elseif ($pagina == 'profile') { 
            echo "<h2>Perfil do Usuário</h2>"; 
            echo "<p>Nome de usuário: {$_SESSION['usuario']}</p>"; 
            echo "<p>Seguidores: ".count($usuarios_seguidos)."</p>"; 
        } 
        elseif ($pagina == 'messages') { 
            echo "<h2>Suas Mensagens</h2>"; 
            echo "<p>Você ainda não possui mensagens novas.</p>"; 
        } 
        elseif ($pagina == 'photos') { 
            echo "<h2>Suas Fotos</h2>"; 
            echo "<p>Você ainda não enviou fotos.</p>"; 
        } 
        elseif ($pagina == 'settings') { 
            echo "<h2>Configurações</h2>"; 
            echo "<p>Aqui você pode alterar suas preferências.</p>"; 
        } 
        else { 
            echo "<h2>Página não encontrada!</h2>"; 
        } 
    ?> 
  </main> 
</body> 
</html> 