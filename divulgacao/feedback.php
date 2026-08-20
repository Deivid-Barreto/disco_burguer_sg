<!DOCTYPE html>
<html lang="en">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="/../../css/divulgacao/index.css">
  <link rel="stylesheet" href="/../../css/header.css">
</head>
<header>
    <?php
    include __DIR__ . "/../header.php";
    ?>
</header>
<body>
        
<div class="container">

<h2>Feedback</h2>

<form method="POST">

<label>Nome:</label>
<input type="nome" name="nome" required>

<label>Email:</label>
<input name="email" required>

<label>Comentário:</label>
<textarea id="descricao" name="comentario" rows="5" cols="40" required></textarea>

<label>Foto:</label>
<input type="file" name="imagemcaminho" required>

<button>Entrar</button>
</form>
</div>
</body>
</html>