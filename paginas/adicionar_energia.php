<?php
require __DIR__ . '/../app/db.php';

// Garante que só usuários autenticados podem acessar
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para adicionar custos de energia.</div>';
    return;
}

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor_ultima_conta = str_replace(',', '.', $_POST['valor_ultima_conta'] ?? '');
    $energia_eletrica = $_POST['energia_eletrica'] ?? '';
    $usuario_id = $_SESSION['usuario_id'];

    if ($valor_ultima_conta === '' || $energia_eletrica === '') {
        $mensagem = "Preencha todos os campos!";
    } else {
        // Se já existe registro, faz update. Senão, faz insert.
        $stmt = $pdo->prepare("SELECT id FROM energia WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE energia SET valor_ultima_conta = ?, energia_eletrica = ? WHERE usuario_id = ?");
            $stmt->execute([$valor_ultima_conta, $energia_eletrica, $usuario_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO energia (valor_ultima_conta, energia_eletrica, usuario_id) VALUES (?, ?, ?)");
            $stmt->execute([$valor_ultima_conta, $energia_eletrica, $usuario_id]);
        }
        echo '<script>window.location.href="?pagina=energia";</script>';
        exit;
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
    <button type="submit" class="btn btn-salvar">Salvar</button>
    <a href="?pagina=energia" class="btn btn-voltar">Voltar</a>
</form>