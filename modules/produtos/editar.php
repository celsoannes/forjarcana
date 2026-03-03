<?php
require_once __DIR__ . '/../../app/db.php';

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$erro = '';

if ($usuario_id <= 0) {
    header('Location: /404.php');
    exit;
}

$stmt = $pdo->prepare("SELECT
    p.id,
    p.descricao,
    p.observacoes,
    p.markup_lojista,
    p.markup_consumidor_final,
    p.preco_lojista,
    p.preco_consumidor_final,
    p.imagem_capa,
    c.custo_total,
    c.custo_por_unidade,
    s.sku AS sku_codigo,
    cg.nome AS categoria_nome,
    m.nome_original AS miniatura_nome
FROM produtos p
LEFT JOIN sku s ON s.produto_id = p.id
LEFT JOIN categorias cg ON cg.id = p.categoria
LEFT JOIN custos c ON c.produto_id = p.id
LEFT JOIN miniaturas m ON m.produto_id = p.id
WHERE p.id = ? AND p.usuario_id = ?
LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $custo_total = (float) str_replace(',', '.', trim($_POST['custo_total'] ?? '0'));
    $custo_por_unidade = (float) str_replace(',', '.', trim($_POST['custo_por_unidade'] ?? '0'));
    $markup_lojista = (float) str_replace(',', '.', trim($_POST['markup_lojista'] ?? '0'));
    $markup_consumidor_final = (float) str_replace(',', '.', trim($_POST['markup_consumidor_final'] ?? '0'));
    $preco_lojista = (float) str_replace(',', '.', trim($_POST['preco_lojista'] ?? '0'));
    $preco_consumidor_final = (float) str_replace(',', '.', trim($_POST['preco_consumidor_final'] ?? '0'));

    if ($custo_total < 0 || $custo_por_unidade < 0 || $markup_lojista < 0 || $markup_consumidor_final < 0 || $preco_lojista < 0 || $preco_consumidor_final < 0) {
        $erro = 'Informe valores numéricos válidos (maiores ou iguais a zero).';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtCusto = $pdo->prepare("UPDATE custos
              SET custo_total = ?,
                custo_por_unidade = ?
              WHERE produto_id = ?");
            $stmtCusto->execute([$custo_total, $custo_por_unidade, $id]);

            if ($stmtCusto->rowCount() < 1) {
              $stmtInsertCusto = $pdo->prepare("INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)");
              $stmtInsertCusto->execute([$id, $custo_total, $custo_por_unidade]);
            }

            $stmtProduto = $pdo->prepare("UPDATE produtos
              SET descricao = ?,
                observacoes = ?,
                markup_lojista = ?,
                markup_consumidor_final = ?,
                preco_lojista = ?,
                preco_consumidor_final = ?
              WHERE id = ? AND usuario_id = ?");
            $stmtProduto->execute([
                $descricao !== '' ? $descricao : null,
                $observacoes !== '' ? $observacoes : null,
                $markup_lojista,
                $markup_consumidor_final,
                $preco_lojista,
                $preco_consumidor_final,
                $id,
                $usuario_id,
            ]);

            $pdo->commit();
            echo '<script>window.location.href="?pagina=produtos";</script>';
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Erro ao editar produto: ' . $e->getMessage();
        }
    }
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
