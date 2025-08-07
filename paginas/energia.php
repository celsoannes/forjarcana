<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra o registro do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar seus dados de energia.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT id, valor_ultima_conta, energia_eletrica, valor_wh, ultima_atualizacao FROM energia WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$registros = $stmt->fetchAll();
?>
<h2 class="mb-4">Energia elétrica</h2>

<?php if (empty($registros)): ?>
    <a href="?pagina=adicionar_energia" class="btn btn-adicionar mb-3">Adicionar custo de energia elétrica</a>
    <div class="alert alert-info text-center">Nenhum registro de energia elétrica cadastrado.</div>
<?php else: ?>
    <table class="custom-table">
        <thead>
            <tr>
                <th>Valor da última conta (R$)</th>
                <th>Energia elétrica (kWh)</th>
                <th>Valor do Wh (R$)</th>
                <th>Última atualização</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= number_format($r['valor_ultima_conta'], 2, ',', '.') ?></td>
                    <td><?= $r['energia_eletrica'] ?></td>
                    <td><?= number_format($r['valor_wh'], 8, ',', '.') ?></td>
                    <td><?= htmlspecialchars($r['ultima_atualizacao']) ?></td>
                    <td>
                        <a href="?pagina=editar_energia&id=<?= $r['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>