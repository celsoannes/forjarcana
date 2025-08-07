<?php
session_start();
require __DIR__ . '/app/db.php';

// Se o usuário já estiver logado, redireciona para o portal
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header("Location: index.php");
    exit;
}

$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Busca usuário no banco (agora também busca o cargo e data_expiracao)
    $stmt = $pdo->prepare("SELECT id, nome, senha, cargo, data_expiracao FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Validação do reCAPTCHA (simplificado)
    $recaptcha_valido = !empty($_POST['g-recaptcha-response']);

    if ($usuario && password_verify($senha, $usuario['senha']) && $recaptcha_valido) {
        // Verifica data de expiração
        if ($usuario['data_expiracao'] && strtotime($usuario['data_expiracao']) < time()) {
            $erro = "Seu período de contratação terminou. Entre em contato com o administrador.";
        } else {
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_cargo'] = $usuario['cargo'];
            header("Location: index.php");
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forja Arcana - Login</title>
  <link href="css/estilo.css" rel="stylesheet">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <script src="js/script.js" defer></script>
</head>
<body>
  <div class="login-box">
    <img src="img/logo.png" alt="Forja Arcana" class="logo">
    <h2>Entrar na Forja Arcana</h2>
    <?php if (!empty($erro)): ?>
      <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="E-mail" required>
      </div>
      <div class="mb-3">
        <input type="password" name="senha" class="form-control" placeholder="Senha" required>
      </div>
      <div class="mb-3">
        <div class="g-recaptcha" data-sitekey="6Le2ueYqAAAAAK6blZSmXot6VOHqYU689flSfR5w"></div>
      </div>
      <button type="submit" class="btn btn-warning w-100">Entrar</button>
    </form>
  </div>
</body>
</html>