<!DOCTYPE html>
<html lang="en">
    <link rel="stylesheet" href="../../css/sistema_web/login.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    
<div class="container">

<h2>Login</h2>

<form action="../../php/login.php" method="POST">

<label>Email:</label>
<input name="email" required>

<label>Senha:</label>
<input type="password" name="senha" required>

<label>Escolha um cargo:</label>

<select id="cargo" name="cargo" required>
  <option value="" selected disabled>Cargos</option>
  <option value="admin">Administrador</option>
  <option value="gar">Garçom</option>
  <option value="coz">Cozinheiro</option>
</select>

<button>Entrar</button>

</form>
</div>
</body>
</html>