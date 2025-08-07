<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem adicionar impressoras.</div>';
    return;
}

$mensagem = "";
$sucesso = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $preco_aquisicao = str_replace(',', '.', $_POST['preco_aquisicao'] ?? '');
    $consumo = $_POST['consumo'] ?? '';
    $depreciacao = $_POST['depreciacao'] ?? '';
    $tempo_vida_util = $_POST['tempo_vida_util'] ?? '';
    $valor_alcool_limpeza = str_replace(',', '.', $_POST['valor_alcool_limpeza'] ?? '');

    if (
        !$marca ||
        !$modelo ||
        !in_array($tipo, ['FDM', 'Resina']) ||
        $preco_aquisicao === '' ||
        $consumo === '' ||
        $depreciacao === '' ||
        $tempo_vida_util === '' ||
        ($tipo === 'Resina' && $valor_alcool_limpeza === '')
    ) {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO impressoras (marca, modelo, tipo, preco_aquisicao, consumo, depreciacao, tempo_vida_util, valor_alcool_limpeza) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $marca,
            $modelo,
            $tipo,
            $preco_aquisicao,
            $consumo,
            $depreciacao,
            $tempo_vida_util,
            $tipo === 'Resina' ? $valor_alcool_limpeza : 0
        ]);
        $mensagem = "Impressora adicionada com sucesso!";
        $sucesso = true;
    }
    if ($sucesso) {
        header("Location: ?pagina=impressoras");
        exit;
    }
}
?>
<h2 class="mb-4">Adicionar Impressora</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
    <?php if ($sucesso): ?>
        <a href="?pagina=impressoras" class="btn btn-primary mb-3">Voltar para Impressoras</a>
    <?php endif; ?>
<?php endif; ?>
<?php if (!$sucesso): ?>
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
        <select name="tipo" class="form-select" id="tipo-impressora" required>
            <option value="">Selecione...</option>
            <option value="FDM" <?= (($_POST['tipo'] ?? '') == 'FDM') ? 'selected' : '' ?>>FDM</option>
            <option value="Resina" <?= (($_POST['tipo'] ?? '') == 'Resina') ? 'selected' : '' ?>>Resina</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Preço de aquisição (R$)</label>
        <input type="number" step="0.01" name="preco_aquisicao" class="form-control" required value="<?= htmlspecialchars($_POST['preco_aquisicao'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Consumo (Watts)</label>
        <input type="number" name="consumo" class="form-control" required value="<?= htmlspecialchars($_POST['consumo'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Depreciação (%/h)</label>
        <input type="number" name="depreciacao" class="form-control" required value="<?= htmlspecialchars($_POST['depreciacao'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tempo de Vida Útil (horas)</label>
        <input type="number" name="tempo_vida_util" class="form-control" required value="<?= htmlspecialchars($_POST['tempo_vida_util'] ?? '') ?>">
    </div>
    <div class="mb-3" id="campo-alcool" style="display:none;">
        <label class="form-label">Valor do álcool para limpeza (R$)</label>
        <input type="number" step="0.01" name="valor_alcool_limpeza" class="form-control" value="<?= htmlspecialchars($_POST['valor_alcool_limpeza'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-success">Adicionar</button>
    <a href="?pagina=impressoras" class="btn btn-secondary">Voltar</a>
</form>
<script>
document.getElementById('tipo-impressora').addEventListener('change', function() {
    var campoAlcool = document.getElementById('campo-alcool');
    if (this.value === 'Resina') {
        campoAlcool.style.display = 'block';
        campoAlcool.querySelector('input').required = true;
    } else {
        campoAlcool.style.display = 'none';
        campoAlcool.querySelector('input').required = false;
    }
});
if (document.getElementById('tipo-impressora').value === 'Resina') {
    var campoAlcool = document.getElementById('campo-alcool');
    campoAlcool.style.display = 'block';
    campoAlcool.querySelector('input').required = true;
}
</script>
<?php endif; ?>