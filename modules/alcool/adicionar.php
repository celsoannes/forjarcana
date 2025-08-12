<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || !$preco_litro) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO alcool (usuario_id, nome, marca, preco_litro) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $nome, $marca, $preco_litro]);
            echo '<script>window.location.href="?pagina=alcool";</script>';
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Já existe um registro de álcool para este usuário.';
            } else {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Álcool</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required placeholder="Isopropílico, Etanol, etc...">
      </div>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required>
      </div>
      <div class="form-group">
        <label for="preco_litro">Preço por Litro (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_litro" name="preco_litro" required>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=alcool" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>