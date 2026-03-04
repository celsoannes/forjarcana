<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Impressoras3d\Impressora3dController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$impressoraController = new Impressora3dController($pdo);
$dadosFormulario = $impressoraController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $impressoraController->processarFluxoAdicao((int) $usuario_id, $_POST);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=impressoras3d";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Impressora 3D</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars((string) ($dadosFormulario['marca'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="modelo">Modelo</label>
        <input type="text" class="form-control" id="modelo" name="modelo" required value="<?= htmlspecialchars((string) ($dadosFormulario['modelo'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="tipo">Tipo</label>
        <?php $tipoSelecionado = (string) ($dadosFormulario['tipo'] ?? ''); ?>
        <select class="form-control" id="tipo" name="tipo" required>
          <option value="">Selecione...</option>
          <option value="FDM" <?= $tipoSelecionado === 'FDM' ? 'selected' : '' ?>>FDM</option>
          <option value="Resina" <?= $tipoSelecionado === 'Resina' ? 'selected' : '' ?>>Resina</option>
        </select>
      </div>
      <div class="form-group">
        <label for="preco_aquisicao">Preço de Aquisição (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_aquisicao" name="preco_aquisicao" required value="<?= htmlspecialchars((string) ($dadosFormulario['preco_aquisicao'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="potencia">Potência (W)</label>
        <input type="number" class="form-control" id="potencia" name="potencia" required value="<?= htmlspecialchars((string) ($dadosFormulario['potencia'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="depreciacao">Depreciação (%)</label>
        <input type="number" class="form-control" id="depreciacao" name="depreciacao" placeholder="25" required value="<?= htmlspecialchars((string) ($dadosFormulario['depreciacao'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="tempo_vida_util">Tempo Vida Útil (h)</label>
        <input type="number" class="form-control" id="tempo_vida_util" name="tempo_vida_util" required value="<?= htmlspecialchars((string) ($dadosFormulario['tempo_vida_util'] ?? '')) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=impressoras3d" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>