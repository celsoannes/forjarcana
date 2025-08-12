<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db.php';

// Se o usuário já estiver logado, redireciona para o portal
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header("Location: ../index.php");
    exit;
}

$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Inclua os novos campos na consulta
    $stmt = $pdo->prepare("SELECT id, nome, sobrenome, senha, cargo, data_expiracao, uuid, foto, celular, cpf FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    $recaptcha_valido = !empty($_POST['g-recaptcha-response']);

    if ($usuario && password_verify($senha, $usuario['senha']) && $recaptcha_valido) {
        if ($usuario['data_expiracao'] && strtotime($usuario['data_expiracao']) < time()) {
            $erro = "Seu período de contratação terminou. Entre em contato com o administrador.";
        } else {
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_sobrenome'] = $usuario['sobrenome'];
            $_SESSION['usuario_cargo'] = $usuario['cargo'];
            $_SESSION['usuario_uuid'] = $usuario['uuid'];
            $_SESSION['usuario_celular'] = $usuario['celular'];
            $_SESSION['usuario_cpf'] = $usuario['cpf'];

            // Carrega a thumbnail da foto, se existir
            if (!empty($usuario['foto'])) {
                $foto = $usuario['foto'];
                $thumb = str_replace('_media.png', '_thumb.png', $foto);
                $thumbPath = __DIR__ . '/../' . $thumb;
                if (file_exists($thumbPath)) {
                    $_SESSION['usuario_foto'] = $thumb;
                } else {
                    $_SESSION['usuario_foto'] = $foto;
                }
            } else {
                $_SESSION['usuario_foto'] = '';
            }

            header("Location: ../index.php");
            exit;
        }
    } else {
        $erro = "E-mail, senha ou reCAPTCHA inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forja Arcana | Login</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Forja</b>Arcana</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Faça login para iniciar sua sessão</p>
      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required autocomplete="username">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="senha" class="form-control" placeholder="Senha" required autocomplete="current-password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="mb-3 text-center">
          <div class="g-recaptcha" data-sitekey="6Le2ueYqAAAAAK6blZSmXot6VOHqYU689flSfR5w"></div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Lembrar-me
              </label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
          </div>
        </div>
      </form>
      <p class="mb-1 mt-3">
        <a href="#">Precisa forjar uma nova senha?</a>
      </p>
    </div>
  </div>
</div>
<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
</body>
</html>