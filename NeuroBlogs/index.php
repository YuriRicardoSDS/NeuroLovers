<!DOCTYPE html>  
<html lang="pt-BR">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1" />  
  <title>Index</title>  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />   
  <style> 
    /* Css*/ 
 
    body { 
      background: linear-gradient(to right, #dbeafe , #1e3c72);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    } 

    .navbar-custom { 
      background-color: #0d6efd; 
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); 
    } 
 
    .navbar-custom .nav-link,  
    .navbar-custom .navbar-brand { 
      color: #ffffff !important; 
      font-weight: 500; 
      transition: color 0.3s; 
    } 
 
    .navbar-custom .nav-link:hover { 
      color:rgb(255, 186, 130) !important; /* Dourado ao passar o mouse */ 
    } 

    .central-card { 
      margin-top: 120px; 
    } 

    .card { 
      border: none; 
      border-radius: 20px; 
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
      background:rgb(13, 110, 253); 
      padding: 40px 30px; 
      transition: transform 0.3s; 
    } 

    .card:hover { 
      transform: translateY(-5px); 
    } 
 
    .logo-img {
      display: block;
      margin: 0 auto 5px auto;
      max-width: 375px;
      height: auto;
    }

    .btn-custom { 
      background-color:rgb(255, 255, 255); 
      border: none; 
      border-radius: 50px; 
      padding: 10px 30px; 
      font-weight: 500; 
      transition: background-color 0.3s; 
    } 

    .btn-custom:hover { 
      background-color:rgb(255, 186, 130); 
    } 
 
    /* Modal */ 
    .modal-content { 
      color: #979797;
      border-radius: 20px; 
      padding: 20px; 
    } 

    .modal-header { 
      border-bottom: none; 
    } 
  </style> 

</head>  
<body>  
 
  <!-- Navbar fixa no topo --> 
  <nav class="navbar navbar-expand-lg navbar-custom fixed-top"> 
    <div class="container"> 
      <a class="navbar-brand" href="#">NeuroBlogs</a> 
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegação"> 
        <span class="navbar-toggler-icon"></span> 
      </button> 
      <div class="collapse navbar-collapse" id="navbarNav"> 
        <ul class="navbar-nav ms-auto"> 
          <li class="nav-item"> 
            <a class="nav-link" href="login.php">Entrar</a> 
          </li> 
          <li class="nav-item"> 
            <a class="nav-link" href="registrar.php">Cadastrar</a> 
          </li> 
        </ul> 
      </div> 
    </div> 
  </nav> 
 
  <!-- Conteúdo central --> 
  <div class="container d-flex justify-content-center align-items-center central-card">
  <div class="card text-center">
    <img src="img/neuro.png" alt="Logo do site" class="logo-img" />
    <h4 class="mb-4">Bem-vindo ao NeuroBlogs!</h4>
    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#infoModal">
      Ver mais informações
    </button>
  </div>
</div>
 
  <!-- Modal com informações --> 
  <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true"> 
    <div class="modal-dialog"> 
      <div class="modal-content shadow"> 
        <div class="modal-header"> 
          <h5 class="modal-title" id="infoModalLabel">Informações do Site</h5> 
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button> 
        </div> 
        <div class="modal-body"> 
          <!-- Aqui você edita o conteúdo -->
          <p>NeuroBlogs</p> 
        </div> 
      </div> 
    </div> 
  </div> 

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
</body>  
</html> 