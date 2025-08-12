<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Página não encontrada | Forjarcana</title>
  <link rel="icon" type="image/png" sizes="32x32" href="dist/img/favicon-32x32.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <div class="content-wrapper" style="margin-left:0">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Erro 404</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Início</a></li>
              <li class="breadcrumb-item active">Erro 404</li>
            </ol>
          </div>
        </div>
      </div>
    </section>
    <section class="content">
      <div class="error-page">
        <h2 class="headline text-warning">404</h2>
        <div class="error-content">
          <h3><i class="fas fa-exclamation-triangle text-warning"></i> Ops! Página não encontrada.</h3>
          <p>
            Não conseguimos encontrar a página que você procurava.<br>
            Você pode <a href="index.php">voltar para o início</a> ou usar o campo de busca abaixo.
          </p>
          <form class="search-form">
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Buscar...">
              <div class="input-group-append">
                <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i></button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Versão</b> 1.0
    </div>
    <strong>&copy; <?= date('Y') ?> Forjarcana.</strong> Todos os direitos reservados.
  </footer>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src