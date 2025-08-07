<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra o registro do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar seus dados de álcool.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT id, nome, marca, preco_litro, ultima_atualizacao FROM alcool WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$registros = $stmt->fetchAll();
?>
<h2 class="mb-4">Álcool para Lavagem</h2>

<?php if (empty($registros)): ?>
    <a href="?pagina=alcool&acao=adicionar" class="btn btn-adicionar mb-3">Adicionar Álcool</a>
    <div class="alert alert-info text-center">Nenhum registro de álcool cadastrado.</div>
<?php else: ?>
    <table class="custom-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Marca</th>
                <th>Preço por litro (R$)</th>
                <th>Última atualização</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nome']) ?></td>
                    <td><?= htmlspecialchars($r['marca']) ?></td>
                    <td><?= number_format($r['preco_litro'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($r['ultima_atualizacao']) ?></td>
                    <td>
                        <a href="?pagina=alcool&acao=editar&id=<?= $r['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;