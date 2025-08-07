<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para adicionar impressoras.</div>';
    return;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $preco_aquisicao = str_replace(',', '.', $_POST['preco_aquisicao'] ?? '');
    $consumo = intval($_POST['consumo'] ?? 0);
    $depreciacao = intval($_POST['depreciacao'] ?? 0);
    $tempo_vida_util = intval($_POST['tempo_vida_util'] ?? 0);
    $usuario_id = $_SESSION['usuario_id'];

    if (!$marca || !$modelo || !$tipo || $preco_aquisicao === '' || !$consumo || !$depreciacao || !$tempo_vida_util) {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO impressoras (marca, modelo, tipo, preco_aquisicao, consumo, depreciacao, tempo_vida_util, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$marca, $modelo, $tipo, $preco_aquisicao, $consumo, $depreciacao, $tempo_vida_util, $usuario_id]);
        echo '<script>window.location.href="?pagina=impressoras";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Impressora</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" required value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Modelo</label>
        <input type="text" name="modelo" class="form-control" required value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
            <option value="">Selecione...</option>
            <option value="FDM" <?= (($_POST['tipo'] ?? '') === 'FDM') ? 'selected' : '' ?>>FDM</option>
            <option value="Resina" <?= (($_POST['tipo'] ?? '') === 'Resina') ? 'selected' : '' ?>>Resina</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Preço de aquisição (R$)</label>
        <input type="number" step="0.01" name="preco_aquisicao" class="form-control" required value="<?= htmlspecialchars($_POST['preco_aquisicao'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Potência (W)</label>
        <input type="number" name="consumo" class="form-control" required value="<?= htmlspecialchars($_POST['consumo'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Depreciação (%)</label>
        <input type="number" name="depreciacao" class="form-control" required value="<?= htmlspecialchars($_POST['depreciacao'] ?? 200) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tempo de vida útil (horas)</label>
        <input type="number" name="tempo_vida_util" class="form-control" required value="<?= htmlspecialchars($_POST['tempo_vida_util'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
    <a href="?pagina=impressoras" class="btn btn-secondary">Cancelar</a>
</form>