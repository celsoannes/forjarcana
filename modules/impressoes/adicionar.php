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

// Verifica se um material foi escolhido
$material_id = 0;
$material_tipo = '';
$material = null;

if ($impressora_escolhida) {
    if ($impressora_escolhida['tipo'] === 'Resina' && isset($_GET['resina_id'])) {
        $material_id = intval($_GET['resina_id']);
        $material_tipo = 'resina';
        $stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$material_id, $usuario_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($impressora_escolhida['tipo'] === 'FDM' && isset($_GET['filamento_id'])) {
        $material_id = intval($_GET['filamento_id']);
        $material_tipo = 'filamento';
        $stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$material_id, $usuario_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Processa o formulário
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $impressora_escolhida && $material) {
    $nome = trim($_POST['nome'] ?? '');
    $nome_original = trim($_POST['nome_original'] ?? '');
    $arquivo_impressao = trim($_POST['arquivo_impressao'] ?? '');
    $imagem_capa = trim($_POST['imagem_capa'] ?? '');
    $estudio_id = intval($_POST['estudio_id'] ?? 0);
    $colecao_id = intval($_POST['colecao_id'] ?? 0);
    $tempo_dias = intval($_POST['tempo_dias'] ?? 0);
    $tempo_horas = intval($_POST['tempo_horas'] ?? 0);
    $tempo_minutos = intval($_POST['tempo_minutos'] ?? 0);
    $tempo_impressao = ($tempo_dias * 24 * 60) + ($tempo_horas * 60) + $tempo_minutos;
    $unidades_produzidas = intval($_POST['unidades_produzidas'] ?? 1);
    $markup = intval($_POST['markup'] ?? 5); // valor padrão 5
    $taxa_falha = intval($_POST['taxa_falha'] ?? 15);
    if ($taxa_falha <= 0) $taxa_falha = 15;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $peso_material = intval($_POST['peso_material'] ?? 0);

    $campos_faltando = [];
    if ($markup <= 0) $campos_faltando[] = 'Markup';
    if ($taxa_falha <= 0) $campos_faltando[] = 'Taxa de Falha';
    if (!$nome || !$tempo_impressao || !$unidades_produzidas) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($campos_faltando) {
        $erro = 'Preencha os campos obrigatórios: ' . implode(', ', $campos_faltando) . '.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO impressoes 
                (nome, nome_original, arquivo_impressao, impressora_id, material_id, tempo_impressao, imagem_capa, unidades_produzidas, markup, taxa_falha, estudio_id, colecao_id, usuario_id, peso_material) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nome,
                $nome_original,
                $arquivo_impressao,
                $impressora_escolhida['id'],
                $material_id,
                $tempo_impressao,
                $imagem_capa,
                $unidades_produzidas,
                $markup,
                $taxa_falha,
                $estudio_id ?: null,
                $colecao_id ?: null,
                $usuario_id,
                $peso_material
            ]);
            echo '<script>window.location.href="?pagina=impressoes";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar impressão: ' . $e->getMessage();
        }
    }
}
?>

<?php if (!$impressora_escolhida): ?>
    <!-- Escolha da impressora -->
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
    <a href="?pagina=impressoes" class="btn btn-secondary mt-3">Voltar</a>
<?php elseif (!$material): ?>
    <!-- Escolha do material -->
    <h5 class="mb-2">
      Impressora escolhida: <?= htmlspecialchars($impressora_escolhida['marca'] . ' ' . $impressora_escolhida['modelo']) ?>
    </h5>
    <?php if ($impressora_escolhida['tipo'] === 'Resina'): ?>
        <?php
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
    <a href="?pagina=impressoes&acao=adicionar" class="btn btn-secondary mt-3">Voltar</a>
<?php else: ?>
    <!-- Formulário de cadastro da impressão -->
    <h5 class="mb-2">Cadastrar Impressão</h5>
    <div class="row mb-3">
      <div class="col-md-3">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"><?= htmlspecialchars($impressora_escolhida['marca'] . ' ' . $impressora_escolhida['modelo']) ?></h3>
          </div>
          <div class="card-body">
            <strong>Tipo:</strong> <?= htmlspecialchars($impressora_escolhida['tipo']) ?><br>
            <strong>Depreciação:</strong> <?= htmlspecialchars($impressora_escolhida['depreciacao']) ?>%<br>
            <strong>Custo Hora:</strong> R$ <?= number_format($impressora_escolhida['custo_hora'], 4, ',', '.') ?>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card <?= $material_tipo === 'resina' ? 'card-success' : 'card-info' ?>">
          <div class="card-header">
            <h3 class="card-title">
              <?= $material_tipo === 'filamento'
                ? htmlspecialchars($material['tipo'] . ' ' . $material['nome'])
                : htmlspecialchars($material['nome']) ?>
            </h3>
          </div>
          <div class="card-body">
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
          </div>
        </div>
      </div>
    </div>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="card card-primary">
        <div class="card-body">
          <div class="form-group">
            <label for="nome">Nome da Impressão</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>
          <div class="form-group">
            <label for="nome_original">Nome Original</label>
            <input type="text" class="form-control" id="nome_original" name="nome_original">
          </div>
          <div class="form-group">
            <label for="arquivo_impressao">Arquivo de Impressão</label>
            <input type="text" class="form-control" id="arquivo_impressao" name="arquivo_impressao" placeholder="Ex: modelo.stl">
          </div>
          <div class="form-group">
            <label for="imagem_capa">Imagem de Capa</label>
            <input type="text" class="form-control" id="imagem_capa" name="imagem_capa" placeholder="URL ou caminho da imagem">
          </div>
          <div class="form-group">
            <label for="estudio_id">Estúdio</label>
            <select class="form-control" id="estudio_id" name="estudio_id">
              <option value="">Selecione...</option>
              <?php
              $stmt = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ?");
              $stmt->execute([$usuario_id]);
              foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $estudio) {
                  echo '<option value="' . $estudio['id'] . '">' . htmlspecialchars($estudio['nome']) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="colecao_id">Coleção</label>
            <select class="form-control" id="colecao_id" name="colecao_id">
              <option value="">Selecione...</option>
              <?php
              $stmt = $pdo->prepare("SELECT id, nome FROM colecoes WHERE usuario_id = ?");
              $stmt->execute([$usuario_id]);
              foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $colecao) {
                  echo '<option value="' . $colecao['id'] . '">' . htmlspecialchars($colecao['nome']) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <?php if ($material_tipo === 'filamento'): ?>
              <label for="peso_material">Peso (g)</label>
              <input
                type="number"
                class="form-control"
                id="peso_material"
                name="peso_material"
                placeholder="Informe o peso do filamento utilizado em gramas"
                required
              >
            <?php elseif ($material_tipo === 'resina'): ?>
              <label for="peso_material">Volume (ml)</label>
              <input
                type="number"
                class="form-control"
                id="peso_material"
                name="peso_material"
                placeholder="Informe o volume de resina utilizado em mililitros"
                required
              >
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Tempo de Impressão</label>
            <div class="form-row">
              <div class="col">
                <input type="number" class="form-control" name="tempo_dias" placeholder="Dias" min="0">
              </div>
              <div class="col">
                <input type="number" class="form-control" name="tempo_horas" placeholder="Horas" min="0" max="23">
              </div>
              <div class="col">
                <input type="number" class="form-control" name="tempo_minutos" placeholder="Minutos" min="0" max="59">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="unidades_produzidas">Unidades Produzidas</label>
            <input
              type="number"
              class="form-control"
              id="unidades_produzidas"
              name="unidades_produzidas"
              placeholder="Informe o número de peças idênticas que a impressão gerou. Se for apenas uma, coloque 1"
              required
            >
          </div>
          <div class="form-group">
            <label for="markup">Markup</label>
            <select class="form-control" id="markup" name="markup" required>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == 5 ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="taxa_falha">Taxa de Falha (%)</label>
            <input type="number" class="form-control" id="taxa_falha" name="taxa_falha" required value="" placeholder="10">
          </div>
          <div class="form-group">
            <label for="observacoes">Observações</label>
            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">Salvar</button>
          <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $impressora_escolhida['id'] ?>" class="btn btn-secondary">Voltar</a>
        </div>
      </div>
    </form>
<?php endif; ?>

<style>
.card-hover:hover {
  box-shadow: 0 0 0.5rem #007bff;
  border-color: #007bff;
}
</style>