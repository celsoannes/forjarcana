<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca as impressoras do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h5 class="mb-2">Escolha a impressora</h5>
<div class="row">
  <?php if ($impressoras): ?>
    <?php foreach ($impressoras as $imp): ?>
      <div class="col-md-3">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"><?= htmlspecialchars($imp['marca'] . ' ' . $imp['modelo']) ?></h3>
          </div>
          <div class="card-body">
            <strong>Tipo:</strong> <?= htmlspecialchars($imp['tipo']) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12">
      <div class="alert alert-info text-center">Nenhuma impressora cadastrada.</div>
    </div>
  <?php endif;