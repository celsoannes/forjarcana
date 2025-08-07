<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar impressoras.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca impressora do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$imp = $stmt->fetch();

if (!$imp) {
    echo '<div class="alert alert-danger">Impressora não encontrada ou você não tem permissão para editar.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $preco_aquisicao = str_replace(',', '.', $_POST['preco_aquisicao'] ?? '');
    $consumo = intval($_POST['consumo'] ?? 0);
    $depreciacao = intval($_POST['depreciacao'] ?? 0);
    $tempo_vida_util = intval($_POST['tempo_vida_util'] ?? 0);

    if (!$marca || !$modelo || !$tipo || $preco_aquisicao === '' || !$consumo || !$depreciacao || !$tempo_vida_util) {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE impressoras SET marca = ?, modelo = ?, tipo = ?, preco_aquisicao = ?, consumo = ?, depreciacao = ?, tempo_vida_util = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$marca, $modelo, $tipo, $preco_aquisicao, $consumo, $depreciacao, $tempo_vida_util, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=impressoras";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Impressora</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? $imp['marca']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Modelo</label>
        <input type="text" name="modelo" class="form-control" required value="<?= htmlspecialchars($_POST['modelo'] ?? $imp['modelo']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
            <option value="">Selecione...</option>
            <option value="FDM" <?= ((($_POST['tipo'] ?? $imp['tipo']) === 'FDM') ? 'selected' : '') ?>>FDM</option>
            <option value="Resina" <?= ((($_POST['tipo'] ?? $imp['tipo']) === 'Resina') ? 'selected' : '') ?>>Resina</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Preço de aquisição (R$)</label>
        <input type="number" step="0.01" name="preco_aquisicao" class="form-control" required value="<?= htmlspecialchars($_POST['preco_aquisicao'] ?? $imp['preco_aquisicao']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Potência (W)</label>
        <input type="number" name="consumo" class="form-control" required value="<?= htmlspecialchars($_POST['consumo'] ?? $imp['consumo']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Depreciação (%)</label>
        <input type="number" name="depreciacao" class="form-control" required placeholder="200" value="<?= htmlspecialchars($_POST['depreciacao'] ?? $imp['depreciacao']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tempo de vida útil (horas)</label>
        <input type="number" name="tempo_vida_util" class="form-control" required value="<?= htmlspecialchars($_POST['tempo_vida_util'] ?? $imp['tempo_vida_util']) ?>">
    </div>
    <button type="submit" class="btn btn-salvar">Salvar</button>
    <a href="?pagina=impressoras" class="btn btn-cancelar">Cancelar</a>
</form>