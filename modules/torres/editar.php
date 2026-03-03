<?php
require_once __DIR__ . '/../../app/db.php';

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$erro = '';

if ($usuario_id <= 0 || $id <= 0) {
    header('Location: /404.php');
    exit;
}

$stmt = $pdo->prepare("SELECT
    t.id,
    t.produto_id,
    t.nome_original,
    p.nome,
    p.descricao,
    p.observacoes,
    p.markup_lojista,
    p.markup_consumidor_final,
    p.preco_lojista,
    p.preco_consumidor_final
FROM torres t
INNER JOIN produtos p ON p.id = t.produto_id
WHERE t.id = ? AND t.usuario_id = ?
LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$torre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$torre) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $nome_original = trim($_POST['nome_original'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $markup_lojista = (float) str_replace(',', '.', trim($_POST['markup_lojista'] ?? '0'));
    $markup_consumidor_final = (float) str_replace(',', '.', trim($_POST['markup_consumidor_final'] ?? '0'));
    $preco_lojista = (float) str_replace(',', '.', trim($_POST['preco_lojista'] ?? '0'));
    $preco_consumidor_final = (float) str_replace(',', '.', trim($_POST['preco_consumidor_final'] ?? '0'));

    if ($nome === '') {
        $erro = 'Preencha o nome da torre.';
    } elseif ($markup_lojista < 0 || $markup_consumidor_final < 0 || $preco_lojista < 0 || $preco_consumidor_final < 0) {
        $erro = 'Informe valores numéricos válidos (maiores ou iguais a zero).';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtTorre = $pdo->prepare("UPDATE torres SET nome_original = ? WHERE id = ? AND usuario_id = ?");
            $stmtTorre->execute([
                $nome_original !== '' ? $nome_original : null,
                $id,
                $usuario_id,
            ]);

            $stmtProduto = $pdo->prepare("UPDATE produtos
                SET nome = ?,
                    descricao = ?,
                    observacoes = ?,
                    markup_lojista = ?,
                    markup_consumidor_final = ?,
                    preco_lojista = ?,
                    preco_consumidor_final = ?
                WHERE id = ? AND usuario_id = ?");
            $stmtProduto->execute([
                $nome,
                $descricao !== '' ? $descricao : null,
                $observacoes !== '' ? $observacoes : null,
                $markup_lojista,
                $markup_consumidor_final,
                $preco_lojista,
                $preco_consumidor_final,
                (int) $torre['produto_id'],
                $usuario_id,
            ]);

            $pdo->commit();
            echo '<script>window.location.href="?pagina=torres";</script>';
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Erro ao editar torre: ' . $e->getMessage();
        }
    }
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
