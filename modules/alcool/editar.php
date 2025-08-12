<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? '';
$erro = '';

// Busca registro de álcool do usuário
$stmt = $pdo->prepare("SELECT * FROM alcool WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$alcool = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alcool) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || !$preco_litro) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE alcool SET nome = ?, marca = ?, preco_litro = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $marca, $preco_litro, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=alcool";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Álcool</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required placeholder="Isopropílico, Etanol, etc..." value="<?= htmlspecialchars($alcool['nome']) ?>">
      </div>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars($alcool['marca']) ?>">
      </div>
      <div class="form-group">
        <label for="preco_litro">Preço por Litro (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_litro" name="preco_litro" required value="<?= htmlspecialchars($alcool['preco_litro']) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=alcool" class="btn btn-secondary">Cancelar</a>
    </div>