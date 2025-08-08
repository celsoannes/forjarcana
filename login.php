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

    $stmt = $pdo->prepare("SELECT id, nome, senha, cargo, data_expiracao FROM usuarios WHERE email = ?");
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
    <title>Forja Arcana - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-box">
        <div class="text-center mb-4">
            <img src="img/logo.png" alt="Logo" class="logo mb-3">
            <h2>Forja Arcana</h2>
            <p class="text-muted">Do éter à matéria</p>
        </div>
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">📧 Email</label>
                <input type="email" class="form-control" id="email" name="email" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">🔒 Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required autocomplete="current-password">
            </div>
            <div class="recaptcha-container text-center">
                <div class="g-recaptcha" data-sitekey="6Le2ueYqAAAAAK6blZSmXot6VOHqYU689flSfR5w"></div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-magic">
                    🪄 Ativar runas da forja
                </button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="#" class="text-decoration-none">Precisa forjar uma nova senha?</a>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>