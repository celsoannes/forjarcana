<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Energia\EnergiaController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$energiaController = new EnergiaController($pdo);
$dadosFormulario = $energiaController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $energiaController->processarFluxoAdicao((int) $usuario_id, $_POST);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=energia";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Energia</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="prestadora">Prestadora</label>
        <input type="text" class="form-control" id="prestadora" name="prestadora" required value="<?= htmlspecialchars((string) ($dadosFormulario['prestadora'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="valor_ultima_conta">Valor da Última Conta (R$)</label>
        <input type="number" step="0.01" class="form-control" id="valor_ultima_conta" name="valor_ultima_conta" required value="<?= htmlspecialchars((string) ($dadosFormulario['valor_ultima_conta'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="energia_eletrica">Energia Elétrica (kWh)</label>
        <input type="number" class="form-control" id="energia_eletrica" name="energia_eletrica" required value="<?= htmlspecialchars((string) ($dadosFormulario['energia_eletrica'] ?? '')) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=energia" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<!-- /.card -->