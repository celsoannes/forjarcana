<?php
require __DIR__ . '/../app/db.php';

// Garante que só usuários autenticados podem acessar
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar álcool.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca o registro do usuário
$stmt = $pdo->prepare("SELECT * FROM alcool WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$alcool = $stmt->fetch();

if (!$alcool) {
    echo '<div class="alert alert-danger">Registro não encontrado ou você não tem permissão para editar.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || $preco_litro === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE alcool SET nome = ?, marca = ?, preco_litro = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome, $marca, $preco_litro, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=alcool";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Álcool para Lavagem</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required placeholder="Isopropílico, Etanol, 99%, etc..." value="<?= htmlspecialchars($_POST['nome'] ?? $alcool['nome']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? $alcool['marca']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Preço por litro (R$)</label>
        <input type="number" step="0.01" name="preco_litro" class="form-control" required value="<?= htmlspecialchars($_POST['preco_litro'] ?? $alcool['preco_litro']) ?>">
    </div>
    <button type="submit" class="btn btn-salvar">Salvar</button>
    <a href="?pagina=alcool" class="btn btn-