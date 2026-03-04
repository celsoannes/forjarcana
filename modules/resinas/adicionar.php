<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Resinas\ResinaController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$resinaController = new ResinaController($pdo);
$dadosFormulario = $resinaController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $resinaController->processarFluxoAdicao((int) $usuario_id, $_POST);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=resinas";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Resina</h3>
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
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars((string) ($dadosFormulario['marca'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="cor">Cor</label>
        <?php $corSelecionada = (string) ($dadosFormulario['cor'] ?? ''); ?>
        <select class="form-control" id="cor" name="cor" required>
          <option value="">Selecione...</option>
          <option value="Branco" <?= $corSelecionada === 'Branco' ? 'selected' : '' ?>>Branco</option>
          <option value="Cinza" <?= $corSelecionada === 'Cinza' ? 'selected' : '' ?>>Cinza</option>
          <option value="Preto" <?= $corSelecionada === 'Preto' ? 'selected' : '' ?>>Preto</option>
          <option value="Transparente" <?= $corSelecionada === 'Transparente' ? 'selected' : '' ?>>Transparente</option>
        </select>
      </div>
      <div class="form-group">
        <label for="preco_litro">Preço por Litro (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_litro" name="preco_litro" required value="<?= htmlspecialchars((string) ($dadosFormulario['preco_litro'] ?? '')) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=resinas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>