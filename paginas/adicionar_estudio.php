<?php
require __DIR__ . '/../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $site = trim($_POST['site'] ?? '');

    if (!$nome || !$site) {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO estudios (nome, site, usuario_id) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $site, $usuario_id]);
        echo '<script>window.location.href="?pagina=estudios";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Estudio</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST">
    <div class="mb-3">
        <label class="form-label">Nome do Estudio *</label>
        <input type="text" name="nome" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Site *</label>
        <input type="text" name="site" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
    <a href="?pagina=estudios" class="btn btn-voltar">Cancelar</a>
</form>