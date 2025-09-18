<!DOCTYPE html>  
<html lang="pt-BR">  
<head>  
  <meta charset="UTF-8" />  
  <meta name="viewport" content="width=device-width, initial-scale=1" />  
  <title>Index</title>  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
  <link rel="stylesheet" href="index.css">
  <style>
    
  </style>
</head>  
<body>  
 
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
 
  <div class="container d-flex justify-content-center align-items-center central-card">
  <div class="card text-center">
    <img src="img/neuro.png" alt="Logo do site" class="logo-img" />
    <h4 class="mb-4">Bem-vindo ao NeuroBlogs!</h4>
    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#infoModal">
      Ver mais informações
    </button>
  </div>
</div>
 
  <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true"> 
    <div class="modal-dialog"> 
      <div class="modal-content shadow"> 
        <div class="modal-header"> 
          <h5 class="modal-title" id="infoModalLabel">Informações do Site</h5> 
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button> 
        </div> 
        <div class="modal-body"> 
          <p>NeuroBlogs</p> 
        </div> 
      </div> 
    </div> 
  </div> 

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
</body>  
</html>