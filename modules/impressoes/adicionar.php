<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php'; // Adiciona o upload

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = $_SESSION['usuario_uuid'] ?? '';

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
    $imagem_capa = ''; // Inicializa como vazio
    $estudio_id = intval($_POST['estudio_id'] ?? 0);
    $colecao_id = intval($_POST['colecao_id'] ?? 0);
    $tempo_dias = intval($_POST['tempo_dias'] ?? 0);
    $tempo_horas = intval($_POST['tempo_horas'] ?? 0);
    $tempo_minutos = intval($_POST['tempo_minutos'] ?? 0);
    $tempo_impressao = ($tempo_dias * 24 * 60) + ($tempo_horas * 60) + $tempo_minutos;
    $unidades_produzidas = intval($_POST['unidades_produzidas'] ?? 1);
    $markup = intval($_POST['markup'] ?? 5);
    $taxa_falha = intval($_POST['taxa_falha'] ?? 15);
    if ($taxa_falha <= 0) $taxa_falha = 15;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $peso_material = intval($_POST['peso_material'] ?? 0);

    // Upload da imagem de capa (opcional)
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
        $imagem_capa = uploadImagem($_FILES['imagem_capa'], $usuario_uuid, 'usuarios', null, 'impressao', false);
        if (!$imagem_capa) {
            $erro = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
        }
    } else {
        $imagem_capa = trim($_POST['imagem_capa'] ?? '');
    }

    $campos_faltando = [];
    if ($markup <= 0) $campos_faltando[] = 'Markup';
    if ($taxa_falha <= 0) $campos_faltando[] = 'Taxa de Falha';
    if (!$nome || !$tempo_impressao || !$unidades_produzidas) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($campos_faltando) {
        $erro = 'Preencha os campos obrigatórios: ' . implode(', ', $campos_faltando) . '.';
    } else if (!$erro) {
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
    <div class="card card-primary mb-3">
      <div class="card-header">
        <h3 class="card-title">Escolha a impressora</h3>
      </div>
      <div class="card-body">
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
      </div>
      <div class="card-footer">
        <a href="?pagina=impressoes" class="btn btn-secondary">Voltar</a>
      </div>
    </div>
<?php elseif (!$material): ?>
    <!-- Escolha do material -->
    <div class="card card-warning mb-3">
      <div class="card-header">
        <h3 class="card-title">Escolha do material</h3>
      </div>
      <div class="card-body">
        <!-- Card Impressora escolhida -->
        <div class="card card-primary mb-3">
          <div class="card-header">
            <h3 class="card-title">Impressora escolhida</h3>
          </div>
          <div class="card-body">
            <div class="card card-info mb-3">
              <div class="card-header">
                <h3 class="card-title">
                  <?= htmlspecialchars($impressora_escolhida['marca'] . ' ' . $impressora_escolhida['modelo']) ?>
                </h3>
              </div>
              <div class="card-body">
                <strong>Tipo:</strong> <?= htmlspecialchars($impressora_escolhida['tipo']) ?><br>
                <strong>Depreciação:</strong> <?= htmlspecialchars($impressora_escolhida['depreciacao']) ?>%<br>
                <strong>Custo Hora:</strong> R$ <?= number_format($impressora_escolhida['custo_hora'], 4, ',', '.') ?>
              </div>
            </div>
          </div>
        </div>
        <!-- Card Escolha a resina -->
        <?php if ($impressora_escolhida['tipo'] === 'Resina'): ?>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="card card-success mb-3">
              <div class="card-header">
                <h3 class="card-title">Escolha a resina</h3>
              </div>
              <div class="card-body">
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
              </div>
            </div>
        <?php elseif ($impressora_escolhida['tipo'] === 'FDM'): ?>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $filamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <!-- Card Escolha o filamento movido para dentro do card Escolha do material -->
            <div class="card card-info mb-3">
              <div class="card-header">
                <h3 class="card-title">Escolha o filamento</h3>
              </div>
              <div class="card-body">
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
              </div>
            </div>
        <?php endif; ?>
      </div>
      <div class="card-footer">
        <a href="?pagina=impressoes&acao=adicionar" class="btn btn-secondary">Voltar</a>
      </div>
    </div>
<?php else: ?>
    <!-- Card Cadastrar Impressão -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Cadastrar Impressão</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
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
          <div class="col-md-6">
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
          <!-- Cards: Identificação, Arquivos e Mídia, Dados Técnicos, Observações -->
          <!-- Card Identificação da Impressão -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Identificação da Impressão</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label for="nome">Nome da Impressão</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nome_original">Nome Original</label>
                  <input type="text" class="form-control" id="nome_original" name="nome_original">
                </div>
                <div class="form-group col-md-3">
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
                <div class="form-group col-md-3">
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
              </div>
            </div>
          </div>
          <!-- Título entre os cards -->
          <h5 class="mt-3 mb-3">Identificação da Impressão</h5>
          <!-- Card Arquivos e Mídia -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Arquivos e Mídia</h3>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-7">
                  <label for="arquivo_impressao">Arquivo de Impressão</label>
                  <input type="text" class="form-control" id="arquivo_impressao" name="arquivo_impressao" placeholder="Ex: modelo.stl">
                </div>
                <div class="form-group col-md-5">
                  <label for="imagem_capa">Imagem de Capa</label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="imagem_capa" name="imagem_capa" accept="image/png,image/jpeg,image/webp,image/gif">
                    <label class="custom-file-label" for="imagem_capa">Selecione uma imagem</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Card Dados Técnicos da Impressão -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Dados Técnicos da Impressão</h3>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-2">
                  <label for="peso_material">Peso (g)</label>
                  <input
                    type="number"
                    class="form-control"
                    id="peso_material"
                    name="peso_material"
                    placeholder="Peso"
                    required
                  >
                </div>
                <div class="form-group col-md-2">
                  <label>Tempo de Impressão</label>
                  <div class="form-row">
                    <div class="col">
                      <input type="number" class="form-control" name="tempo_dias" placeholder="Dias" min="0">
                    </div>
                    <div class="col">
                      <input type="number" class="form-control" name="tempo_horas" placeholder="Horas" min="0" max="23">
                    </div>
                    <div class="col">
                      <input type="number" class="form-control" name="tempo_minutos" placeholder="Min" min="0" max="59">
                    </div>
                  </div>
                </div>
                <div class="form-group col-md-2">
                  <label for="unidades_produzidas">Unidades Produzidas</label>
                  <input
                    type="number"
                    class="form-control"
                    id="unidades_produzidas"
                    name="unidades_produzidas"
                    placeholder="Unidades"
                    required
                  >
                </div>
                <div class="form-group col-md-2">
                  <label for="taxa_falha">Taxa de Falha (%)</label>
                  <input type="number" class="form-control" id="taxa_falha" name="taxa_falha" required value="" placeholder="10">
                </div>
                <div class="form-group col-md-2">
                  <label for="markup">Markup</label>
                  <select class="form-control" id="markup" name="markup" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == 5 ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <!-- Card Observações -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Observações</h3>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea class="form-control" id="observacoes" name="observacoes" rows="2" style="width:100%;"></textarea>
              </div>
            </div>
          </div>
          <!-- Botões Salvar e Voltar -->
      </div>
      <div class="card-footer">
        <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $impressora_escolhida['id'] ?>" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
        </form>
    </div>
<?php endif; ?>

<style>
.card-hover:hover {
  box-shadow: 0 0 0.5rem #007bff;
  border-color: #007bff;
}
</style>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(document).ready(function () {
    bsCustomFileInput.init();
  });
</script>