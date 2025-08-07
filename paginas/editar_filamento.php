<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar filamentos.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca os dados atuais do filamento
$stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$filamento = $stmt->fetch();

if (!$filamento) {
    echo '<div class="alert alert-danger">Filamento não encontrado ou você não tem permissão para editar.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $cor = trim($_POST['cor'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $preco_kilo = str_replace(',', '.', $_POST['preco_kilo'] ?? '');

    if (!$nome || !$marca || !$cor || !$tipo || $preco_kilo === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE filamento SET nome = ?, marca = ?, cor = ?, tipo = ?, preco_kilo = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$nome, $marca, $cor, $tipo, $preco_kilo, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=filamentos";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Filamento</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $filamento['nome']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? $filamento['marca']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Cor</label>
        <input type="color" name="cor" class="form-control form-control-color" required value="<?= htmlspecialchars($_POST['cor'] ?? $filamento['cor']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
            <option value="">Selecione...</option>
            <option value="ABS" <?= (($filamento['tipo'] ?? '') === 'ABS' || ($_POST['tipo'] ?? '') === 'ABS') ? 'selected' : '' ?>>ABS</option>
            <option value="PLA" <?= (($filamento['tipo'] ?? '') === 'PLA' || ($_POST['tipo'] ?? '') === 'PLA') ? 'selected' : '' ?>>PLA</option>
            <option value="PET-G" <?= (($filamento['tipo'] ?? '') === 'PET-G' || ($_POST['tipo'] ?? '') === 'PET-G') ? 'selected' : '' ?>>PET-G</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Preço por quilo (R$)</label>
        <input type="number" step="0.01" name="preco_kilo" class="form-control" required value="<?= htmlspecialchars($_POST['preco_kilo'] ?? $filamento['preco_kilo']) ?>">
    </div>
    <button type="submit" class="btn btn-salvar">Salvar Alterações</button>
    <a href="?pagina=filamentos" class="btn btn-voltar">Voltar</a>