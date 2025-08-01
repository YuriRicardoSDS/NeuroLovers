<?php
session_start();

include "conexao.php"; // Garanta que 'conexao.php' está configurado para sua conexão com o banco de dados, se planeja usar um.

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

// Garante que o array 'posts' exista na sessão
if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Cria o diretório 'uploads' se não existir
}

// Lida com o envio de uma nova postagem
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    $postText = htmlspecialchars($_POST['post_text']);
    $postImage = '';

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == UPLOAD_ERR_OK) {
        $imageFileType = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $validExtensions)) {
            $fileName = uniqid() . '.' . $imageFileType;
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $targetFilePath)) {
                $postImage = $targetFilePath;
            } else {
                $_SESSION['message'] = "Erro ao fazer upload da imagem.";
            }
        } else {
            $_SESSION['message'] = "Apenas JPG, JPEG, PNG e GIF são permitidos.";
        }
    }

    if (!empty($postText) || !empty($postImage)) {
        // Se a sessão 'usuario' não estiver definida, usa um placeholder
        $author = isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : 'Usuário Anônimo';

        $newPost = [
            'author' => $author,
            'text' => $postText,
            'image' => $postImage,
            'timestamp' => date('d/m/Y H:i'),
        ];
        array_unshift($_SESSION['posts'], $newPost); // Adiciona a nova postagem no início do array
        $_SESSION['message'] = "Postagem criada com sucesso!";
    } else {
        $_SESSION['message'] = "A postagem não pode ser vazia.";
    }
    // Redireciona para evitar reenvio do formulário ao atualizar a página
    header("Location: ?pagina=posts");
    exit;
}

// Lida com a exclusão de postagem
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post_index'])) {
    $postIndexToDelete = (int)$_POST['delete_post_index'];

    if (isset($_SESSION['posts'][$postIndexToDelete])) {
        // Antes de remover a postagem, verifique se há uma imagem e tente excluí-la
        $imagePath = $_SESSION['posts'][$postIndexToDelete]['image'];
        if (!empty($imagePath) && file_exists($imagePath)) {
            unlink($imagePath); // Exclui o arquivo de imagem
        }

        array_splice($_SESSION['posts'], $postIndexToDelete, 1); // Remove a postagem pelo índice
        $_SESSION['message'] = "Postagem excluída com sucesso!";
    } else {
        $_SESSION['message'] = "Erro: Postagem não encontrada para exclusão.";
    }
    header("Location: ?pagina=posts");
    exit;
}


// Pega qual página foi clicada
$pagina = $_GET['pagina'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>HomePage</title>
  <!-- Link para o Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Configuração para usar a fonte Poppins, se desejar. Caso contrário, o Tailwind usará sua fonte padrão sans-serif. -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Link para o Font Awesome para ícones -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <style>
    /*
    * O arquivo homePage.css agora está quase vazio,
    * pois a maior parte da estilização é feita com classes Tailwind diretamente no HTML.
    * Você pode adicionar estilos customizados aqui, se necessário.
    */
    body {
        font-family: 'Poppins', sans-serif; /* Aplica a fonte Poppins ao corpo */
    }
    /* Estilo para a barra de rolagem customizada */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #eef3f8;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Estilo para o item de navegação ativo */
    .navigation ul li.active a {
      color: #1da1f2; /* Cor azul de destaque */
      background-color: rgba(29, 161, 242, 0.1); /* Fundo azul claro no active */
    }

    /* Garante que o input type="file" do formulário de postagem tenha o estilo aplicado no PHP */
    .post-form input[type="file"] {
        cursor: pointer;
    }

    /* Limpa o float para garantir que o layout não seja quebrado pelo botão flutuante */
    .clear-both {
        clear: both;
    }

    /* Estilo para o modal de confirmação */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Fundo semi-transparente */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000; /* Acima de tudo */
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: 0.75rem; /* rounded-xl */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-xl */
        max-width: 400px;
        width: 90%;
        text-align: center;
        transform: translateY(-20px);
        transition: transform 0.3s ease;
    }

    .modal-overlay.show .modal-content {
        transform: translateY(0);
    }
  </style>
</head>
<body class="h-screen flex bg-[#eef3f8]"> <!-- Fundo principal da página -->

  <!-- Barra de Navegação Lateral -->
  <nav class="navigation w-20 bg-white rounded-r-xl shadow-lg flex flex-col pt-6 fixed top-0 left-0 h-screen z-10">
    <ul class="list-none flex flex-col gap-5 items-center w-full">
      <li class="list w-full flex justify-center <?php if($pagina == 'home') echo 'active'; ?>" title="Início">
        <a href="?pagina=home" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
          <i class="fa-solid fa-house"></i>
        </a>
      </li>
      <li class="list w-full flex justify-center <?php if($pagina == 'posts') echo 'active'; ?>" title="Posts">
        <a href="?pagina=posts" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
          <i class="fa-solid fa-note-sticky"></i>
        </a>
      </li>
      <li class="list w-full flex justify-center <?php if($pagina == 'profile') echo 'active'; ?>" title="Perfil">
        <a href="?pagina=profile" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
          <i class="fa-solid fa-user"></i>
        </a>
      </li>
      <li class="list w-full flex justify-center <?php if($pagina == 'settings') echo 'active'; ?>" title="Configurações">
        <a href="?pagina=settings" class="flex justify-center items-center text-gray-600 text-3xl h-16 w-11/12 rounded-xl transition-all duration-300 hover:text-[#1da1f2] hover:bg-blue-50">
          <i class="fa-solid fa-gear"></i>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Área de Conteúdo Principal -->
  <main class="content-area ml-20 p-8 flex-grow overflow-y-auto h-screen custom-scrollbar">
    <?php
        // Exibe mensagem da sessão, se houver
        if (isset($_SESSION['message'])) {
            echo '<div class="message bg-green-100 text-green-700 p-4 mb-6 rounded-lg font-semibold max-w-2xl mx-auto text-center border border-green-200">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']); // Limpa a mensagem após exibição
        }

        if ($pagina == 'home') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Bem-vindo à Página Inicial!</h2>';
            echo '<p class="text-gray-600 text-lg">Explore as novas postagens ou crie a sua.</p>';
        }
        elseif ($pagina == 'posts') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-6">Criar Nova Postagem</h2>';
            echo '
            <div class="post-form-container bg-white p-6 rounded-xl shadow-md max-w-2xl mx-auto mb-8">
                <form action="?pagina=posts" method="POST" enctype="multipart/form-data" class="post-form space-y-4">
                    <textarea name="post_text" placeholder="O que você está pensando?" rows="5"
                              class="w-full p-4 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-[#1da1f2] transition-all duration-200"></textarea>
                    <input type="file" name="post_image" accept="image/*"
                           class="block w-full text-gray-600 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#1da1f2] hover:file:bg-blue-100">
                    <button type="submit" name="submit_post"
                            class="bg-[#1da1f2] text-white px-6 py-3 rounded-lg font-semibold text-lg cursor-pointer transition-all duration-300 hover:bg-[#0d95e8] transform hover:-translate-y-0.5 float-right">
                        Postar
                    </button>
                    <div class="clear-both"></div> <!-- Para limpar o float do botão -->
                </form>
            </div>
            ';

            echo '<h2 class="feed-title text-3xl font-bold text-[#1da1f2] mb-6 mt-10 text-center">Feed de Postagens</h2>';
            if (!empty($_SESSION['posts'])) {
                echo '<div class="grid gap-6 max-w-2xl mx-auto">'; // Grid para organizar os posts
                foreach ($_SESSION['posts'] as $index => $post) { // Adiciona $index para identificar a postagem
                    echo '
                    <div class="post-card bg-white rounded-xl p-6 shadow-md border border-gray-100 relative">
                        <div class="absolute top-4 right-4">
                            <button onclick="showDeleteConfirmation(' . $index . ')" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="fa-solid fa-ellipsis"></i>
                            </button>
                        </div>
                        <div class="autor-info flex items-center gap-4 mb-4">
                            <img src="https://via.placeholder.com/48/1da1f2/ffffff?text=U" alt="Foto de Perfil" class="foto-perfil-mini w-12 h-12 object-cover rounded-full border-2 border-[#1da1f2] flex-shrink-0">
                            <div>
                                <strong class="font-bold text-gray-800 text-lg">' . htmlspecialchars($post['author']) . '</strong>
                                <small class="text-gray-500 text-sm block">' . htmlspecialchars($post['timestamp']) . '</small>
                            </div>
                        </div>
                        <p class="text-gray-700 text-base leading-relaxed mb-4">' . nl2br(htmlspecialchars($post['text'])) . '</p>'; // nl2br converte quebras de linha em <br>
                    if (!empty($post['image'])) {
                        echo '<img src="' . htmlspecialchars($post['image']) . '" alt="Imagem da Postagem" class="imagem-post max-w-full rounded-lg mt-4 shadow-sm h-auto object-cover">';
                    }
                    echo '</div>';
                }
                echo '</div>'; // Fecha o grid de posts
            } else {
                echo '<p class="no-posts text-center text-gray-500 italic mt-10 text-lg">Nenhuma postagem ainda. Seja o primeiro a postar!</p>';
            }
        }
        elseif ($pagina == 'profile') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Perfil do Usuário</h2>';
            echo '<p class="text-gray-600 text-lg">Aqui você pode ver e editar suas informações de perfil.</p>';
            // Exemplo de como você poderia adicionar um formulário de perfil com Tailwind:
            echo '
            <div class="profile-form bg-white p-6 rounded-xl shadow-md max-w-2xl mx-auto mt-8">
                <form class="space-y-4">
                    <div>
                        <label for="username" class="block text-gray-700 font-semibold mb-2">Nome de Usuário:</label>
                        <input type="text" id="username" name="username" value="' . (isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : 'Nome de Usuário') . '"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1da1f2]">
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Email:</label>
                        <input type="email" id="email" name="email" value="usuario@exemplo.com"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1da1f2]">
                    </div>
                    <button type="submit" class="bg-[#1da1f2] text-white px-6 py-3 rounded-lg font-semibold text-lg cursor-pointer transition-all duration-300 hover:bg-[#0d95e8] transform hover:-translate-y-0.5">
                        Salvar Alterações
                    </button>
                </form>
            </div>
            ';
        }
        elseif ($pagina == 'settings') {
            echo '<h2 class="text-3xl font-bold text-[#1da1f2] mb-4">Configurações</h2>';
            echo '<p class="text-gray-600 text-lg">Aqui você pode ajustar as configurações da sua conta.</p>';
            // Exemplo de como você poderia adicionar opções de configuração:
            echo '
            <div class="bg-white p-6 rounded-xl shadow-md max-w-2xl mx-auto mt-8 space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Privacidade</h3>
                    <label class="inline-flex items-center">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-[#1da1f2] rounded focus:ring-[#1da1f2]">
                        <span class="ml-2 text-gray-700">Manter meu perfil privado</span>
                    </label>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Notificações</h3>
                    <label class="inline-flex items-center">
                        <input type="checkbox" class="form-checkbox h-5 w-5 text-[#1da1f2] rounded focus:ring-[#1da1f2]" checked>
                        <span class="ml-2 text-gray-700">Receber notificações por e-mail</span>
                    </label>
                </div>
                <button class="bg-[#1da1f2] text-white px-6 py-3 rounded-lg font-semibold text-lg cursor-pointer transition-all duration-300 hover:bg-[#0d95e8] transform hover:-translate-y-0.5">
                    Aplicar Configurações
                </button>
            </div>
            ';
        }
        else {
            echo '<h2 class="text-3xl font-bold text-red-600 mb-4">Página não encontrada!</h2>';
            echo '<p class="text-gray-600 text-lg">A página que você está tentando acessar não existe.</p>';
        }
    ?>
  </main>

  <!-- Modal de Confirmação de Exclusão -->
  <div id="deleteConfirmationModal" class="modal-overlay">
    <div class="modal-content">
      <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmar Exclusão</h3>
      <p class="text-gray-700 mb-6">Tem certeza que deseja excluir permanentemente essa postagem?</p>
      <div class="flex justify-center gap-4">
        <button onclick="hideDeleteConfirmation()" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-400 transition-colors duration-200">
          Cancelar
        </button>
        <button onclick="confirmDelete()" class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition-colors duration-200">
          Excluir
        </button>
      </div>
    </div>
  </div>

  <!-- Formulário oculto para exclusão de postagem -->
  <form id="deletePostForm" action="?pagina=posts" method="POST" style="display: none;">
    <input type="hidden" name="delete_post_index" id="deletePostIndex">
  </form>

  <script>
    let postIndexToDelete = -1; // Variável para armazenar o índice da postagem a ser excluída

    // Função para mostrar o modal de confirmação
    function showDeleteConfirmation(index) {
      postIndexToDelete = index;
      const modal = document.getElementById('deleteConfirmationModal');
      modal.classList.add('show');
    }

    // Função para esconder o modal de confirmação
    function hideDeleteConfirmation() {
      const modal = document.getElementById('deleteConfirmationModal');
      modal.classList.remove('show');
      postIndexToDelete = -1; // Reseta o índice
    }

    // Função para confirmar a exclusão e enviar o formulário
    function confirmDelete() {
      if (postIndexToDelete !== -1) {
        document.getElementById('deletePostIndex').value = postIndexToDelete;
        document.getElementById('deletePostForm').submit();
      }
      hideDeleteConfirmation(); // Esconde o modal após a ação
    }
  </script>
</body>
</html>