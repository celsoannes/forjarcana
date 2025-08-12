<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $site = trim($_POST['site'] ?? '');

    if (!$nome) {
        $erro = 'Preencha o campo nome obrigatório.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO estudios (usuario_id, nome, site, ultima_atualizacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario_id, $nome, $site]);
            echo '<script>window.location.href="?pagina=estudios";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Estudio</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required>
      </div>
      <div class="form-group">
        <label for="site">Site</label>
        <input type="url" class="form-control" id="site" name="site" placeholder="https://exemplo.com">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=estudios" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>