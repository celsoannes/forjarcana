<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para acessar esta ferramenta.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$id = $_GET['id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

if (!$id || !$tipo) {
    echo '<div class="alert alert-danger">Impressora não encontrada.</div>';
    return;
}

// Busca impressora
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$impressora = $stmt->fetch();

if (!$impressora) {
    echo '<div class="alert alert-danger">Impressora não encontrada.</div>';
    return;
}

// Busca materiais
if ($tipo === 'FDM') {
    $stmt = $pdo->prepare("SELECT id, nome, marca, cor, tipo, preco_kilo FROM filamento WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $materiais = $stmt->fetchAll();
    $label_material = 'Filamento';
    $campo_material = 'filamento_id';
    $campo_quantidade = 'peso';
    $label_quantidade = 'Peso (g)';
} elseif ($tipo === 'Resina') {
    $stmt = $pdo->prepare("SELECT id, nome, marca, cor, preco_litro FROM resinas WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $materiais = $stmt->fetchAll();
    $label_material = 'Resina';
    $campo_material = 'resina_id';
    $campo_quantidade = 'volume';
    $label_quantidade = 'Volume (ml)';
} else {
    echo '<div class="alert alert-danger">Tipo de impressora inválido.</div>';
    return;
}
?>

<h2 class="mb-4">Cálculo Detalhado</h2>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($impressora['marca']) ?> <?= htmlspecialchars($impressora['modelo']) ?></h5>
        <p class="card-text">
            <strong>Tipo:</strong> <?= $impressora['tipo'] ?><br>
            <strong>Preço de aquisição:</strong> R$ <?= number_format($impressora['preco_aquisicao'], 2, ',', '.') ?><br>
            <strong>Consumo:</strong> <?= $impressora['consumo'] ?> W<br>
            <strong>Depreciação:</strong> <?= $impressora['depreciacao'] ?> %<br>
            <strong>Tempo de vida útil:</strong> <?= $impressora['tempo_vida_util'] ?> horas<br>
            <strong>Custo hora:</strong> R$ <?= number_format($impressora['custo_hora'], 4, ',', '.') ?><br>
            <strong>Última atualização:</strong> <?= date('d/m/Y', strtotime($impressora['ultima_atualizacao'])) ?>
        </p>
    </div>
</div>

<form method="POST" id="form-calculo-detalhado">
    <div class="mb-3">
        <label class="form-label"><?= $label_material ?></label>
        <select name="<?= $campo_material ?>" class="form-select" required>
            <option value="">Selecione...</option>
            <?php foreach ($materiais as $mat): ?>
                <?php
                $selected = isset($_POST[$campo_material]) && $_POST[$campo_material] == $mat['id'] ? 'selected' : '';
                ?>
                <?php if ($tipo === 'FDM'): ?>
                    <option value="<?= $mat['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($mat['nome']) ?> - <?= htmlspecialchars($mat['marca']) ?>
                        <?= isset($mat['cor']) ? '(' . htmlspecialchars($mat['cor']) . ')' : '' ?>
                        <?= isset($mat['tipo']) ? ' [' . htmlspecialchars($mat['tipo']) . ']' : '' ?>
                        - R$ <?= number_format($mat['preco_kilo'], 2, ',', '.') ?>/kg
                    </option>
                <?php elseif ($tipo === 'Resina'): ?>
                    <option value="<?= $mat['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($mat['nome']) ?> - <?= htmlspecialchars($mat['marca']) ?>
                        <?= isset($mat['cor']) ? '(' . htmlspecialchars($mat['cor']) . ')' : '' ?>
                        - R$ <?= number_format($mat['preco_litro'], 2, ',', '.') ?>/litro
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= $label_quantidade ?></label>
        <input type="number" name="<?= $campo_quantidade ?>" class="form-control" min="0" step="0.01" required
            value="<?= htmlspecialchars($_POST[$campo_quantidade] ?? '') ?>">
    </div>
    <!-- Campo Tempo de impressão -->
    <div class="mb-3">
        <label class="form-label">Tempo de impressão</label>
        <div class="row">
            <div class="col">
                <input type="number" name="dias" class="form-control" min="0" placeholder="Dias"
                    value="<?= htmlspecialchars($_POST['dias'] ?? '') ?>">
            </div>
            <div class="col">
                <input type="number" name="horas" class="form-control" min="0" max="23" placeholder="Horas"
                    value="<?= htmlspecialchars($_POST['horas'] ?? '') ?>">
            </div>
            <div class="col">
                <input type="number" name="minutos" class="form-control" min="0" max="59" placeholder="Minutos"
                    value="<?= htmlspecialchars($_POST['minutos'] ?? '') ?>">
            </div>
        </div>
    </div>
    <!-- Campo Unidades produzidas -->
    <div class="mb-3">
        <label class="form-label">Unidades produzidas</label>
        <input type="number" name="unidades" class="form-control" min="1" required
            placeholder="Informe o número de peças idênticas que a impressão gerou. Se for apenas uma, coloque &quot;1&quot;."
            value="<?= htmlspecialchars($_POST['unidades'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Margem de lucro (%)</label>
        <input type="number" name="lucro" class="form-control" min="0" step="1" value="<?= htmlspecialchars($_POST['lucro'] ?? '500') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Taxa de falha (%)</label>
        <input type="number" name="taxa_falha" class="form-control" min="0" max="100" step="1" value="<?= htmlspecialchars($_POST['taxa_falha'] ?? '30') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Calcular</button>
    <button type="button" class="btn btn-secondary" id="btn-limpar">Limpar</button>
</form>

<script>
document.getElementById('btn-limpar').addEventListener('click', function() {
    const form = document.getElementById('form-calculo-detalhado');
    form.reset();

    // Limpa selects manualmente
    const selects = form.querySelectorAll('select');
    selects.forEach(function(select) {
        select.selectedIndex = 0;
    });

    // Limpa inputs manualmente
    const inputs = form.querySelectorAll('input');
    inputs.forEach(function(input) {
        if (input.type === 'number' || input.type === 'text') {
            input.value = '';
        }
    });

    // Limpa o resultado do cálculo, se existir
    const resultado = document.querySelector('.alert-success');
    if (resultado) resultado.remove();
});
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = $_POST[$campo_material];
    $quantidade = $_POST[$campo_quantidade];
    $lucro = $_POST['lucro'] / 100;
    $taxa_falha = $_POST['taxa_falha'] / 100;
    $dias = intval($_POST['dias'] ?? 0);
    $horas = intval($_POST['horas'] ?? 0);
    $minutos = intval($_POST['minutos'] ?? 0);

    // Busca material
    if ($tipo === 'FDM') {
        $stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$material_id, $usuario_id]);
        $material = $stmt->fetch();
    } elseif ($tipo === 'Resina') {
        $stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$material_id, $usuario_id]);
        $material = $stmt->fetch();
    }

    if (!$material) {
        echo '<div class="alert alert-danger">Material não encontrado.</div>';
        return;
    }

    // Cálculo do custo do material
    if ($tipo === 'FDM') {
        $custo_material = ($material['preco_kilo'] / 1000) * $quantidade;
        $custo_lavagem_alcool = 0;
    } elseif ($tipo === 'Resina') {
        $custo_material = ($material['preco_litro'] / 1000) * $quantidade;

        // Busca preço do litro de álcool
        $stmt = $pdo->prepare("SELECT preco_litro FROM alcool WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $preco_litro_alcool = $stmt->fetchColumn();

        // Calcula custo de lavagem com álcool (1 ml de álcool para cada 1 ml de resina)
        $custo_lavagem_alcool = ($preco_litro_alcool / 1000) * $quantidade;
    }

    // Cálculo do custo de energia
    // Tempo total em minutos
    $tempo_total_min = ($dias * 24 * 60) + ($horas * 60) + $minutos;

    // 1. Converte consumo de watts para kW
    $consumo_kw = $impressora['consumo'] / 1000;

    // 2. Converte tempo total de impressão para horas
    $tempo_total_horas = $tempo_total_min / 60;

    // 3. Busca valor do kWh (valor_wh da tabela energia é o valor do kWh)
    $stmt = $pdo->prepare("SELECT valor_wh FROM energia WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $valor_kwh = $stmt->fetchColumn();

    // 4. Calcula o custo de energia
    $custo_energia = $consumo_kw * $tempo_total_horas * $valor_kwh;

    // Cálculo da depreciação
    // custo_hora está em R$/hora, converte para R$/minuto
    $custo_hora_minuto = $impressora['custo_hora'] / 60;
    $depreciacao = $custo_hora_minuto * $tempo_total_min;

    // Exibe resultado
    echo '<div class="alert alert-success mt-4">';
    echo 'Tempo total de impressão: ' . $tempo_total_min . ' minutos<br>';
    echo 'Tempo total de impressão: ' . number_format($tempo_total_horas, 2, ',', '.') . ' horas<br>';
    echo 'Valor de energia (kWh): R$ ' . number_format($valor_kwh, 6, ',', '.');
    echo '<hr>';
    echo '<strong>Resultado do cálculo:</strong><br>';
    echo 'Custo do material: R$ ' . number_format($custo_material, 2, ',', '.') . '<br>';
    if ($tipo === 'Resina') {
        echo 'Custo de lavagem com álcool: R$ ' . number_format($custo_lavagem_alcool, 2, ',', '.') . '<br>';
    }
    echo 'Custo de energia: R$ ' . number_format($custo_energia, 2, ',', '.') . '<br>';
    echo 'Depreciação: R$ ' . number_format($depreciacao, 2, ',', '.') . '<br>';

    // Soma custo total (inclui lavagem se for resina)
    $custo_total = $custo_material + $custo_energia + $depreciacao + ($tipo === 'Resina' ? $custo_lavagem_alcool : 0);

    // Cálculo do custo por unidade
    $unidades = intval($_POST['unidades'] ?? 1);
    $custo_por_unidade = $unidades > 0 ? $custo_total / $unidades : 0;

    // Cálculo do preço de venda sugerido
    $preco_venda_sugerido = $custo_total * (1 + $taxa_falha) * (1 + $lucro);

    // Cálculo do lucro
    $lucro_final = $preco_venda_sugerido - $custo_total;
    $porcentagem_total = ($_POST['lucro'] ?? 500) + ($_POST['taxa_falha'] ?? 30);

    // Cálculo do preço de venda sugerido por unidade
    $preco_venda_sugerido_unidade = $unidades > 0 ? $preco_venda_sugerido / $unidades : 0;

    echo '<strong>Custo total da impressão: R$ ' . number_format($custo_total, 2, ',', '.') . '</strong><br>';
    echo '<strong>Custo por unidade: R$ ' . number_format($custo_por_unidade, 2, ',', '.') . '</strong><br>';
    echo '<hr>';
    echo '<strong>Lucro: R$ ' . number_format($lucro_final, 2, ',', '.') . ' (' . $porcentagem_total . '%)</strong><br>';
    echo '<strong>Preço de venda sugerido: R$ ' . number_format($preco_venda_sugerido, 2, ',', '.') . '</strong><br>';
    echo '<strong>Preço de venda sugerido por unidade: R$ ' . number_format($preco_venda_sugerido_unidade, 2, ',', '.') . '</strong><br>';
    echo '</div>';
}