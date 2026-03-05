<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Impressoras3d\Impressora3dController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$impressoraController = new Impressora3dController($pdo);
$dadosFormulario = $impressoraController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Passa $_FILES para o controller
  $resultadoFluxo = $impressoraController->processarFluxoAdicao((int) $usuario_id, $_POST, $_FILES);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=impressoras3d";</script>';
    exit;
  }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar.');
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Impressora 3D</h3>
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
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="marca">Marca</label>
              <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars((string) ($dadosFormulario['marca'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-6">
              <label for="modelo">Modelo</label>
              <input type="text" class="form-control" id="modelo" name="modelo" required value="<?= htmlspecialchars((string) ($dadosFormulario['modelo'] ?? '')) ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="tipo">Tipo</label>
            <?php $tipoSelecionado = (string) ($dadosFormulario['tipo'] ?? ''); ?>
            <select class="form-control" id="tipo" name="tipo" required>
              <option value="">Selecione...</option>
              <option value="FDM" <?= $tipoSelecionado === 'FDM' ? 'selected' : '' ?>>FDM</option>
              <option value="Resina" <?= $tipoSelecionado === 'Resina' ? 'selected' : '' ?>>Resina</option>
            </select>
          </div>
          <div class="form-group">
            <label for="preco_aquisicao">Preço de Aquisição (R$)</label>
            <input type="number" step="0.01" class="form-control" id="preco_aquisicao" name="preco_aquisicao" required value="<?= htmlspecialchars((string) ($dadosFormulario['preco_aquisicao'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="potencia">Potência (W)</label>
            <input type="number" class="form-control" id="potencia" name="potencia" required value="<?= htmlspecialchars((string) ($dadosFormulario['potencia'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="depreciacao">Depreciação (%)</label>
            <input type="number" class="form-control" id="depreciacao" name="depreciacao" placeholder="25" required value="<?= htmlspecialchars((string) ($dadosFormulario['depreciacao'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="tempo_vida_util">Tempo Vida Útil (h)</label>
            <input type="number" class="form-control" id="tempo_vida_util" name="tempo_vida_util" required value="<?= htmlspecialchars((string) ($dadosFormulario['tempo_vida_util'] ?? '')) ?>">
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

    var renderizarCapaExistente = function () {
      // Não há capa persistida neste formulário, apenas preview do upload
      previewImagem.src = '';
      previewImagem.classList.add('d-none');
      capaPlaceholder.classList.remove('d-none');
      capaPlaceholder.style.display = 'flex';
      removeCapaBtn.classList.add('d-none');
    };

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
      renderizarCapaExistente();
    });
  });
  </script>