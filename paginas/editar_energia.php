<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem editar custos de energia.</div>';
    return;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">Registro não encontrado.</div>';
    return;
}

$stmt = $pdo->prepare("SELECT id, valor_ultima_conta, energia_eletrica FROM energia WHERE id = ?");
$stmt->execute([$id]);
$registro = $stmt->fetch();

if (!$registro) {
    echo '<div class="alert alert-danger">Registro não encontrado.</div>';
    return;
}

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor_ultima_conta = str_replace(',', '.', $_POST['valor_ultima_conta'] ?? '');
    $energia_eletrica = $_POST['energia_eletrica'] ?? '';

    if ($valor_ultima_conta === '' || $energia_eletrica === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        $stmt = $pdo->prepare("UPDATE energia SET valor_ultima_conta = ?, energia_eletrica = ? WHERE id = ?");
        $stmt->execute([$valor_ultima_conta, $energia_eletrica, $id]);
        $mensagem = "Registro atualizado com sucesso!";
        // Atualiza os dados exibidos
        $registro['valor_ultima_conta'] = $valor_ultima_conta;
        $registro['energia_eletrica'] = $energia_eletrica;
    }
}
?>
<h2 class="mb-4">Editar custo de energia elétrica</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Valor da última conta (R$)</label>
        <input type="number" step="0.01" name="valor_ultima_conta" class="form-control" value="<?= htmlspecialchars($registro['valor_ultima_conta']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Energia elétrica consumida (kWh)</label>
        <input type="number" name="energia_eletrica" class="form-control" value="<?= htmlspecialchars($registro['energia_eletrica']) ?>" required>
    </div>
    <button type="submit" class="btn btn-salvar">Salvar</button>
    <a href="?pagina=energia" class="btn btn-voltar">Voltar</a>
</form>