<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit;
}

$pagina = $_GET['pagina'] ?? 'inicio';
$paginas_acao = [
    'excluir_resina',
    'excluir_usuario',
    'excluir_filamento'
];

if (in_array($pagina, $paginas_acao)) {
    require __DIR__ . '/paginas/' . $pagina . '.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forja Arcana - Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
  <!-- Navbar fixa para mobile -->
  <nav class="navbar navbar-dark bg-dark fixed-top mobile-navbar" style="display:none;">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <!-- Navbar mobile -->
        <img src="img/logo.png" alt="Forja Arcana" width="40" height="40" class="d-inline-block align-text-top">
        Forja Arcana
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-expanded="false" aria-label="Menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mobileMenu">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php include __DIR__ . '/core/menu.php'; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar para desktop -->
  <div class="container-fluid">
    <div class="row flex-md-row">
      <!-- Sidebar fixa à esquerda -->
      <nav class="sidebar col-12 col-md-3 col-lg-2 p-3 d-none d-md-block">
        <div class="text-center mb-4">
          <img src="img/logo.png" alt="Forja Arcana" style="max-width: 120px;">
        </div>
        <ul class="nav flex-column">
          <?php include __DIR__ . '/core/menu.php'; ?>
        </ul>
      </nav>
      <!-- Conteúdo centralizado na área restante -->
      <main class="content col-12 col-md-9 col-lg-10 d-flex justify-content-center align-items-start" style="min-height: 100vh;">
        <div class="container" style="max-width: 1200px; margin: 2rem auto;">
          <?php
            $arquivo = __DIR__ . '/paginas/' . $pagina . '.php';
            if (file_exists($arquivo)) {
                include $arquivo;
            } else {
                echo "<h1>Página não encontrada!</h1>";
            }
          ?>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>