<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Torres\TorreController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$usuario_uuid = trim($_SESSION['usuario_uuid'] ?? '');
$erro = '';
$impressora_id = (int) ($_GET['impressora_id'] ?? 0);
$filamento_id = (int) ($_GET['filamento_id'] ?? 0);
$resina_id = (int) ($_GET['resina_id'] ?? 0);
$selecao_confirmacao = null;
$aviso_selecao = '';
$estudios_disponiveis = [];
$colecoes_disponiveis = [];
$tematicas_disponiveis = [];
$outras_caracteristicas_disponiveis = [];

$torreController = new TorreController($pdo);

$contextoAdicao = $torreController->carregarContextoAdicao($usuario_id, $usuario_uuid, $impressora_id, $filamento_id, $resina_id);
$usuario_uuid = (string) ($contextoAdicao['usuario_uuid'] ?? $usuario_uuid);
$selecao_confirmacao = $contextoAdicao['selecao_confirmacao'] ?? null;
$aviso_selecao = (string) ($contextoAdicao['aviso_selecao'] ?? '');
$estudios_disponiveis = is_array($contextoAdicao['estudios_disponiveis'] ?? null) ? $contextoAdicao['estudios_disponiveis'] : [];
$colecoes_disponiveis = is_array($contextoAdicao['colecoes_disponiveis'] ?? null) ? $contextoAdicao['colecoes_disponiveis'] : [];
$tematicas_disponiveis = is_array($contextoAdicao['tematicas_disponiveis'] ?? null) ? $contextoAdicao['tematicas_disponiveis'] : [];
$outras_caracteristicas_disponiveis = is_array($contextoAdicao['outras_caracteristicas_disponiveis'] ?? null) ? $contextoAdicao['outras_caracteristicas_disponiveis'] : [];
$dadosFormulario = $torreController->montarEstadoFormularioAdicao($_POST ?? []);

  $foto = null;
  $imagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoFluxo = $torreController->processarFluxoAdicao(
      $usuario_id,
      $usuario_uuid,
      $_POST,
      $_FILES,
      $selecao_confirmacao
    );

    $foto = $resultadoFluxo['foto'] ?? $foto;
    $imagens = is_array($resultadoFluxo['imagens'] ?? null) ? $resultadoFluxo['imagens'] : $imagens;

    if (!empty($resultadoFluxo['sucesso'])) {
      $torreId = (int) ($resultadoFluxo['torre_id'] ?? 0);
      echo '<script>window.location.href="?pagina=torres&acao=visualizar&id=' . $torreId . '";</script>';
      exit;
    }

    $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar torre.');
}
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Torre de Dados</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="form-adicionar-torre">
    <input type="hidden" id="foto_existente" name="foto_existente" value="<?= htmlspecialchars((string) ($foto ?? '')) ?>">
    <input type="hidden" id="imagens_existentes" name="imagens_existentes" value="<?= htmlspecialchars(json_encode($imagens ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <?php if ($selecao_confirmacao): ?>
        <h5 class="mb-3">Seleção confirmada</h5>
        <div class="selecao-grid mb-4">
          <div class="selecao-card">
            <div class="selecao-icon"><i class="fas fa-microscope"></i></div>
            <div class="selecao-content">
              <h2><?= htmlspecialchars($selecao_confirmacao['impressora']['marca'] . ' ' . $selecao_confirmacao['impressora']['modelo']) ?></h2>
              <p>
                <strong>Tipo:</strong> <?= htmlspecialchars($selecao_confirmacao['impressora']['tipo']) ?><br>
                <strong>Custo Hora:</strong> R$ <?= number_format((float) ($selecao_confirmacao['impressora']['custo_hora'] ?? 0), 4, ',', '.') ?>
              </p>
            </div>
          </div>

          <div class="selecao-card">
            <div class="selecao-icon">
              <i class="<?= $selecao_confirmacao['material_tipo'] === 'Resina' ? 'fa-solid fa-bottle-water' : 'fas fa-compact-disc' ?>"></i>
            </div>
            <div class="selecao-content">
              <h2><?= htmlspecialchars($selecao_confirmacao['material']['nome']) ?></h2>
              <p>
                <strong>Tipo:</strong> <?= htmlspecialchars($selecao_confirmacao['material_tipo']) ?><br>
                <strong>Marca:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['marca']) ?><br>
                <strong>Cor:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['cor']) ?>
                <?php if (!empty($selecao_confirmacao['material']['tipo'])): ?>
                  <br><strong>Subtipo:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['tipo']) ?>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      <?php elseif ($aviso_selecao): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($aviso_selecao) ?></div>
      <?php endif; ?>

      <style>
        .selecao-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
          gap: 20px;
        }

        .selecao-card {
          position: relative;
          background: #fff;
          border-radius: 12px;
          padding: 28px 24px;
          box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
          border: 1px solid #e9ecef;
          display: flex;
          flex-direction: row;
          align-items: flex-start;
          gap: 16px;
          min-height: 180px;
        }

        .selecao-icon {
          font-size: 2.2rem;
          color: #007bff;
          margin-top: 2px;
          flex-shrink: 0;
        }

        .selecao-content {
          flex: 1;
        }

        .selecao-card h2 {
          font-size: 1.35rem;
          font-weight: 600;
          margin-bottom: 10px;
          color: #343a40;
        }

        .selecao-card p {
          color: #6c757d;
          font-size: 0.95rem;
          margin-bottom: 0;
        }

        .outras_caracteristicas-container,
        .colecao-container {
          border: 1px solid #ced4da;
          border-radius: 0.25rem;
          padding: 0.25rem 0.5rem;
          min-height: calc(2.25rem + 2px);
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          gap: 0.3rem;
          position: relative;
          background: #fff;
        }

        .colecao-input,
        .outras_caracteristicas-input {
          border: none;
          outline: none;
          flex: 1;
          min-width: 180px;
          padding: 0.2rem;
        }

        .colecao-tag,
        .outras_caracteristicas-tag {
          background: #e9f2ff;
          color: #004085;
          border: 1px solid #b8daff;
          border-radius: 16px;
          padding: 0.2rem 0.55rem;
          font-size: 0.85rem;
          display: inline-flex;
          align-items: center;
          gap: 0.35rem;
        }

        .colecao-tag small,
        .outras_caracteristicas-tag small {
          cursor: pointer;
          font-size: 1rem;
          line-height: 1;
          font-weight: 700;
        }

        .autocomplete-list {
          list-style: none;
          margin: 0.25rem 0 0;
          padding: 0;
          border: 1px solid #dee2e6;
          border-radius: 0.25rem;
          background: #fff;
          max-height: 180px;
          overflow-y: auto;
          display: none;
          position: absolute;
          z-index: 1050;
          width: 100%;
          left: 0;
          top: 100%;
        }

        .autocomplete-list.active {
          display: block;
        }

        .autocomplete-list li {
          padding: 0.45rem 0.65rem;
          cursor: pointer;
        }

        .autocomplete-list li:hover,
        .autocomplete-list li.selected {
          background: #f1f3f5;
        }
      </style>

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
            <div class="form-group col-md-12">
              <label for="nome">Nome *</label>
              <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars((string) ($dadosFormulario['nome'] ?? '')) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="nome_original">Nome Original</label>
              <input type="text" class="form-control" id="nome_original" name="nome_original" value="<?= htmlspecialchars((string) ($dadosFormulario['nome_original'] ?? '')) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="estudio">Estúdio</label>
              <input type="text" class="form-control" id="estudio" name="estudio" value="<?= htmlspecialchars((string) ($dadosFormulario['estudio'] ?? '')) ?>" placeholder="Digite ou selecione um estúdio" autocomplete="off">
              <ul id="estudio-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="colecao-input">Coleção</label>
              <div class="colecao-container">
                <div id="colecao-tags" class="colecao-tags"></div>
                <input type="text" class="colecao-input" id="colecao-input" placeholder="Digite uma coleção..." autocomplete="off">
                <input type="hidden" id="colecao" name="colecao" value="<?= htmlspecialchars((string) ($dadosFormulario['colecao'] ?? '')) ?>">
              </div>
              <ul id="colecao-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="tematica">Temática
                <i id="tematica-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="tematica" name="tematica">
                <option value="">-- Selecione --</option>
                <option value="Cyberpunk" <?= (($dadosFormulario['tematica'] ?? '') === 'Cyberpunk') ? 'selected' : '' ?>>Cyberpunk</option>
                <option value="Fantasia" <?= (($dadosFormulario['tematica'] ?? '') === 'Fantasia') ? 'selected' : '' ?>>Fantasia</option>
                <option value="Faroeste" <?= (($dadosFormulario['tematica'] ?? '') === 'Faroeste') ? 'selected' : '' ?>>Faroeste</option>
                <option value="Horror Cósmico" <?= (($dadosFormulario['tematica'] ?? '') === 'Horror Cósmico') ? 'selected' : '' ?>>Horror Cósmico</option>
                <option value="Paranormal" <?= (($dadosFormulario['tematica'] ?? '') === 'Paranormal') ? 'selected' : '' ?>>Paranormal</option>
                <option value="Space Opera" <?= (($dadosFormulario['tematica'] ?? '') === 'Space Opera') ? 'selected' : '' ?>>Space Opera</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="outras_caracteristicas-input">Outras Características</label>
              <div class="outras_caracteristicas-container">
                <div id="outras_caracteristicas-tags" class="outras_caracteristicas-tags"></div>
                <input type="text" class="outras_caracteristicas-input" id="outras_caracteristicas-input" placeholder="Digite uma característica..." autocomplete="off">
                <input type="hidden" id="outras_caracteristicas" name="outras_caracteristicas" value="<?= htmlspecialchars((string) ($dadosFormulario['outras_caracteristicas'] ?? '')) ?>">
              </div>
              <ul id="outras_caracteristicas-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="descricao_produto">Descrição</label>
          <textarea class="form-control" id="descricao_produto" name="descricao_produto" rows="2"><?= htmlspecialchars((string) ($dadosFormulario['descricao_produto'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="form-group">
        <label for="fotos">Imagens</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="fotos" name="fotos[]" accept=".jpg,.png,.webp" multiple>
          <label class="custom-file-label" for="fotos" data-browse="Escolher arquivo">Nenhum arquivo escolhido</label>
        </div>
        <small class="form-text text-muted">Formatos: JPG, PNG, WEBP (max 2MB por arquivo)</small>
        <div class="mt-2 d-none" id="preview-imagens-container"></div>
      </div>

      <hr>
      <h5>Dados Técnicos da Impressão</h5>

      <div class="form-row">
        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="gramas">Gramas (g)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="gramas" name="gramas" value="<?= htmlspecialchars((string) ($dadosFormulario['gramas'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-8">
              <label>Tempo de impressão (dias, horas, minutos)</label>
              <div class="form-row">
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_dias" name="tempo_dias" placeholder="Dias" value="<?= htmlspecialchars((string) ($dadosFormulario['tempo_dias'] ?? '')) ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_horas" name="tempo_horas" placeholder="Horas" value="<?= htmlspecialchars((string) ($dadosFormulario['tempo_horas'] ?? '')) ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_minutos" name="tempo_minutos" placeholder="Min" value="<?= htmlspecialchars((string) ($dadosFormulario['tempo_minutos'] ?? '')) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="unidades_produzidas">Unidades Produzidas</label>
              <input type="number" min="0" class="form-control" id="unidades_produzidas" name="unidades_produzidas" value="<?= htmlspecialchars((string) ($dadosFormulario['unidades_produzidas'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="taxa_falha">Taxa de Falha (%)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="taxa_falha" name="taxa_falha" value="<?= htmlspecialchars((string) ($dadosFormulario['taxa_falha'] ?? '10')) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="markup_consumidor_final">Markup Consumidor Final</label>
              <select class="form-control" id="markup_consumidor_final" name="markup_consumidor_final">
                <option value="">-- Selecione --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <option value="<?= $i ?>" <?= (string)($dadosFormulario['markup_consumidor_final'] ?? '5') === (string)$i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars((string) ($dadosFormulario['observacoes'] ?? '')) ?></textarea>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary" id="btn-salvar-torre">Salvar</button>
      <a href="?pagina=torres" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<div class="modal fade" id="tematicaInfoModal" tabindex="-1" role="dialog" aria-labelledby="tematicaInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tematicaInfoModalLabel">Tipos de Temática</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="tematica-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var estudiosDisponiveis = <?= json_encode($estudios_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var colecoesDisponiveis = <?= json_encode($colecoes_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var tematicasDisponiveis = <?= json_encode($tematicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var outrasCaracteristicasDisponiveis = <?= json_encode($outras_caracteristicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  var inputEstudio = document.getElementById('estudio');
  var estudioSugestoesList = document.getElementById('estudio-sugestoes');
  var inputColecao = document.getElementById('colecao-input');
  var colecaoTagsContainer = document.getElementById('colecao-tags');
  var colecaoHiddenInput = document.getElementById('colecao');
  var colecaoSugestoesList = document.getElementById('colecao-sugestoes');
  var selectTematica = document.getElementById('tematica');
  var tooltipTematica = document.getElementById('tematica-tooltip-trigger');
  var tematicaModalBody = document.getElementById('tematica-modal-body');

  var outrasCaracteristicasInput = document.getElementById('outras_caracteristicas-input');
  var outrasCaracteristicasTagsContainer = document.getElementById('outras_caracteristicas-tags');
  var outrasCaracteristicasHiddenInput = document.getElementById('outras_caracteristicas');
  var outrasCaracteristicasSugestoesList = document.getElementById('outras_caracteristicas-sugestoes');

  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var inputFotos = document.getElementById('fotos');
  var formAdicionarTorre = document.getElementById('form-adicionar-torre');
  var btnSalvarTorre = document.getElementById('btn-salvar-torre');
  var labelFotos = document.querySelector('label.custom-file-label[for="fotos"]');
  var previewImagensContainer = document.getElementById('preview-imagens-container');
  var fotoExistenteInput = document.getElementById('foto_existente');
  var imagensExistentesInput = document.getElementById('imagens_existentes');

  var estudiosNomesDisponiveis = Array.isArray(estudiosDisponiveis)
    ? Array.from(new Set(estudiosDisponiveis
      .map(function (itemEstudio) {
        return itemEstudio && typeof itemEstudio.nome === 'string' ? itemEstudio.nome.trim() : '';
      })
      .filter(function (nomeEstudio) {
        return nomeEstudio !== '';
      })))
    : [];

  var colecoesNomesDisponiveis = Array.isArray(colecoesDisponiveis)
    ? Array.from(new Set(colecoesDisponiveis
      .map(function (itemColecao) {
        return itemColecao && typeof itemColecao.nome === 'string' ? itemColecao.nome.trim() : '';
      })
      .filter(function (nomeColecao) {
        return nomeColecao !== '';
      })))
    : [];

  var initAutocompleteField = function (inputElement, sugestoesList, options) {
    if (!inputElement || !sugestoesList) {
      return;
    }

    options = options || {};
    var minChars = Number.isFinite(Number(options.minChars)) ? Number(options.minChars) : 2;
    var showOnFocus = options.showOnFocus === true;
    var indiceSelecionado = -1;

    var fecharSugestoes = function () {
      sugestoesList.classList.remove('active');
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;
    };

    var renderizarSugestoes = function (termo) {
      if (!Array.isArray(options.localSuggestions)) {
        return;
      }

      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = options.localSuggestions
        .filter(function (itemSugestao) {
          if (typeof itemSugestao !== 'string') {
            return false;
          }

          if (termoNormalizado === '') {
            return true;
          }

          return itemSugestao.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 10);

      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;

      if (!dadosFiltrados.length) {
        fecharSugestoes();
        return;
      }

      dadosFiltrados.forEach(function (sugestao, index) {
        var li = document.createElement('li');
        li.textContent = sugestao;
        li.dataset.index = String(index);
        li.addEventListener('click', function () {
          inputElement.value = sugestao;
          fecharSugestoes();
          inputElement.focus();
        });
        sugestoesList.appendChild(li);
      });

      sugestoesList.classList.add('active');
    };

    var atualizarSelecao = function (listaItens) {
      listaItens.forEach(function (li, index) {
        li.classList.toggle('selected', index === indiceSelecionado);
      });
    };

    inputElement.addEventListener('input', function () {
      var termo = this.value.trim();

      if (termo.length < minChars) {
        if (showOnFocus && termo.length === 0) {
          renderizarSugestoes('');
          return;
        }
        fecharSugestoes();
        return;
      }

      renderizarSugestoes(termo);
    });

    inputElement.addEventListener('focus', function () {
      if (!showOnFocus) {
        return;
      }
      renderizarSugestoes(this.value.trim());
    });

    inputElement.addEventListener('keydown', function (e) {
      var listaItens = sugestoesList.querySelectorAll('li');

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceSelecionado = Math.min(indiceSelecionado + 1, listaItens.length - 1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceSelecionado = Math.max(indiceSelecionado - 1, -1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'Enter') {
        if (indiceSelecionado >= 0 && listaItens[indiceSelecionado]) {
          e.preventDefault();
          inputElement.value = listaItens[indiceSelecionado].textContent || '';
          fecharSugestoes();
        }
      } else if (e.key === 'Escape') {
        fecharSugestoes();
      }
    });

    document.addEventListener('click', function (e) {
      if (e.target !== inputElement && !sugestoesList.contains(e.target)) {
        fecharSugestoes();
      }
    });
  };

  var initTagsField = function (inputElement, tagsContainer, hiddenInput, sugestoesList, fieldName, options) {
    if (!inputElement || !tagsContainer || !hiddenInput || !sugestoesList) {
      return;
    }

    options = options || {};
    var minChars = Number.isFinite(Number(options.minChars)) ? Number(options.minChars) : 2;
    var showOnFocus = options.showOnFocus === true;

    var itens = [];
    var indiceSelecionado = -1;
    var ignorarBlurUmaVez = false;

    var valorInicial = (hiddenInput.value || '').trim();
    if (valorInicial !== '') {
      itens = valorInicial.split(',').map(function (item) { return item.trim(); }).filter(function (item) { return item.length > 0; });
    }

    var atualizarDisplay = function () {
      tagsContainer.innerHTML = '';

      itens.forEach(function (item, index) {
        var tag = document.createElement('div');
        tag.className = fieldName + '-tag';
        tag.innerHTML = item + '<small>×</small>';

        var botaoRemover = tag.querySelector('small');
        if (botaoRemover) {
          botaoRemover.addEventListener('click', function (e) {
            e.stopPropagation();
            itens.splice(index, 1);
            atualizarDisplay();
          });
        }

        tagsContainer.appendChild(tag);
      });

      hiddenInput.value = itens.join(',');
    };

    var fecharSugestoes = function () {
      sugestoesList.classList.remove('active');
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;
    };

    var adicionarItem = function (item) {
      var valor = (item || '').trim();
      if (valor === '') {
        return;
      }

      var existe = itens.some(function (itemAtual) {
        return itemAtual.toLocaleLowerCase() === valor.toLocaleLowerCase();
      });

      if (!existe) {
        itens.push(valor);
        atualizarDisplay();
      }

      inputElement.value = '';
      fecharSugestoes();
      inputElement.focus();
    };

    var atualizarSelecao = function (listaItens) {
      listaItens.forEach(function (li, index) {
        li.classList.toggle('selected', index === indiceSelecionado);
      });
    };

    var renderizarSugestoesLocais = function (termo) {
      if (!Array.isArray(options.localSuggestions)) {
        return false;
      }

      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = options.localSuggestions
        .filter(function (itemSugestao) {
          if (typeof itemSugestao !== 'string') {
            return false;
          }

          if (termoNormalizado === '') {
            return true;
          }

          return itemSugestao.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 10);

      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;

      if (!dadosFiltrados.length) {
        fecharSugestoes();
        return true;
      }

      dadosFiltrados.forEach(function (sugestao, index) {
        var li = document.createElement('li');
        li.textContent = sugestao;
        li.dataset.index = String(index);
        li.addEventListener('click', function () {
          adicionarItem(sugestao);
        });
        sugestoesList.appendChild(li);
      });

      sugestoesList.classList.add('active');
      return true;
    };

    inputElement.addEventListener('input', function () {
      var termo = this.value.trim();

      if (termo.length < minChars) {
        fecharSugestoes();
        if (showOnFocus && termo.length === 0) {
          renderizarSugestoesLocais('');
        }
        return;
      }

      renderizarSugestoesLocais(termo);
    });

    inputElement.addEventListener('keydown', function (e) {
      var listaItens = sugestoesList.querySelectorAll('li');

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceSelecionado = Math.min(indiceSelecionado + 1, listaItens.length - 1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceSelecionado = Math.max(indiceSelecionado - 1, -1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indiceSelecionado >= 0 && listaItens[indiceSelecionado]) {
          adicionarItem(listaItens[indiceSelecionado].textContent || '');
        } else {
          adicionarItem(this.value);
        }
      } else if (e.key === 'Escape') {
        fecharSugestoes();
      }
    });

    sugestoesList.addEventListener('mousedown', function () {
      ignorarBlurUmaVez = true;
    });

    inputElement.addEventListener('blur', function () {
      var valorPendente = this.value.trim();

      if (ignorarBlurUmaVez) {
        ignorarBlurUmaVez = false;
        return;
      }

      if (valorPendente !== '') {
        adicionarItem(valorPendente);
      } else {
        fecharSugestoes();
      }
    });

    inputElement.addEventListener('focus', function () {
      if (!showOnFocus) {
        return;
      }
      renderizarSugestoesLocais(this.value.trim());
    });

    document.addEventListener('click', function (e) {
      if (e.target !== inputElement && !sugestoesList.contains(e.target)) {
        fecharSugestoes();
      }
    });

    tagsContainer.addEventListener('click', function () {
      inputElement.focus();
    });

    atualizarDisplay();
  };

  initAutocompleteField(inputEstudio, estudioSugestoesList, {
    localSuggestions: estudiosNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  initTagsField(
    inputColecao,
    colecaoTagsContainer,
    colecaoHiddenInput,
    colecaoSugestoesList,
    'colecao',
    {
      localSuggestions: colecoesNomesDisponiveis,
      minChars: 0,
      showOnFocus: true
    }
  );

  initTagsField(
    outrasCaracteristicasInput,
    outrasCaracteristicasTagsContainer,
    outrasCaracteristicasHiddenInput,
    outrasCaracteristicasSugestoesList,
    'outras_caracteristicas',
    {
      localSuggestions: outrasCaracteristicasDisponiveis,
      minChars: 0,
      showOnFocus: true
    }
  );

  if (selectTematica && tooltipTematica) {
    var descricoesTematica = {
      'Fantasia': {
        sistema: 'Dungeons & Dragons',
        foco: 'Aventura e Poder'
      },
      'Horror Cósmico': {
        sistema: 'Call of Cthulhu',
        foco: 'Mistério e Fragilidade Humana'
      },
      'Paranormal': {
        sistema: 'Ordem Paranormal',
        foco: 'Investigação e Combate ao Oculto'
      },
      'Cyberpunk': {
        sistema: 'Cyberpunk 2020/RED',
        foco: 'Rebeldia e Tecnologia'
      },
      'Space Opera': {
        sistema: 'Star Wars / Starfinder',
        foco: 'Exploração Espacial e Política Galáctica'
      },
      'Faroeste': {
        sistema: 'Deadlands',
        foco: 'Duelos e Exploração de Fronteira'
      }
    };

    var opcoesTematica = Array.from(selectTematica.options)
      .filter(function (opcao) { return opcao.value !== ''; })
      .map(function (opcao) { return opcao.text; });

    var linhasTematica = opcoesTematica.map(function (tematicaNome) {
      var dadosTematica = descricoesTematica[tematicaNome] || { sistema: '', foco: '' };
      return '<tr>'
        + '<td><strong>' + tematicaNome + '</strong></td>'
        + '<td>' + dadosTematica.sistema + '</td>'
        + '<td>' + dadosTematica.foco + '</td>'
        + '</tr>';
    }).join('');

    var tabelaTematicaHtml = ''
      + '<div class="table-responsive">'
      + '<table class="table table-sm table-bordered mb-0">'
      + '<thead><tr><th>Temática</th><th>Exemplo de Sistema</th><th>Foco Principal</th></tr></thead>'
      + '<tbody>' + linhasTematica + '</tbody>'
      + '</table>'
      + '</div>';

    if (tematicaModalBody) {
      tematicaModalBody.innerHTML = tabelaTematicaHtml;
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(tooltipTematica).off('click.tematicaModal').on('click.tematicaModal', function () {
        window.jQuery('#tematicaInfoModal').modal('show');
      });
    }
  }

  if (inputFoto && previewImagem && capaPlaceholder && removeCapaBtn) {
    var renderizarCapaExistente = function () {
      var caminhoCapaExistente = fotoExistenteInput && typeof fotoExistenteInput.value === 'string'
        ? fotoExistenteInput.value.trim()
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

      if (fotoExistenteInput) {
        fotoExistenteInput.value = '';
      }

      renderizarCapaExistente();
    });
  }

  if (inputFotos && labelFotos && previewImagensContainer) {
    var arquivosSelecionados = [];
    var imagensPersistidas = [];

    if (imagensExistentesInput && typeof imagensExistentesInput.value === 'string' && imagensExistentesInput.value.trim() !== '') {
      try {
        var imagensPersistidasRaw = JSON.parse(imagensExistentesInput.value);
        if (Array.isArray(imagensPersistidasRaw)) {
          imagensPersistidas = imagensPersistidasRaw
            .filter(function (item) { return typeof item === 'string' && item.trim() !== ''; })
            .map(function (item) { return item.trim(); });
        }
      } catch (e) {
        imagensPersistidas = [];
      }
    }

    var sincronizarImagensPersistidas = function () {
      if (!imagensExistentesInput) {
        return;
      }
      imagensExistentesInput.value = JSON.stringify(imagensPersistidas);
    };

    var sincronizarInputArquivos = function () {
      var dataTransfer = new DataTransfer();
      arquivosSelecionados.forEach(function (arquivo) {
        dataTransfer.items.add(arquivo);
      });
      inputFotos.files = dataTransfer.files;
    };

    var atualizarListaArquivos = function () {
      var totalArquivos = imagensPersistidas.length + arquivosSelecionados.length;

      if (!totalArquivos) {
        labelFotos.textContent = 'Nenhum arquivo escolhido';
        previewImagensContainer.innerHTML = '';
        previewImagensContainer.classList.add('d-none');
        return;
      }

      labelFotos.textContent = totalArquivos === 1
        ? '1 arquivo selecionado'
        : totalArquivos + ' arquivos selecionados';

      previewImagensContainer.innerHTML = '';
      previewImagensContainer.classList.remove('d-none');

      imagensPersistidas.forEach(function (caminhoImagem, indicePersistido) {
        var wrapperPersistido = document.createElement('div');
        wrapperPersistido.style.position = 'relative';
        wrapperPersistido.style.display = 'inline-block';
        wrapperPersistido.className = 'mr-2 mb-2';

        var imagemPersistida = document.createElement('img');
        imagemPersistida.src = caminhoImagem;
        imagemPersistida.alt = 'Pré-visualização';
        imagemPersistida.className = 'img-thumbnail';
        imagemPersistida.style.width = '90px';
        imagemPersistida.style.height = '90px';
        imagemPersistida.style.objectFit = 'cover';

        var botaoRemoverPersistido = document.createElement('button');
        botaoRemoverPersistido.type = 'button';
        botaoRemoverPersistido.innerHTML = '&times;';
        botaoRemoverPersistido.setAttribute('aria-label', 'Remover imagem');
        botaoRemoverPersistido.style.position = 'absolute';
        botaoRemoverPersistido.style.top = '2px';
        botaoRemoverPersistido.style.right = '2px';
        botaoRemoverPersistido.style.width = '22px';
        botaoRemoverPersistido.style.height = '22px';
        botaoRemoverPersistido.style.border = 'none';
        botaoRemoverPersistido.style.borderRadius = '50%';
        botaoRemoverPersistido.style.background = 'rgba(220,53,69,0.9)';
        botaoRemoverPersistido.style.color = '#fff';
        botaoRemoverPersistido.style.fontSize = '16px';
        botaoRemoverPersistido.style.lineHeight = '20px';
        botaoRemoverPersistido.style.cursor = 'pointer';
        botaoRemoverPersistido.style.padding = '0';

        botaoRemoverPersistido.addEventListener('click', function () {
          imagensPersistidas.splice(indicePersistido, 1);
          sincronizarImagensPersistidas();
          atualizarListaArquivos();
        });

        wrapperPersistido.appendChild(imagemPersistida);
        wrapperPersistido.appendChild(botaoRemoverPersistido);
        previewImagensContainer.appendChild(wrapperPersistido);
      });

      arquivosSelecionados.forEach(function (arquivo, indice) {
        if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
          return;
        }

        var leitor = new FileReader();
        leitor.onload = function (evento) {
          var wrapper = document.createElement('div');
          wrapper.style.position = 'relative';
          wrapper.style.display = 'inline-block';
          wrapper.className = 'mr-2 mb-2';

          var imagem = document.createElement('img');
          imagem.src = evento.target.result;
          imagem.alt = 'Pré-visualização';
          imagem.className = 'img-thumbnail';
          imagem.style.width = '90px';
          imagem.style.height = '90px';
          imagem.style.objectFit = 'cover';

          var botaoRemover = document.createElement('button');
          botaoRemover.type = 'button';
          botaoRemover.innerHTML = '&times;';
          botaoRemover.setAttribute('aria-label', 'Remover imagem');
          botaoRemover.style.position = 'absolute';
          botaoRemover.style.top = '2px';
          botaoRemover.style.right = '2px';
          botaoRemover.style.width = '22px';
          botaoRemover.style.height = '22px';
          botaoRemover.style.border = 'none';
          botaoRemover.style.borderRadius = '50%';
          botaoRemover.style.background = 'rgba(220,53,69,0.9)';
          botaoRemover.style.color = '#fff';
          botaoRemover.style.fontSize = '16px';
          botaoRemover.style.lineHeight = '20px';
          botaoRemover.style.cursor = 'pointer';
          botaoRemover.style.padding = '0';

          botaoRemover.addEventListener('click', function () {
            arquivosSelecionados.splice(indice, 1);
            sincronizarInputArquivos();
            atualizarListaArquivos();
          });

          wrapper.appendChild(imagem);
          wrapper.appendChild(botaoRemover);
          previewImagensContainer.appendChild(wrapper);
        };
        leitor.readAsDataURL(arquivo);
      });
    };

    inputFotos.addEventListener('change', function () {
      var novosArquivos = this.files ? Array.from(this.files) : [];

      novosArquivos.forEach(function (novoArquivo) {
        var jaExiste = arquivosSelecionados.some(function (arquivoAtual) {
          return arquivoAtual.name === novoArquivo.name
            && arquivoAtual.size === novoArquivo.size
            && arquivoAtual.lastModified === novoArquivo.lastModified;
        });

        if (!jaExiste) {
          arquivosSelecionados.push(novoArquivo);
        }
      });

      sincronizarInputArquivos();
      atualizarListaArquivos();
    });

    sincronizarImagensPersistidas();
    atualizarListaArquivos();
  }

  if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.summernote === 'function') {
    window.jQuery('#descricao_produto').summernote({
      placeholder: 'Place some text here',
      height: 180
    });
  }

  if (formAdicionarTorre && btnSalvarTorre) {
    formAdicionarTorre.addEventListener('submit', function () {
      btnSalvarTorre.disabled = true;
      btnSalvarTorre.textContent = 'Salvando...';
    });
  }
});
</script>
