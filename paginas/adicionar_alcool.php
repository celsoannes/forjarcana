<?php
require __DIR__ . '/../app/db.php';

// Garante que só usuários autenticados podem acessar
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para adicionar álcool.</div>';
    return;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if (!$nome || !$marca || $preco_litro === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        // Se já existe registro, faz update. Senão, faz insert.
        $stmt = $pdo->prepare("SELECT id FROM alcool WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE alcool SET nome = ?, marca = ?, preco_litro = ? WHERE usuario_id = ?");
            $stmt->execute([$nome, $marca, $preco_litro, $usuario_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO alcool (nome, marca, preco_litro, usuario_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $marca, $preco_litro, $usuario_id]);
        }
        echo '<script>window.location.href="?pagina=alcool";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Álcool para Lavagem</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required placeholder="Isopropílico, Etanol, 99%, etc..." value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Preço por litro (R$)</label>
        <input type="number" step="0.01" name="preco_litro" class="form-control" required value="<?= htmlspecialchars($_POST['preco_litro'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
    <a href="?pagina=alcool" class="btn btn-secondary">Cancelar</a>
</form>