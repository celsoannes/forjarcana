<?php
session_start();
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit;
}
// Define qual página será carregada no corpo
$pagina = $_GET['pagina'] ?? 'inicio';
$permitidas = ['inicio', 'impressoras', 'materiais', 'insumos', 'produtos', 'pintura', 'energia', 'usuarios', 'editar_usuario', 'excluir_usuario', 'adicionar_usuario', 'energia.php', 'adicionar_energia', 'editar_energia', 'adicionar_impressora', 'editar_impressora', 'adicionar_material', 'editar_material', 'adicionar_insumo', 'editar_insumo', 'adicionar_produto', 'editar_produto', 'adicionar_pintura', 'editar_pintura'];
if (!in_array($pagina, $permitidas)) {
    $pagina = 'inicio';
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
  <style>
    @media (max-width: 767.98px) {
      .sidebar {
        display: none !important;
      }
      .mobile-navbar {
        display: block !important;
      }
      body {
        padding-top: 70px; /* espaço para navbar fixa */
      }
    }
    @media (min-width: 768px) {
      .mobile-navbar {
        display: none !important;
      }
    }
  </style>
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
    <div class="row flex-column flex-md-row">
      <nav class="sidebar col-12 col-md-3 col-lg-2 p-3 d-none d-md-block">
        <div class="text-center mb-4">
          <!-- Sidebar desktop -->
          <img src="img/logo.png" alt="Forja Arcana" style="max-width: 120px;">
        </div>
        <ul class="nav flex-column">
          <?php include __DIR__ . '/core/menu.php'; ?>
        </ul>
      </nav>
      <main class="content col-12 col-md-9 ms-sm-auto col-lg-10">
        <?php
          $arquivo = __DIR__ . '/paginas/' . $pagina . '.php';
          if (file_exists($arquivo)) {
              include $arquivo;
          } else {
              echo "<h1>Página não encontrada!</h1>";
          }
        ?>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>