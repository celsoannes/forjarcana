<?php
require __DIR__ . '/../app/db.php';

$stmt = $pdo->query("SELECT id, valor_ultima_conta, energia_eletrica, valor_wh, ultima_atualizacao FROM energia ORDER BY ultima_atualizacao DESC");
$registros = $stmt->fetchAll();
?>
<h2 class="mb-4">Energia elétrica</h2>
<?php if (count($registros) === 0): ?>
    <a href="?pagina=adicionar_energia" class="btn btn-success mb-3">Adicionar custo de energia elétrica</a>
<?php endif; ?>

<table class="custom-table">
    <thead>
        <tr>
            <th>ID</th>
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
                <td><?= $r['id'] ?></td>
                <td><?= number_format($r['valor_ultima_conta'], 2, ',', '.') ?></td>
                <td><?= $r['energia_eletrica'] ?></td>
                <td><?= number_format($r['valor_wh'], 8, ',', '.') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['ultima_atualizacao'])) ?></td>
                <td>
                    <a href="?pagina=editar_energia&id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>