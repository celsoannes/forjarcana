<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Torres\TorreController;

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$erro = '';

if ($usuario_id <= 0 || $id <= 0) {
    header('Location: /404.php');
    exit;
}

$torreController = new TorreController($pdo);
$torre = $torreController->buscarParaEdicao($id, $usuario_id);

if (!$torre) {
    header('Location: /404.php');
    exit;
}

$compatibilidade = (array) ($torre['__compatibilidade'] ?? []);
unset($torre['__compatibilidade']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $torreController->processarEdicao($id, $usuario_id, (int) ($torre['produto_id'] ?? 0), $_POST, $compatibilidade);
    if (!empty($resultado['sucesso'])) {
      echo '<script>window.location.href="?pagina=torres";</script>';
      exit;
    }

    $erro = (string) ($resultado['erro'] ?? 'Erro ao editar torre.');

    $torre['nome'] = (string) ($_POST['nome'] ?? ($torre['nome'] ?? ''));
    $torre['nome_original'] = (string) ($_POST['nome_original'] ?? ($torre['nome_original'] ?? ''));
    $torre['descricao'] = (string) ($_POST['descricao'] ?? ($torre['descricao'] ?? ''));
    $torre['observacoes'] = (string) ($_POST['observacoes'] ?? ($torre['observacoes'] ?? ''));
    $torre['markup_lojista'] = (string) ($_POST['markup_lojista'] ?? ($torre['markup_lojista'] ?? 0));
    $torre['markup_consumidor_final'] = (string) ($_POST['markup_consumidor_final'] ?? ($torre['markup_consumidor_final'] ?? 0));
    $torre['preco_lojista'] = (string) ($_POST['preco_lojista'] ?? ($torre['preco_lojista'] ?? 0));
    $torre['preco_consumidor_final'] = (string) ($_POST['preco_consumidor_final'] ?? ($torre['preco_consumidor_final'] ?? 0));
}
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Torre de Dados</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="nome">Nome *</label>
          <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? $torre['nome'] ?? '') ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="nome_original">Nome Original</label>
          <input type="text" class="form-control" id="nome_original" name="nome_original" value="<?= htmlspecialchars($_POST['nome_original'] ?? $torre['nome_original'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <label for="markup_lojista">Markup Lojista</label>
          <input type="number" step="0.01" min="0" class="form-control" id="markup_lojista" name="markup_lojista" value="<?= htmlspecialchars($_POST['markup_lojista'] ?? $torre['markup_lojista'] ?? 0) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="markup_consumidor_final">Markup Final</label>
          <input type="number" step="0.01" min="0" class="form-control" id="markup_consumidor_final" name="markup_consumidor_final" value="<?= htmlspecialchars($_POST['markup_consumidor_final'] ?? $torre['markup_consumidor_final'] ?? 0) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="preco_lojista">Preço Lojista</label>
          <input type="number" step="0.01" min="0" class="form-control" id="preco_lojista" name="preco_lojista" value="<?= htmlspecialchars($_POST['preco_lojista'] ?? $torre['preco_lojista'] ?? 0) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="preco_consumidor_final">Preço Final</label>
          <input type="number" step="0.01" min="0" class="form-control" id="preco_consumidor_final" name="preco_consumidor_final" value="<?= htmlspecialchars($_POST['preco_consumidor_final'] ?? $torre['preco_consumidor_final'] ?? 0) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($_POST['descricao'] ?? $torre['descricao'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($_POST['observacoes'] ?? $torre['observacoes'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=torres" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
