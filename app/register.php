<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';

$totalUsuarios = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

if ($totalUsuarios > 0) {
    header("Location: login.php");
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
  $senha_confirmacao = $_POST['senha_confirmacao'] ?? '';
  $cargo = 'admin';
    $celular = trim($_POST['celular'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $data_expiracao = trim($_POST['data_expiracao'] ?? '');

  if (!$nome || !$sobrenome || !$email || !$senha || !$senha_confirmacao || !$celular || !$cpf || !$data_expiracao) {
        $erro = 'Preencha todos os campos obrigatórios.';
  } elseif ($senha !== $senha_confirmacao) {
    $erro = 'As senhas não coincidem. Confira e tente novamente.';
    } else {
        $dataObj = DateTime::createFromFormat('d/m/Y', $data_expiracao);
        $dataValida = $dataObj && $dataObj->format('d/m/Y') === $data_expiracao;

        if (!$dataValida) {
            $erro = 'Data de expiração inválida. Use o formato DD/MM/AAAA.';
        } else {
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, sobrenome, email, senha, cargo, celular, cpf, data_expiracao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nome,
                    $sobrenome,
                    $email,
                    $hash,
                    $cargo,
                    $celular,
                    $cpf,
                    $dataObj->format('Y-m-d')
                ]);

                header("Location: login.php");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $erro = 'Já existe um usuário com este e-mail ou CPF.';
                } else {
                    $erro = 'Erro ao cadastrar: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forja Arcana | Primeiro Cadastro</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>
<body class="hold-transition register-page">
<div class="register-box" style="width: 540px; max-width: 95%;">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Forja</b>Arcana</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Primeiro acesso: crie o primeiro usuário</p>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="nome" placeholder="Nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-user"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" name="sobrenome" placeholder="Sobrenome" required value="<?= htmlspecialchars($_POST['sobrenome'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-user-tag"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="email" class="form-control" name="email" placeholder="E-mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="senha" placeholder="Senha" required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="senha_confirmacao" placeholder="Repetir senha" required>
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-lock"></span></div>
          </div>
        </div>

        <input type="hidden" name="cargo" value="admin">

        <div class="input-group mb-3">
          <input type="text" class="form-control" id="celular" name="celular" placeholder="Celular" required data-inputmask="'mask': '(99) 99999-9999'" data-mask value="<?= htmlspecialchars($_POST['celular'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-phone"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF" required data-inputmask="'mask': '999.999.999-99'" data-mask value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-id-card"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" id="data_expiracao" name="data_expiracao" placeholder="Data de expiração (DD/MM/AAAA)" required data-inputmask="'alias': 'datetime', 'inputFormat': 'dd/mm/yyyy'" data-mask value="<?= htmlspecialchars($_POST['data_expiracao'] ?? '') ?>">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-calendar"></span></div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Criar primeiro usuário</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
  $(function () {
    $('[data-mask]').inputmask();
  });
</script>
</body>
</html>
