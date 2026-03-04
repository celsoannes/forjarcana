<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Produtos\ProdutoController;

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$erro = '';

if ($usuario_id <= 0) {
    header('Location: /404.php');
    exit;
}

$produtoController = new ProdutoController($pdo);
$produto = $produtoController->buscarParaEdicao($id, $usuario_id);

if (!$produto) {
    header('Location: /404.php');
    exit;
}

$compatibilidade = (array) ($produto['__compatibilidade'] ?? []);
unset($produto['__compatibilidade']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $produtoController->processarEdicao($id, $usuario_id, $_POST, $compatibilidade);
    if (!empty($resultado['sucesso'])) {
      echo '<script>window.location.href="?pagina=produtos";</script>';
      exit;
    }

    $erro = (string) ($resultado['erro'] ?? 'Erro ao editar produto.');

    $produto['descricao'] = (string) ($_POST['descricao'] ?? ($produto['descricao'] ?? ''));
    $produto['observacoes'] = (string) ($_POST['observacoes'] ?? ($produto['observacoes'] ?? ''));
    $produto['custo_total'] = (string) ($_POST['custo_total'] ?? ($produto['custo_total'] ?? 0));
    $produto['custo_por_unidade'] = (string) ($_POST['custo_por_unidade'] ?? ($produto['custo_por_unidade'] ?? 0));
    $produto['markup_lojista'] = (string) ($_POST['markup_lojista'] ?? ($produto['markup_lojista'] ?? 0));
    $produto['markup_consumidor_final'] = (string) ($_POST['markup_consumidor_final'] ?? ($produto['markup_consumidor_final'] ?? 0));
    $produto['preco_lojista'] = (string) ($_POST['preco_lojista'] ?? ($produto['preco_lojista'] ?? 0));
    $produto['preco_consumidor_final'] = (string) ($_POST['preco_consumidor_final'] ?? ($produto['preco_consumidor_final'] ?? 0));
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Produto</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>SKU</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($produto['sku_codigo'] ?? '-')) ?>" disabled>
        </div>
        <div class="form-group col-md-4">
          <label>Nome</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($produto['miniatura_nome'] ?? 'Produto sem nome')) ?>" disabled>
        </div>
        <div class="form-group col-md-4">
          <label>Categoria</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($produto['categoria_nome'] ?? '-')) ?>" disabled>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-2">
          <label for="custo_total">Custo Total (R$)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="custo_total" name="custo_total" value="<?= htmlspecialchars((string) ($produto['custo_total'] ?? 0)) ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="custo_por_unidade">Custo por Unidade (R$)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="custo_por_unidade" name="custo_por_unidade" value="<?= htmlspecialchars((string) ($produto['custo_por_unidade'] ?? 0)) ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="markup_lojista">Markup Lojista</label>
          <input type="number" step="0.01" min="0" class="form-control" id="markup_lojista" name="markup_lojista" value="<?= htmlspecialchars((string) ($produto['markup_lojista'] ?? 0)) ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="markup_consumidor_final">Markup Final</label>
          <input type="number" step="0.01" min="0" class="form-control" id="markup_consumidor_final" name="markup_consumidor_final" value="<?= htmlspecialchars((string) ($produto['markup_consumidor_final'] ?? 0)) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="preco_lojista">Preço Lojista (R$)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="preco_lojista" name="preco_lojista" value="<?= htmlspecialchars((string) ($produto['preco_lojista'] ?? 0)) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="preco_consumidor_final">Preço Final (R$)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="preco_consumidor_final" name="preco_consumidor_final" value="<?= htmlspecialchars((string) ($produto['preco_consumidor_final'] ?? 0)) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars((string) ($produto['descricao'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars((string) ($produto['observacoes'] ?? '')) ?></textarea>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=produtos" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
