<?php
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca impressoras do usuário
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

// Verifica se um material foi escolhido
$filamento_id = isset($_GET['filamento_id']) ? intval($_GET['filamento_id']) : 0;
$resina_id = isset($_GET['resina_id']) ? intval($_GET['resina_id']) : 0;
$material = null;
$material_tipo = null;

if ($impressora_escolhida) {
    if ($impressora_escolhida['tipo'] === 'FDM' && $filamento_id) {
        $stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$filamento_id, $usuario_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        $material_tipo = 'filamento';
    } elseif ($impressora_escolhida['tipo'] === 'Resina' && $resina_id) {
        $stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$resina_id, $usuario_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        $material_tipo = 'resina';
    }
}
?>

<!-- 1 - Sessão Escolha da impressora -->
<?php if (!$impressora_escolhida): ?>
    <h5>Escolha a impressora</h5>
    <div class="row">
      <?php if ($impressoras): ?>
        <?php foreach ($impressoras as $imp): ?>
          <div class="col-md-3">
            <a href="?pagina=calculo_rapido&impressora_id=<?= $imp['id'] ?>" style="text-decoration: none;">
              <div class="card card-primary card-hover" style="cursor:pointer;">
                <div class="card-header">
                  <h3 class="card-title"><?= htmlspecialchars($imp['marca'] . ' ' . $imp['modelo']) ?></h3>
                </div>
                <div class="card-body">
                  <strong>Tipo:</strong> <?= htmlspecialchars($imp['tipo']) ?><br>
                  <strong>Depreciação:</strong> <?= htmlspecialchars($imp['depreciacao']) ?>%<br>
                  <strong>Custo Hora:</strong> R$ <?= number_format($imp['custo_hora'], 4, ',', '.') ?>
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

    <!-- 2 - Sessão Escolha do material -->
    <?php if ($impressora_escolhida['tipo'] === 'Resina' && !$resina_id): ?>
        <h5>Escolha a resina</h5>
        <div class="row">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ?");
          $stmt->execute([$usuario_id]);
          $resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <?php if ($resinas): ?>
            <?php foreach ($resinas as $resina): ?>
              <div class="col-md-3">
                <a href="?pagina=calculo_rapido&impressora_id=<?= $impressora_escolhida['id'] ?>&resina_id=<?= $resina['id'] ?>" style="text-decoration: none;">
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
        <a href="?pagina=calculo_rapido" class="btn btn-secondary mb-3">Voltar</a>
    <?php elseif ($impressora_escolhida['tipo'] === 'FDM' && !$filamento_id): ?>
        <h5>Escolha o filamento</h5>
        <div class="row">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ?");
          $stmt->execute([$usuario_id]);
          $filamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <?php if ($filamentos): ?>
            <?php foreach ($filamentos as $filamento): ?>
              <div class="col-md-3">
                <a href="?pagina=calculo_rapido&impressora_id=<?= $impressora_escolhida['id'] ?>&filamento_id=<?= $filamento['id'] ?>" style="text-decoration: none;">
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
        <a href="?pagina=calculo_rapido" class="btn btn-secondary mb-3">Voltar</a>
    <?php endif; ?>

    <!-- 3 - Sessão Calcular (Card Cadastrar Impressão simplificado) -->
    <?php if ($material): ?>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Calcular</h3>
      </div>
      <div class="card-body">
        <!-- Exibe erros, se houver -->
        <?php if (isset($erro) && $erro): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <div class="row">
          <div class="col-6">
            <h4>
              <i class="fas fa-microscope"></i> 
              <?= htmlspecialchars($impressora_escolhida['marca'] . ' ' . $impressora_escolhida['modelo']) ?>
            </h4>
          </div>
          <div class="col-6">
            <h4>
              <?php if ($material_tipo === 'filamento'): ?>
                <i class="fas fa-compact-disc"></i>
              <?php else: ?>
                <i class="fa-solid fa-bottle-water"></i>
              <?php endif; ?>
              <?= $material_tipo === 'filamento'
                ? htmlspecialchars($material['tipo'] . ' ' . $material['nome'])
                : htmlspecialchars($material['nome']) ?>
            </h4>
          </div>
        </div>
        <div class="row invoice-info">
          <div class="col-sm-6 invoice-col">
            <strong>Tipo:</strong> <?= htmlspecialchars($impressora_escolhida['tipo']) ?><br>
            <strong>Depreciação:</strong> <?= htmlspecialchars($impressora_escolhida['depreciacao']) ?>%<br>
            <strong>Custo Hora:</strong> R$ <?= number_format($impressora_escolhida['custo_hora'], 4, ',', '.') ?><br>
          </div>
          <div class="col-sm-6 invoice-col">
            <strong>Marca:</strong> <?= htmlspecialchars($material['marca']) ?><br>
            <strong>Cor:</strong>
            <?php if (!empty($material['cor'])): ?>
              <i class="fas fa-circle nav-icon" style="color:<?= htmlspecialchars($material['cor']) ?>; border:1px solid #ddd; border-radius:50%;"></i>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
            <br>
            <?php if ($material_tipo === 'filamento'): ?>
              <strong>Preço/Kg:</strong> R$ <?= number_format($material['preco_kilo'], 2, ',', '.') ?>
            <?php else: ?>
              <strong>Preço/Litro:</strong> R$ <?= number_format($material['preco_litro'], 2, ',', '.') ?>
            <?php endif; ?>
            <br>
          </div>
        </div>
        <form method="POST">
          <hr>
          <h5>Dados Técnicos da Impressão</h5>
          <div class="form-row">
            <div class="form-group col-md-2 mb-3">
              <?php if ($material_tipo === 'filamento'): ?>
                <label for="peso_material">Peso (g)</label>
                <input
                  type="number"
                  class="form-control"
                  id="peso_material"
                  name="peso_material"
                  placeholder="Peso"
                  required
                  value="<?= isset($_POST['peso_material']) ? htmlspecialchars($_POST['peso_material']) : '' ?>"
                >
              <?php elseif ($material_tipo === 'resina'): ?>
                <label for="peso_material">Volume (ml)</label>
                <input
                  type="number"
                  class="form-control"
                  id="peso_material"
                  name="peso_material"
                  placeholder="Volume"
                  required
                  value="<?= isset($_POST['peso_material']) ? htmlspecialchars($_POST['peso_material']) : '' ?>"
                >
              <?php endif; ?>
            </div>
            <div class="form-group col-md-4 mb-3">
              <label>Tempo de Impressão</label>
              <div class="form-row">
                <div class="col">
                  <input type="number" class="form-control" name="tempo_dias" placeholder="Dias" min="0" value="<?= isset($_POST['tempo_dias']) ? htmlspecialchars($_POST['tempo_dias']) : '' ?>">
                </div>
                <div class="col">
                  <input type="number" class="form-control" name="tempo_horas" placeholder="Horas" min="0" max="23" value="<?= isset($_POST['tempo_horas']) ? htmlspecialchars($_POST['tempo_horas']) : '' ?>">
                </div>
                <div class="col">
                  <input type="number" class="form-control" name="tempo_minutos" placeholder="Min" min="0" max="59" value="<?= isset($_POST['tempo_minutos']) ? htmlspecialchars($_POST['tempo_minutos']) : '' ?>">
                </div>
              </div>
            </div>
            <div class="form-group col-md-2 mb-3">
              <label for="unidades_produzidas">Unidades Produzidas</label>
              <input
                type="number"
                class="form-control"
                id="unidades_produzidas"
                name="unidades_produzidas"
                placeholder="Unidades"
                required
                value="<?= isset($_POST['unidades_produzidas']) ? htmlspecialchars($_POST['unidades_produzidas']) : '' ?>"
              >
            </div>
            <div class="form-group col-md-2 mb-3">
              <label for="taxa_falha">Taxa de Falha (%)</label>
              <input type="number" class="form-control" id="taxa_falha" name="taxa_falha" required value="<?= isset($_POST['taxa_falha']) ? htmlspecialchars($_POST['taxa_falha']) : '' ?>" placeholder="10">
            </div>
            <div class="form-group col-md-2 mb-3">
              <label for="markup">Markup</label>
              <select class="form-control" id="markup" name="markup" required>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= (isset($_POST['markup']) && $_POST['markup'] == $i) ? 'selected' : ($i == 5 ? 'selected' : '') ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
      </div>
      <div class="card-footer">
        <a href="?pagina=calculo_rapido&impressora_id=<?= $impressora_escolhida['id'] ?>" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Calcular</button>
      </div>
        </form>
    </div>
    <?php endif; ?>
<?php endif; ?>