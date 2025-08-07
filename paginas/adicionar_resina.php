<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para adicionar resinas.</div>';
    return;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $cor = trim($_POST['cor'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if (!$nome || !$marca || !$cor || $preco_litro === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO resinas (nome, marca, cor, preco_litro, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $marca, $cor, $preco_litro, $usuario_id]);
        echo '<script>window.location.href="?pagina=resina";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Resina</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Cor</label>
        <input type="color" name="cor" class="form-control form-control-color" required value="<?= htmlspecialchars($_POST['cor'] ?? '#ffffff') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Preço por litro (R$)</label>
        <input type="number" step="0.01" name="preco_litro" class="form-control" required value="<?= htmlspecialchars($_POST['preco_litro'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-success">Adicionar</button>
    <a href="?pagina=resina" class="btn btn-secondary">Voltar</a>
</form>