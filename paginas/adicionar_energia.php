<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem adicionar custos de energia.</div>';
    return;
}

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor_ultima_conta = str_replace(',', '.', $_POST['valor_ultima_conta'] ?? '');
    $energia_eletrica = $_POST['energia_eletrica'] ?? '';

    if ($valor_ultima_conta === '' || $energia_eletrica === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO energia (valor_ultima_conta, energia_eletrica) VALUES (?, ?)");
        $stmt->execute([$valor_ultima_conta, $energia_eletrica]);
        $mensagem = "Registro de energia adicionado com sucesso!";
    }
}
?>
<h2 class="mb-4">Adicionar custo de energia elétrica</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Valor da última conta (R$)</label>
        <input type="number" step="0.01" name="valor_ultima_conta" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Energia elétrica consumida (kWh)</label>
        <input type="number" name="energia_eletrica" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Salvar</button>
    <a href="?pagina=energia" class="btn btn-secondary">Voltar</a>
</form>