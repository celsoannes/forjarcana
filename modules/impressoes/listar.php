<?php
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$impressora_id = isset($_GET['impressora_id']) ? (int) $_GET['impressora_id'] : 0;
$fluxo = $_GET['fluxo'] ?? '';
$fluxo_miniaturas = ($fluxo === 'miniaturas');
$fluxo_torres = ($fluxo === 'torres');
$fluxo_produtos = $fluxo_miniaturas || $fluxo_torres;

$stmt = $pdo->prepare("SELECT id, marca, modelo, tipo, depreciacao, custo_hora FROM impressoras WHERE usuario_id = ? ORDER BY marca, modelo");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$impressoraSelecionada = null;
if ($impressora_id > 0) {
  foreach ($impressoras as $impressora) {
    if ((int) $impressora['id'] === $impressora_id) {
      $impressoraSelecionada = $impressora;
      break;
    }
  }
}
?>

<?php if (!$impressoraSelecionada): ?>
  <h4 class="mb-3"><?= $fluxo_miniaturas ? 'Selecione a Impressora para Miniaturas' : ($fluxo_torres ? 'Selecione a Impressora para Torres de Dados' : 'Impressoras') ?></h4>

  <?php if ($impressoras): ?>
    <div class="impressoes-grid">
      <?php foreach ($impressoras as $impressora): ?>
        <?php
          $cardLink = '?pagina=impressoes&impressora_id=' . (int) $impressora['id'] . ($fluxo_produtos ? '&fluxo=' . urlencode($fluxo) : '');
        ?>
        <a href="<?= $cardLink ?>" class="impressao-card impressao-card-link impressao-card-horizontal" style="display:flex; text-decoration:none; color:inherit; align-items:center;">
          <div class="impressao-card-img">
            <?php
              // Buscar a capa da impressora
              $stmtCapa = $pdo->prepare('SELECT capa FROM impressoras WHERE id = ? LIMIT 1');
              $stmtCapa->execute([$impressora['id']]);
              $rowCapa = $stmtCapa->fetch(PDO::FETCH_ASSOC);
              $impressoraCapa = ($rowCapa && !empty($rowCapa['capa'])) ? trim((string)$rowCapa['capa']) : '';
              $impressoraCapaThumb = '';
              if ($impressoraCapa !== '') {
                if (preg_match('/_media\\.webp$/', $impressoraCapa)) {
                  $impressoraCapaThumb = preg_replace('/_media\\.webp$/', '_thumbnail.webp', $impressoraCapa);
                } else {
                  $impressoraCapaThumb = $impressoraCapa;
                }
              }
            ?>
            <?php if ($impressoraCapaThumb !== ''): ?>
              <img src="<?= htmlspecialchars($impressoraCapaThumb) ?>" alt="Capa da impressora" style="width:96px; height:96px; object-fit:cover; border-radius:12px; border:1px solid #dee2e6;">
            <?php else: ?>
              <i class="fas fa-microscope" style="font-size:3.5rem;"></i>
            <?php endif; ?>
          </div>
          <div class="impressao-card-info">
            <h2><?= htmlspecialchars($impressora['marca'] . ' ' . $impressora['modelo']) ?></h2>
            <p>
              <strong>Tipo:</strong> <?= htmlspecialchars($impressora['tipo']) ?><br>
              <strong>Depreciação:</strong> <?= htmlspecialchars($impressora['depreciacao']) ?>%<br>
              <strong>Custo Hora:</strong> R$ <?= number_format((float) $impressora['custo_hora'], 4, ',', '.') ?>
            </p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-info">Nenhuma impressora cadastrada para seleção.</div>
  <?php endif; ?>

<?php else: ?>
  <h4 class="mb-2">Impressora selecionada</h4>
  <div class="alert alert-primary">
    <strong><?= htmlspecialchars($impressoraSelecionada['marca'] . ' ' . $impressoraSelecionada['modelo']) ?></strong>
    — Tipo: <?= htmlspecialchars($impressoraSelecionada['tipo']) ?>
  </div>

  <?php if ($impressoraSelecionada['tipo'] === 'Resina'): ?>
    <?php
    $stmtMateriais = $pdo->prepare("SELECT id, nome, marca, cor, preco_kilo FROM resinas WHERE usuario_id = ? ORDER BY marca, nome");
    $stmtMateriais->execute([$usuario_id]);
    $materiais = $stmtMateriais->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h4 class="mb-3">Selecione uma Resina</h4>
    <?php if ($materiais): ?>
      <div class="impressoes-grid">
        <?php foreach ($materiais as $material): ?>
          <?php
            $cardLink = $fluxo_miniaturas
              ? '?pagina=miniaturas&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&resina_id=' . (int) $material['id']
              : ($fluxo_torres
                ? '?pagina=torres&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&resina_id=' . (int) $material['id']
                : '?pagina=impressoes&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&resina_id=' . (int) $material['id']);
            // Buscar thumb da resina
            $resinaCapaThumb = '';
            if (!empty($material['id'])) {
              $stmtCapa = $pdo->prepare('SELECT capa FROM resinas WHERE id = ? LIMIT 1');
              $stmtCapa->execute([$material['id']]);
              $rowCapa = $stmtCapa->fetch(PDO::FETCH_ASSOC);
              $resinaCapa = ($rowCapa && !empty($rowCapa['capa'])) ? trim((string)$rowCapa['capa']) : '';
              if ($resinaCapa !== '') {
                if (preg_match('/_media\\.webp$/', $resinaCapa)) {
                  $resinaCapaThumb = preg_replace('/_media\\.webp$/', '_thumbnail.webp', $resinaCapa);
                } else {
                  $resinaCapaThumb = $resinaCapa;
                }
              }
            }
          ?>
          <a href="<?= $cardLink ?>" class="impressao-card impressao-card-horizontal impressao-card-link">
            <div class="impressao-card-img">
              <?php if ($resinaCapaThumb !== ''): ?>
                <img src="<?= htmlspecialchars($resinaCapaThumb) ?>" alt="Capa da resina" style="width:96px; height:96px; object-fit:cover; border-radius:12px; border:1px solid #dee2e6;">
              <?php else: ?>
                <i class="fa-solid fa-bottle-water"></i>
              <?php endif; ?>
            </div>
            <div class="impressao-card-info">
              <h2><?= htmlspecialchars($material['nome']) ?></h2>
              <p>
                <strong>Marca:</strong> <?= htmlspecialchars($material['marca']) ?><br>
                <strong>Cor:</strong> <?= htmlspecialchars($material['cor']) ?><br>
                <strong>Preço/Kg:</strong> R$ <?= number_format((float) $material['preco_kilo'], 2, ',', '.') ?>
              </p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Nenhuma resina cadastrada.</div>
    <?php endif; ?>

  <?php else: ?>
    <?php
    $stmtMateriais = $pdo->prepare("SELECT id, nome, marca, cor, tipo, preco_kilo FROM filamento WHERE usuario_id = ? ORDER BY marca, nome");
    $stmtMateriais->execute([$usuario_id]);
    $materiais = $stmtMateriais->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h4 class="mb-3">Selecione um Filamento</h4>
    <?php if ($materiais): ?>
      <div class="impressoes-grid">
        <?php foreach ($materiais as $material): ?>
          <?php
            $cardLink = $fluxo_miniaturas
              ? '?pagina=miniaturas&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&filamento_id=' . (int) $material['id']
              : ($fluxo_torres
                ? '?pagina=torres&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&filamento_id=' . (int) $material['id']
                : '?pagina=impressoes&acao=adicionar&impressora_id=' . (int) $impressoraSelecionada['id'] . '&filamento_id=' . (int) $material['id']);
            // Buscar thumb do filamento
            $filamentoCapaThumb = '';
            if (!empty($material['id'])) {
              $stmtCapa = $pdo->prepare('SELECT capa FROM filamento WHERE id = ? LIMIT 1');
              $stmtCapa->execute([$material['id']]);
              $rowCapa = $stmtCapa->fetch(PDO::FETCH_ASSOC);
              $filamentoCapa = ($rowCapa && !empty($rowCapa['capa'])) ? trim((string)$rowCapa['capa']) : '';
              if ($filamentoCapa !== '') {
                if (preg_match('/_media\\.webp$/', $filamentoCapa)) {
                  $filamentoCapaThumb = preg_replace('/_media\\.webp$/', '_thumbnail.webp', $filamentoCapa);
                } else {
                  $filamentoCapaThumb = $filamentoCapa;
                }
              }
            }
          ?>
          <a href="<?= $cardLink ?>" class="impressao-card impressao-card-horizontal impressao-card-link">
            <div class="impressao-card-img">
              <?php if ($filamentoCapaThumb !== ''): ?>
                <img src="<?= htmlspecialchars($filamentoCapaThumb) ?>" alt="Capa do filamento" style="width:96px; height:96px; object-fit:cover; border-radius:12px; border:1px solid #dee2e6;">
              <?php else: ?>
                <i class="fas fa-compact-disc" style="font-size:3.5rem;"></i>
              <?php endif; ?>
            </div>
            <div class="impressao-card-info">
              <h2><?= htmlspecialchars($material['tipo'] . ' ' . $material['nome']) ?></h2>
              <p>
                <strong>Marca:</strong> <?= htmlspecialchars($material['marca']) ?><br>
                <strong>Cor:</strong> <?= htmlspecialchars($material['cor']) ?><br>
                <strong>Preço/Kg:</strong> R$ <?= number_format((float) $material['preco_kilo'], 2, ',', '.') ?>
              </p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Nenhum filamento cadastrado.</div>
    <?php endif; ?>
  <?php endif; ?>

  <a href="<?= $fluxo_produtos ? '?pagina=produtos&acao=adicionar' : '?pagina=impressoes' ?>" class="btn btn-secondary mt-3"><?= $fluxo_produtos ? 'Voltar para categorias' : 'Voltar para Impressoras' ?></a>
<?php endif; ?>

<style>
.impressoes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
}


/* Card padrão e link */
.impressao-card, .impressao-card-link {
  .impressao-card-link {
    text-decoration: none !important;
    color: inherit !important;
    transition: box-shadow 0.2s, transform 0.2s;
  }
  .impressao-card-link:hover, .impressao-card-link:focus {
    box-shadow: 0 14px 26px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
    text-decoration: none !important;
    color: inherit !important;
  }
  position: relative;
  background: #fff;
  border-radius: 12px;
  padding: 28px 24px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e9ecef;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  min-height: 320px;
}

.impressao-card-horizontal {
  flex-direction: row;
  align-items: center;
  min-height: 120px;
  padding: 18px 18px;
}
.impressao-card-img {
  flex: 0 0 110px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 22px;
}
.impressao-card-img img,
.impressao-card-img i {
  display: block;
  width: 96px;
  height: 96px;
  font-size: 3.5rem;
  color: #007bff;
  border-radius: 12px;
}
.impressao-card-info {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.impressao-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 26px rgba(0, 0, 0, 0.12);
}

.impressao-icon {
  font-size: 2.2rem;
  color: #007bff;
  margin-bottom: 14px;
}

.impressao-card h2 {
  font-size: 1.35rem;
  font-weight: 600;
  margin-bottom: 10px;
  color: #343a40;
}

.impressao-card p {
  color: #6c757d;
  font-size: 0.95rem;
  margin-bottom: 18px;
}

.impressao-actions {
  margin-top: auto;
  display: flex;
  gap: 10px;
}

.btn-selecionar {
  flex: 1;
  text-align: center;
  padding: 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
}

.btn-selecionar {
  background: #007bff;
  color: #fff;
}

.btn-selecionar:hover {
  background: #0069d9;
  color: #fff;
}
</style>
