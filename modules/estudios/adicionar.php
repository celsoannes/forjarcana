<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Estudios\EstudioController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$estudioController = new EstudioController($pdo);
$dadosFormulario = $estudioController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $estudioController->processarFluxoAdicao((int) $usuario_id, $_POST);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=estudios";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
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
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars((string) ($dadosFormulario['nome'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="site">Site</label>
        <input type="url" class="form-control" id="site" name="site" placeholder="https://exemplo.com" value="<?= htmlspecialchars((string) ($dadosFormulario['site'] ?? '')) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=estudios" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>