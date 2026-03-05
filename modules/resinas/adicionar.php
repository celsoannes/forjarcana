<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Resinas\ResinaController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$resinaController = new ResinaController($pdo);
$dadosFormulario = $resinaController->montarEstadoFormularioAdicao($_POST ?? []);
$coresDisponiveis = [];
$nomesDisponiveis = [];
$marcasDisponiveis = [];
try {
  $stmtCores = $pdo->prepare("SELECT DISTINCT cor FROM resinas WHERE usuario_id = ? AND cor IS NOT NULL AND cor <> '' ORDER BY cor ASC");
  $stmtCores->execute([(int) $usuario_id]);
  $coresDisponiveis = $stmtCores->fetchAll(PDO::FETCH_COLUMN);

  $stmtNomes = $pdo->prepare("SELECT DISTINCT nome FROM resinas WHERE usuario_id = ? AND nome IS NOT NULL AND nome <> '' ORDER BY nome ASC");
  $stmtNomes->execute([(int) $usuario_id]);
  $nomesDisponiveis = $stmtNomes->fetchAll(PDO::FETCH_COLUMN);

  $stmtMarcas = $pdo->prepare("SELECT DISTINCT marca FROM resinas WHERE usuario_id = ? AND marca IS NOT NULL AND marca <> '' ORDER BY marca ASC");
  $stmtMarcas->execute([(int) $usuario_id]);
  $marcasDisponiveis = $stmtMarcas->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
  $coresDisponiveis = [];
  $nomesDisponiveis = [];
  $marcasDisponiveis = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once __DIR__ . '/../../app/upload_imagem.php';
  $caminhoCapa = null;
  $usuario_uuid = isset($_SESSION['usuario_uuid']) ? trim((string)$_SESSION['usuario_uuid']) : null;
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $usuario_uuid) {
    // Salva em uploads/usuarios/{uuid} com prefixo 'resina'
    $caminhoCapa = uploadImagem($_FILES['foto'], $usuario_uuid, 'usuarios', null, 'resina');
  }
  $resultadoFluxo = $resinaController->processarFluxoAdicao((int) $usuario_id, $_POST, $caminhoCapa);
  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=resinas";</script>';
    exit;
  }
  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Resina</h3>
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
              <img id="preview-capa" src="" alt="Pré-visualização da capa" class="img-fluid w-100 h-100 d-none" style="min-height: 470px; object-fit: cover;">
              <button type="button" id="remove-capa-btn" class="btn btn-danger btn-sm rounded-circle d-none" style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; line-height:26px;" onclick="event.stopPropagation();">&times;</button>
              <div id="capa-placeholder" class="align-items-center justify-content-center text-muted" style="position:absolute; top:0; right:0; bottom:0; left:0; display:flex;">
                Clique para selecionar a capa
              </div>
            </div>
            <input type="file" id="foto" name="foto" accept=".jpg,.png,.webp" style="display:none;">
          </div>
        </div>
        <div class="col-md-9">
          <div class="form-group position-relative">
            <label for="nome">Série/Atributo</label>
            <input type="text" class="form-control" id="nome" name="nome" required placeholder="Basic, Pro, Tough, ABS-Like, Flex" autocomplete="off" value="<?= htmlspecialchars((string) ($dadosFormulario['nome'] ?? '')) ?>">
            <ul id="nome-sugestoes" class="autocomplete-sugestoes list-group position-absolute w-100 d-none" style="top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></ul>
          </div>
          <div class="form-group position-relative">
            <label for="marca">Marca</label>
            <input type="text" class="form-control" id="marca" name="marca" required autocomplete="off" value="<?= htmlspecialchars((string) ($dadosFormulario['marca'] ?? '')) ?>">
            <ul id="marca-sugestoes" class="autocomplete-sugestoes list-group position-absolute w-100 d-none" style="top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></ul>
          </div>
          <div class="form-group position-relative">
            <label for="cor">Cor</label>
            <?php $corSelecionada = (string) ($dadosFormulario['cor'] ?? ''); ?>
            <input type="text" class="form-control" id="cor" name="cor" required autocomplete="off" value="<?= htmlspecialchars($corSelecionada) ?>">
            <ul id="cor-sugestoes" class="autocomplete-sugestoes list-group position-absolute w-100 d-none" style="top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></ul>
          </div>
          <div class="form-group">
            <label for="preco_kilo">Preço por Kg (R$)</label>
            <input type="number" step="0.01" class="form-control" id="preco_kilo" name="preco_kilo" required value="<?= htmlspecialchars((string) ($dadosFormulario['preco_kilo'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="link_compra">Link de Compra do Produto</label>
            <input type="url" class="form-control" id="link_compra" name="link_compra" placeholder="https://www.loja.com/produto" value="<?= htmlspecialchars((string) ($dadosFormulario['link_compra'] ?? '')) ?>">
          </div>
        </div>
      </div>
    </div>
    <!-- Fim card-body -->
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=resinas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script>
window.addEventListener('DOMContentLoaded', function () {
  // Função genérica para autocomplete
  function setupAutocomplete(input, ul, lista) {
    function renderizarSugestoes(termo) {
      if (!Array.isArray(lista)) return;
      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = lista.filter(function(item) {
        if (typeof item !== 'string') return false;
        if (termoNormalizado === '') return true;
        return item.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
      }).slice(0, 10);
      ul.innerHTML = '';
      if (!dadosFiltrados.length) {
        ul.classList.remove('d-block');
        ul.classList.add('d-none');
        return;
      }
      dadosFiltrados.forEach(function(sugestao) {
        var li = document.createElement('li');
        li.textContent = sugestao;
        li.className = 'list-group-item list-group-item-action';
        li.addEventListener('mousedown', function(e) {
          e.preventDefault();
          input.value = sugestao;
          ul.classList.remove('d-block');
          ul.classList.add('d-none');
          input.blur();
        });
        ul.appendChild(li);
      });
      ul.classList.remove('d-none');
      ul.classList.add('d-block');
    }
    if (input && ul) {
      input.addEventListener('input', function() {
        var termo = this.value.trim();
        renderizarSugestoes(termo);
      });
      input.addEventListener('focus', function() {
        renderizarSugestoes(this.value.trim());
      });
      input.addEventListener('blur', function() {
        setTimeout(function() {
          ul.classList.remove('d-block');
          ul.classList.add('d-none');
        }, 120);
      });
    }
  }

  var inputCor = document.getElementById('cor');
  var corSugestoesList = document.getElementById('cor-sugestoes');
  var coresDisponiveis = <?= json_encode(array_values(array_unique(array_filter(array_map('trim', is_array($coresDisponiveis) ? $coresDisponiveis : []), static function ($cor) { return $cor !== ''; }))), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  setupAutocomplete(inputCor, corSugestoesList, coresDisponiveis);

  var inputNome = document.getElementById('nome');
  var nomeSugestoesList = document.getElementById('nome-sugestoes');
  var nomesDisponiveis = <?= json_encode(array_values(array_unique(array_filter(array_map('trim', is_array($nomesDisponiveis) ? $nomesDisponiveis : []), static function ($n) { return $n !== ''; }))), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  setupAutocomplete(inputNome, nomeSugestoesList, nomesDisponiveis);

  var inputMarca = document.getElementById('marca');
  var marcaSugestoesList = document.getElementById('marca-sugestoes');
  var marcasDisponiveis = <?= json_encode(array_values(array_unique(array_filter(array_map('trim', is_array($marcasDisponiveis) ? $marcasDisponiveis : []), static function ($m) { return $m !== ''; }))), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  setupAutocomplete(inputMarca, marcaSugestoesList, marcasDisponiveis);

  // Preview e remoção da capa
  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  if (inputFoto && previewImagem && capaPlaceholder && removeCapaBtn) {
    function renderizarCapaExistente() {
      previewImagem.src = '';
      previewImagem.classList.add('d-none');
      capaPlaceholder.classList.remove('d-none');
      capaPlaceholder.style.display = 'flex';
      removeCapaBtn.classList.add('d-none');
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
      inputFoto.value = '';
      renderizarCapaExistente();
    });
  }
});
</script>