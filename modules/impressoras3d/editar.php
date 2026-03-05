<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';
$id = intval($_GET['id'] ?? 0);

// Busca impressora do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$imp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$imp) {
    echo '<div class="alert alert-danger">Impressora não encontrada ou você não tem permissão!</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $marca = trim($_POST['marca'] ?? '');
  $modelo = trim($_POST['modelo'] ?? '');
  $tipo = $_POST['tipo'] ?? '';
  $preco_aquisicao = floatval($_POST['preco_aquisicao'] ?? 0);
  $potencia = intval($_POST['potencia'] ?? 0);
  $depreciacao = intval($_POST['depreciacao'] ?? 0);
  $tempo_vida_util = intval($_POST['tempo_vida_util'] ?? 0);
  $capa_existente = trim($_POST['capa_existente'] ?? '');

  // Upload da nova capa, se enviada
  $novoCaminhoCapa = $capa_existente;
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    require_once __DIR__ . '/../../app/upload_imagem.php';
    // Buscar uuid do usuário
    $stmtUuid = $pdo->prepare('SELECT uuid FROM usuarios WHERE id = ?');
    $stmtUuid->execute([$usuario_id]);
    $usuarioUuid = $stmtUuid->fetchColumn();
    if ($usuarioUuid) {
      $tamanhosUpload = [
        'thumbnail' => [150, 150, 'crop'],
        'media'     => [300, 300, 'proporcional'],
        'grande'    => [1024, 1024, 'proporcional']
      ];
      $caminho = uploadImagem($_FILES['foto'], $usuarioUuid, 'usuarios', $tamanhosUpload, 'impressora', false);
      if ($caminho) {
        // Remove a capa antiga se existir e for diferente
        if ($capa_existente && file_exists(__DIR__ . '/../../' . $capa_existente)) {
          @unlink(__DIR__ . '/../../' . $capa_existente);
        }
        $novoCaminhoCapa = $caminho;
      }
    }
  } elseif ($capa_existente === '') {
    // Se o usuário removeu a capa, apaga todos os tamanhos
    if ($imp['capa']) {
      $caminhoBase = preg_replace('/_media\.webp$/', '', $imp['capa']);
      $tamanhos = ['media', 'thumbnail', 'grande'];
      foreach ($tamanhos as $tamanho) {
        $arquivo = __DIR__ . '/../../' . $caminhoBase . "_{$tamanho}.webp";
        if (file_exists($arquivo)) {
          @unlink($arquivo);
        }
      }
    }
    $novoCaminhoCapa = null;
  }

  if (!$marca || !$modelo || !$tipo || !$preco_aquisicao || !$potencia || !$depreciacao || !$tempo_vida_util) {
    $erro = 'Preencha todos os campos obrigatórios.';
  } else {
    try {
      $stmt = $pdo->prepare("UPDATE impressoras SET marca = ?, modelo = ?, tipo = ?, preco_aquisicao = ?, potencia = ?, depreciacao = ?, tempo_vida_util = ?, capa = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
      $stmt->execute([$marca, $modelo, $tipo, $preco_aquisicao, $potencia, $depreciacao, $tempo_vida_util, $novoCaminhoCapa, $id, $usuario_id]);
      echo '<script>window.location.href="?pagina=impressoras3d";</script>';
      exit;
    } catch (PDOException $e) {
      $erro = 'Erro ao editar: ' . $e->getMessage();
    }
  }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Impressora 3D</h3>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-row">
        <div class="col-md-3">
          <div class="form-group h-100">
            <label for="foto">Capa</label>
            <div id="capa-preview-area" class="border rounded bg-light position-relative" style="min-height: 470px; cursor: pointer;" onclick="document.getElementById('foto').click();">
              <img id="preview-capa" src="<?= htmlspecialchars($imp['capa'] ?? '') ?>" alt="Pré-visualização da capa" class="img-fluid w-100 h-100<?= empty($imp['capa']) ? ' d-none' : '' ?>" style="min-height: 470px; object-fit: cover;">
              <button type="button" id="remove-capa-btn" class="btn btn-danger btn-sm rounded-circle<?= empty($imp['capa']) ? ' d-none' : '' ?>" style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; line-height:26px;" onclick="event.stopPropagation();">&times;</button>
              <div id="capa-placeholder" class="align-items-center justify-content-center text-muted<?= !empty($imp['capa']) ? ' d-none' : '' ?>" style="position:absolute; top:0; right:0; bottom:0; left:0; display:flex;">
                Clique para selecionar a capa
              </div>
            </div>
            <input type="file" id="foto" name="foto" accept=".jpg,.png,.webp" style="display:none;">
            <input type="hidden" id="capa_existente" name="capa_existente" value="<?= htmlspecialchars($imp['capa'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-9">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="marca">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars($imp['marca']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label for="modelo">Modelo</label>
              <input type="text" class="form-control" id="modelo" name="modelo" required value="<?= htmlspecialchars($imp['modelo']) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="tipo">Tipo</label>
              <select class="form-control" id="tipo" name="tipo" required>
                <option value="">Selecione...</option>
                <option value="FDM" <?= ($imp['tipo'] === 'FDM') ? 'selected' : '' ?>>FDM</option>
                <option value="Resina" <?= ($imp['tipo'] === 'Resina') ? 'selected' : '' ?>>Resina</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="preco_aquisicao">Preço de Aquisição (R$)</label>
              <input type="number" step="0.01" class="form-control" id="preco_aquisicao" name="preco_aquisicao" required value="<?= htmlspecialchars($imp['preco_aquisicao']) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="potencia">Potência (W)</label>
              <input type="number" class="form-control" id="potencia" name="potencia" required value="<?= htmlspecialchars($imp['potencia']) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="depreciacao">Depreciação (%)</label>
              <input type="number" class="form-control" id="depreciacao" name="depreciacao" required value="<?= htmlspecialchars($imp['depreciacao']) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="tempo_vida_util">Tempo Vida Útil (h)</label>
              <input type="number" class="form-control" id="tempo_vida_util" name="tempo_vida_util" required value="<?= htmlspecialchars($imp['tempo_vida_util']) ?>">
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=impressoras3d" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var capaExistenteInput = document.getElementById('capa_existente');

  function renderizarCapaExistente() {
    var caminhoCapaExistente = capaExistenteInput && typeof capaExistenteInput.value === 'string'
      ? capaExistenteInput.value.trim()
      : '';

    if (caminhoCapaExistente !== '') {
      previewImagem.src = caminhoCapaExistente;
      previewImagem.classList.remove('d-none');
      capaPlaceholder.classList.add('d-none');
      capaPlaceholder.style.display = 'none';
      removeCapaBtn.classList.remove('d-none');
    } else {
      previewImagem.src = '';
      previewImagem.classList.add('d-none');
      capaPlaceholder.classList.remove('d-none');
      capaPlaceholder.style.display = 'flex';
      removeCapaBtn.classList.add('d-none');
    }
  }

  renderizarCapaExistente();

  inputFoto.addEventListener('change', function () {
    var arquivo = this.files && this.files[0] ? this.files[0] : null;

    if (!arquivo) {
      renderizarCapaExistente();
      return;
    }

    if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
      renderizarCapaExistente();
      return;
    }

    var leitor = new FileReader();
    leitor.onload = function (evento) {
      previewImagem.src = evento.target.result;
      previewImagem.classList.remove('d-none');
      capaPlaceholder.classList.add('d-none');
      capaPlaceholder.style.display = 'none';
      removeCapaBtn.classList.remove('d-none');
    };
    leitor.readAsDataURL(arquivo);
  });

  removeCapaBtn.addEventListener('click', function () {
    if (inputFoto.value) {
      inputFoto.value = '';
      renderizarCapaExistente();
      return;
    }
    if (capaExistenteInput) {
      capaExistenteInput.value = '';
    }
    renderizarCapaExistente();
  });
});
</script>
</div>