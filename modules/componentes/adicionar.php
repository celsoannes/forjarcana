<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Componentes\ComponenteController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = $_SESSION['usuario_uuid'] ?? '';
$erro = '';

$componenteController = new ComponenteController($pdo);
$dadosFormulario = $componenteController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $componenteController->processarFluxoAdicao((int) $usuario_id, (string) $usuario_uuid, $_POST, $_FILES);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=componentes";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Componente</h3>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome_material">Nome do Material</label>
        <input type="text" class="form-control" id="nome_material" name="nome_material" required value="<?= htmlspecialchars((string) ($dadosFormulario['nome_material'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="tipo_material">Tipo do Material</label>
        <input type="text" class="form-control" id="tipo_material" name="tipo_material" required value="<?= htmlspecialchars((string) ($dadosFormulario['tipo_material'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao"><?= htmlspecialchars((string) ($dadosFormulario['descricao'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label for="unidade_medida">Unidade de Medida</label>
        <?php $unidadeSelecionada = (string) ($dadosFormulario['unidade_medida'] ?? ''); ?>
        <select class="form-control" id="unidade_medida" name="unidade_medida" required>
          <option value="">Selecione</option>
          <option value="un" <?= $unidadeSelecionada === 'un' ? 'selected' : '' ?>>Unidade</option>
          <option value="m" <?= $unidadeSelecionada === 'm' ? 'selected' : '' ?>>Metro</option>
          <option value="cm" <?= $unidadeSelecionada === 'cm' ? 'selected' : '' ?>>Centímetro</option>
          <option value="mm" <?= $unidadeSelecionada === 'mm' ? 'selected' : '' ?>>Milímetro</option>
          <option value="kg" <?= $unidadeSelecionada === 'kg' ? 'selected' : '' ?>>Quilograma</option>
          <option value="g" <?= $unidadeSelecionada === 'g' ? 'selected' : '' ?>>Grama</option>
          <option value="L" <?= $unidadeSelecionada === 'L' ? 'selected' : '' ?>>Litro</option>
          <option value="mL" <?= $unidadeSelecionada === 'mL' ? 'selected' : '' ?>>Mililitro</option>
        </select>
      </div>
      <div class="form-group">
        <label for="valor_unitario">Valor Unitário (R$)</label>
        <input type="number" step="0.01" class="form-control" id="valor_unitario" name="valor_unitario" required value="<?= htmlspecialchars((string) ($dadosFormulario['valor_unitario'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="fornecedor">Fornecedor</label>
        <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?= htmlspecialchars((string) ($dadosFormulario['fornecedor'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes"><?= htmlspecialchars((string) ($dadosFormulario['observacoes'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label for="imagem">Imagem do Componente</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="imagem" name="imagem" accept="image/png,image/jpeg,image/webp,image/gif">
          <label class="custom-file-label" for="imagem">Selecione uma imagem</label>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=componentes" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(document).ready(function () {
    bsCustomFileInput.init();
  });
</script>