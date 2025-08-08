<?php
require __DIR__ . '/../app/db.php';

// Garante que só usuários autenticados podem acessar
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar estudios.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca o registro do estudio do usuário
$stmt = $pdo->prepare("SELECT * FROM estudios WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$estudio = $stmt->fetch();

if (!$estudio) {
    echo '<div class="alert alert-danger">Registro não encontrado ou você não tem permissão para editar.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $site = trim($_POST['site'] ?? '');

    if (!$nome || !$site) {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE estudios SET nome = ?, site = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome, $site, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=estudios";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Estudio</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome do Estudio</label>
        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $estudio['nome']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Site</label>
        <input type="text" name="site" class="form-control" required value="<?= htmlspecialchars($_POST['site'] ?? $estudio['site']) ?>">
    </div>
    <button type="submit" class="btn btn-salvar">Salvar</button>
    <a href="?pagina=estudios" class="btn btn-voltar">Cancelar</a>
</form>