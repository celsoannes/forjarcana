<?php
require __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/imagem.php';

$usuario_id = $_SESSION['usuario_id'];

// Busca impressoras do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etapa 1: Seleção da impressora
if (!isset($_GET['impressora_id'])): ?>
    <h2>Escolha a impressora</h2>
    <div class="row">
    <?php foreach ($impressoras as $imp): ?>
      <div class="col-md-4 mb-3">
        <div class="card card-clickable" onclick="window.location='?pagina=impressoes&acao=adicionar&impressora_id=<?= $imp['id'] ?>&tipo=<?= $imp['tipo'] ?>'">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($imp['marca']) ?> <?= htmlspecialchars($imp['modelo']) ?></h5>
            <p class="card-text">
              <strong>Tipo:</strong> <?= htmlspecialchars($imp['tipo']) ?><br>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <a href="?pagina=impressoes" class="btn btn-secondary mt-3">Voltar</a>
    <style>
      .card-clickable { cursor: pointer; transition: box-shadow .2s; }
      .card-clickable:hover { box-shadow: 0 0 16px #ffd700; }
    </style>
<?php
    exit;
endif;

// Etapa 2: Seleção do material
$tipo = $_GET['tipo'] ?? '';
if (isset($_GET['impressora_id']) && !isset($_GET['material_id'])):
    if ($tipo === 'FDM') {
        $stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ?");
    }
    $stmt->execute([$usuario_id]);
    $materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h2>Escolha o material</h2>
    <div class="row">
    <?php foreach ($materiais as $mat): ?>
  <div class="col-md-4 mb-3">
    <div class="card card-clickable"
         onclick="window.location='?pagina=impressoes&acao=adicionar&impressora_id=<?= $_GET['impressora_id'] ?>&tipo=<?= $tipo ?>&material_id=<?= $mat['id'] ?>&material_tabela=<?= ($tipo === 'FDM' ? 'filamento' : 'resinas') ?>'">
      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($mat['nome']) ?></h5>
        <p class="card-text">
          <strong>Marca:</strong> <?= htmlspecialchars($mat['marca']) ?><br>
          <strong>Cor:</strong>
          <span style="display:inline-block;width:16px;height:16px;border-radius:50%;vertical-align:middle;margin-left:4px;background:<?= htmlspecialchars($mat['cor']) ?>;"></span><br>
          <?php if ($tipo === 'FDM'): ?>
            <strong>Tipo:</strong> <?= htmlspecialchars($mat['tipo']) ?><br>
            <strong>Preço/kg:</strong> R$ <?= number_format($mat['preco_kilo'], 2, ',', '.') ?>
          <?php else: ?>
            <strong>Preço/L:</strong> R$ <?= number_format($mat['preco_litro'], 2, ',', '.') ?>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
    </div>
    <a href="?pagina=impressoes&acao=adicionar" class="btn btn-secondary mt-3">Voltar</a>
    <?php
    exit;
endif;

// Etapa 3: Cadastro da impressão
if (isset($_GET['impressora_id']) && isset($_GET['material_id'])):
    // Busca impressora e material escolhidos
    $stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['impressora_id'], $usuario_id]);
    $impressora = $stmt->fetch(PDO::FETCH_ASSOC);

    $material_tabela = $_GET['material_tabela'] ?? 'filamento';
    $stmt = $pdo->prepare("SELECT * FROM $material_tabela WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$_GET['material_id'], $usuario_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    $mensagem = '';

    // Processa cadastro
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome_impressao = trim($_POST['nome_impressao'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $imagem_nome = '';

        // Upload da imagem (opcional)
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $imagem_nome = salvarImagemUsuario($_FILES['imagem'], $usuario_id, __DIR__ . '/../uploads');
        }

        if (!$nome_impressao) {
            $mensagem = "Preencha o nome da impressão!";
        } else {
            // Insere impressão no banco
            $stmt = $pdo->prepare("INSERT INTO impressoes (usuario_id, impressora_id, material_id, nome_impressao, descricao, imagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $usuario_id,
                $_GET['impressora_id'],
                $_GET['material_id'],
                $nome_impressao,
                $descricao,
                $imagem_nome
            ]);
            echo '<script>window.location.href="?pagina=impressoes";</script>';
            exit;
        }
    }

    // Cards das escolhas e formulário
    ?>
    <h2>Adicionar Impressão</h2>
    <?php if ($mensagem): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card card-produto-detalhe">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($impressora['marca']) ?> <?= htmlspecialchars($impressora['modelo']) ?></h5>
            <p class="card-text">
              <strong>Tipo:</strong> <?= htmlspecialchars($impressora['tipo']) ?><br>
              <strong>Depreciação:</strong> <?= htmlspecialchars($impressora['depreciacao']) ?>%<br>
              <strong>Custo/hora:</strong> R$ <?= number_format($impressora['custo_hora'], 2, ',', '.') ?><br>
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-produto-detalhe">
          <div class="card-body">
            <?php if ($material_tabela === 'filamento'): ?>
              <h5 class="card-title"><?= htmlspecialchars($material['tipo']) ?> <?= htmlspecialchars($material['nome']) ?></h5>
              <p class="card-text">
                <strong>Marca:</strong> <?= htmlspecialchars($material['marca']) ?><br>
                <strong>Cor:</strong>
                <span style="display:inline-block;width:16px;height:16px;border-radius:50%;vertical-align:middle;margin-left:4px;background:<?= htmlspecialchars($material['cor']) ?>;"></span>
                <br>
                <strong>Preço/kg:</strong> R$ <?= number_format($material['preco_kilo'], 2, ',', '.') ?>
              </p>
            <?php else: ?>
              <h5 class="card-title"><?= htmlspecialchars($material['nome']) ?></h5>
              <p class="card-text">
                <strong>Marca:</strong> <?= htmlspecialchars($material['marca']) ?><br>
                <strong>Cor:</strong>
                <span style="display:inline-block;width:16px;height:16px;border-radius:50%;vertical-align:middle;margin-left:4px;background:<?= htmlspecialchars($material['cor']) ?>;"></span>
                <br>
                <strong>Preço/L:</strong> R$ <?= number_format($material['preco_litro'], 2, ',', '.') ?>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nome da impressão *</label>
        <input type="text" name="nome_impressao" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Imagem</label>
        <input type="file" name="imagem" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp">
      </div>
      <button type="submit" class="btn btn-success">Adicionar Impressão</button>
      <a href="?pagina=impressoes&acao=adicionar&impressora_id=<?= $_GET['impressora_id'] ?>&tipo=<?= $tipo ?>" class="btn btn-secondary ms-2">Voltar</a>
    </form>
    <?php
endif;
?>