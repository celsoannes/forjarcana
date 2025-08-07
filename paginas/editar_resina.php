<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar resinas.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca os dados atuais da resina
$stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$resina = $stmt->fetch();

if (!$resina) {
    echo '<div class="alert alert-danger">Resina não encontrada ou você não tem permissão para editar.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $cor = trim($_POST['cor'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || !$cor || $preco_litro === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE resinas SET nome = ?, marca = ?, cor = ?, preco_litro = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome, $marca, $cor, $preco_litro, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=resinas";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Resina</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $resina['nome']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? $resina['marca']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Cor</label>
        <input type="color" name="cor" class="form-control form-control-color" required value="<?= htmlspecialchars($_POST['cor'] ?? $resina['cor']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Preço por litro (R$)</label>
        <input type="number" step="0.01" name="preco_litro" class="form-control" required value="<?= htmlspecialchars($_POST['preco_litro'] ?? $resina['preco_litro']) ?>">
    </div>
    <button type="submit" class="btn btn-salvar">Salvar Alterações</button>
    <a href="?pagina=resinas" class="btn btn-voltar">Voltar</a>
</form>