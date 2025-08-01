<?php
include"conexao.php"
?>

<form method="POST" action="cadastro.php">
    
    <label for="nome">Nome:</label><br>
    <input type="text" id="nome" name="nome" required><br>

    <label for="senha">Senha:</label><br>
    <input type="password" id="senha" name="senha" required><br>


    <input type="submit" value="Criar Usuário">
</form>