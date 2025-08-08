<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para acessar esta ferramenta.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca impressoras do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll();
?>

<h2 class="mb-4">Cálculo Rápido</h2>

<div class="row">
    <?php if (empty($impressoras)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center">Nenhuma impressora cadastrada.</div>
        </div>
    <?php else: ?>
        <?php foreach ($impressoras as $imp): ?>
            <div class="col-md-4 mb-3">
                <a href="?pagina=calculo_detalhado&id=<?= $imp['id'] ?>&tipo=<?= urlencode($imp['tipo']) ?>" style="text-decoration:none;">
                    <div class="card h-100 card-clickable">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($imp['marca']) ?> <?= htmlspecialchars($imp['modelo']) ?></h5>
                            <p class="card-text">
                                <strong>Tipo:</strong> <?= $imp['tipo'] ?><br>
                                <strong>Preço de aquisição:</strong> R$ <?= number_format($imp['preco_aquisicao'], 2, ',', '.') ?><br>
                                <strong>Consumo:</strong> <?= $imp['consumo'] ?> W<br>
                                <strong>Depreciação:</strong> <?= $imp['depreciacao'] ?> %<br>
                                <strong>Tempo de vida útil:</strong> <?= $imp['tempo_vida_util'] ?> horas<br>
                                <strong>Custo hora:</strong> R$ <?= number_format($imp['custo_hora'], 4, ',', '.') ?><br>
                                <strong>Última atualização:</strong> <?= date('d/m/Y', strtotime($imp['ultima_atualizacao'])) ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- CSS extra para cursor pointer no card -->
<style>
.card-clickable { cursor: pointer; }
.card-clickable:hover { box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4); }
</style>