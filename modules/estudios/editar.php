<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? '';
$erro = '';

// Busca estudio
$stmt = $pdo->prepare("SELECT * FROM estudios WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$estudio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudio) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $site = trim($_POST['site'] ?? '');

    if (!$nome) {
        $erro = 'Preencha o campo nome obrigatório.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE estudios SET nome = ?, site = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $site, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=estudios";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Estudio</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($estudio['nome']) ?>">
      </div>
      <div class="form-group">
        <label for="site">Site</label>
        <input type="url" class="form-control" id="site" name="site" placeholder="https://exemplo.com" value="<?= htmlspecialchars($estudio['site']) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=estudios" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>