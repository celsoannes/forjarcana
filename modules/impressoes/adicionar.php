<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca impressoras
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verifica se uma impressora foi escolhida
$impressora_id = isset($_GET['impressora_id']) ? intval($_GET['impressora_id']) : 0;
$impressora_escolhida = null;
if ($impressora_id) {
    foreach ($impressoras as $imp) {
        if ($imp['id'] == $impressora_id) {
            $impressora_escolhida = $imp;
            break;
        }
    }
}
?>

<?php if (!$impressora_escolhida): ?>
    <h5 class="mb-2">Escolha a impressora</h5>
    <div class="row">
      <?php if ($impressoras): ?>
        <?php foreach ($impressoras as $imp): ?>
          <div class="col-md-3">
            <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $imp['id'] ?>" style="text-decoration: none;">
              <div class="card card-primary card-hover" style="cursor:pointer;">
                <div class="card-header">
                  <h3 class="card-title"><?= htmlspecialchars($imp['marca'] . ' ' . $imp['modelo']) ?></h3>
                </div>
                <div class="card-body">
                  <strong>Tipo:</strong> <?= htmlspecialchars($imp['tipo']) ?>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center">Nenhuma impressora cadastrada.</div>
        </div>
      <?php endif; ?>
    </div>
<?php else: ?>
    <h5 class="mb-2">
      Impressora escolhida: <?= htmlspecialchars($impressora_escolhida['marca'] . ' ' . $impressora_escolhida['modelo']) ?>
    </h5>
    <?php if ($impressora_escolhida['tipo'] === 'Resina'): ?>
        <?php
        // Busca resinas do usuário
        $stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <h6 class="mb-2">Escolha a resina</h6>
        <div class="row">
          <?php if ($resinas): ?>
            <?php foreach ($resinas as $resina): ?>
              <div class="col-md-3">
                <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $impressora_escolhida['id'] ?>&resina_id=<?= $resina['id'] ?>" style="text-decoration: none;">
                  <div class="card card-success card-hover" style="cursor:pointer;">
                    <div class="card-header">
                      <h3 class="card-title"><?= htmlspecialchars($resina['nome']) ?></h3>
                    </div>
                    <div class="card-body">
                      <strong>Marca:</strong> <?= htmlspecialchars($resina['marca']) ?><br>
                      <strong>Cor:</strong>
                      <?php if (!empty($resina['cor'])): ?>
                        <i class="fas fa-circle nav-icon" style="color:<?= htmlspecialchars($resina['cor']) ?>; border:1px solid #ddd; border-radius:50%;"></i>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                      <br>
                      <strong>Preço/Litro:</strong> R$ <?= number_format($resina['preco_litro'], 2, ',', '.') ?>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-info text-center">Nenhuma resina cadastrada.</div>
            </div>
          <?php endif; ?>
        </div>
    <?php elseif ($impressora_escolhida['tipo'] === 'FDM'): ?>
        <?php
        // Busca filamentos do usuário
        $stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $filamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <h6 class="mb-2">Escolha o filamento</h6>
        <div class="row">
          <?php if ($filamentos): ?>
            <?php foreach ($filamentos as $filamento): ?>
              <div class="col-md-3">
                <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $impressora_escolhida['id'] ?>&filamento_id=<?= $filamento['id'] ?>" style="text-decoration: none;">
                  <div class="card card-info card-hover" style="cursor:pointer;">
                    <div class="card-header">
                      <h3 class="card-title"><?= htmlspecialchars($filamento['tipo'] . ' ' . $filamento['nome']) ?></h3>
                    </div>
                    <div class="card-body">
                      <strong>Marca:</strong> <?= htmlspecialchars($filamento['marca']) ?><br>
                      <strong>Cor:</strong>
                      <?php if (!empty($filamento['cor'])): ?>
                        <i class="fas fa-circle nav-icon" style="color:<?= htmlspecialchars($filamento['cor']) ?>; border:1px solid #ddd; border-radius:50%;"></i>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                      <br>
                      <strong>Preço/Kg:</strong> R$ <?= number_format($filamento['preco_kilo'], 2, ',', '.') ?>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-info text-center">Nenhum filamento cadastrado.</div>
            </div>
          <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.card-hover:hover {
  box-shadow: 0 0 0.5rem #007bff;
  border-color: #007bff;
}
</style>