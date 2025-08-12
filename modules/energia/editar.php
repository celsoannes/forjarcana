<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;
$erro = '';
$sucesso = '';

// Busca o registro atual
$stmt = $pdo->prepare("SELECT * FROM energia WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$energia = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrou ou não pertence ao usuário autenticado, redireciona para 404
if (!$energia) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prestadora = $_POST['prestadora'] ?? '';
    $valor_ultima_conta = str_replace(',', '.', $_POST['valor_ultima_conta'] ?? '');
    $energia_eletrica = $_POST['energia_eletrica'] ?? '';

    if (!$prestadora || !$valor_ultima_conta || !$energia_eletrica) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE energia SET prestadora = ?, valor_ultima_conta = ?, energia_eletrica = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$prestadora, $valor_ultima_conta, $energia_eletrica, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=energia";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Energia</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="prestadora">Prestadora</label>
        <input type="text" class="form-control" id="prestadora" name="prestadora" value="<?= htmlspecialchars($energia['prestadora']) ?>" required>
      </div>
      <div class="form-group">
        <label for="valor_ultima_conta">Valor da Última Conta (R$)</label>
        <input type="number" step="0.01" class="form-control" id="valor_ultima_conta" name="valor_ultima_conta" value="<?= htmlspecialchars($energia['valor_ultima_conta']) ?>" required>
      </div>
      <div class="form-group">
        <label for="energia_eletrica">Energia Elétrica (kWh)</label>
        <input type="number" class="form-control" id="energia_eletrica" name="energia_eletrica" value="<?= htmlspecialchars($energia['energia_eletrica']) ?>" required>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=energia" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<!-- /.card -->