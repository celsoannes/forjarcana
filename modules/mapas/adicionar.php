<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Mapas\MapaController;

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = trim($_SESSION['usuario_uuid'] ?? '');
$fluxoOrigemPost = trim((string) (filter_input(INPUT_POST, 'fluxo_origem', FILTER_UNSAFE_RAW) ?? ''));
$fluxo_origem = trim((string) ($_GET['fluxo'] ?? $fluxoOrigemPost));
$erro = '';
$foto = null;
$imagens = [];
$avisos_upload = [];
$fornecedores_disponiveis = [];

$mapaController = new MapaController($pdo);

$contextoAdicao = $mapaController->carregarContextoAdicao((int) $usuario_id, $usuario_uuid);
$usuario_uuid = (string) ($contextoAdicao['usuario_uuid'] ?? $usuario_uuid);
$fornecedores_disponiveis = is_array($contextoAdicao['fornecedores_disponiveis'] ?? null) ? $contextoAdicao['fornecedores_disponiveis'] : [];
$dadosFormulario = $mapaController->montarEstadoFormularioAdicao($_POST ?? []);

if (($_GET['action'] ?? '') === 'sugerir') {
  header('Content-Type: application/json; charset=UTF-8');

  $campo = trim((string) ($_GET['campo'] ?? ''));
  $termo = trim((string) ($_GET['termo'] ?? ''));
  $sugestoes = $mapaController->sugerirCampo((int) $usuario_id, $campo, $termo);
  echo json_encode($sugestoes, JSON_UNESCAPED_UNICODE);

  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoFluxo = $mapaController->processarFluxoAdicao((int) $usuario_id, $usuario_uuid, $_POST, $_FILES);

    $foto = $resultadoFluxo['foto'] ?? $foto;
    $imagens = is_array($resultadoFluxo['imagens'] ?? null) ? $resultadoFluxo['imagens'] : $imagens;
    $avisos_upload = is_array($resultadoFluxo['avisos_upload'] ?? null) ? $resultadoFluxo['avisos_upload'] : [];

    if (!empty($resultadoFluxo['sucesso'])) {
      $urlRedirecionamento = ($fluxo_origem === 'mapas') ? '?pagina=produtos' : '?pagina=mapas';
      echo '<script>window.location.href=' . json_encode($urlRedirecionamento, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';</script>';
      exit;
    }

    $erro = trim((string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar mapa.'));
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Mapa</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="form-adicionar-mapa">
    <input type="hidden" name="fluxo_origem" value="<?= htmlspecialchars($fluxo_origem, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" id="foto_existente" name="foto_existente" value="<?= htmlspecialchars((string) ($foto ?? '')) ?>">
    <input type="hidden" id="imagens_existentes" name="imagens_existentes" value="<?= htmlspecialchars(json_encode($imagens ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <?php if (!empty($avisos_upload)): ?>
        <div class="alert alert-warning mb-3">
          <?php foreach ($avisos_upload as $avisoUpload): ?>
            <div><?= htmlspecialchars($avisoUpload) ?></div>
          <?php endforeach; ?>
        </div>
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
          <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars((string) ($dadosFormulario['nome'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="fornecedor">Fornecedor</label>
            <div class="position-relative">
              <input type="text" class="form-control" id="fornecedor" name="fornecedor" autocomplete="off" value="<?= htmlspecialchars((string) ($dadosFormulario['fornecedor'] ?? '')) ?>">
              <ul id="fornecedor-sugestoes" class="autocomplete-sugestoes list-group position-absolute w-100 d-none" style="top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></ul>
            </div>
          </div>
          <div class="form-group">
            <label for="material">Material</label>
            <input type="text" class="form-control" id="material" name="material" required value="<?= htmlspecialchars((string) ($dadosFormulario['material'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="formato_grade">
              Formato da Grade
              <button type="button" class="btn btn-xs btn-outline-info ml-2" data-toggle="modal" data-target="#modal-info-formato-grade">
                <i class="fas fa-info-circle"></i> Ver opções
              </button>
            </label>
            <?php $formatoGradeSelecionado = (string) ($dadosFormulario['formato_grade'] ?? ''); ?>
            <select class="form-control" id="formato_grade" name="formato_grade" required>
              <option value="">Selecione...</option>
              <option value="sq-25" <?= $formatoGradeSelecionado === 'sq-25' ? 'selected' : '' ?>>Quadrado 1" (25.4mm)</option>
              <option value="sq-38" <?= $formatoGradeSelecionado === 'sq-38' ? 'selected' : '' ?>>Quadrado 1.5" (38mm)</option>
              <option value="hx-25" <?= $formatoGradeSelecionado === 'hx-25' ? 'selected' : '' ?>>Hexágono 1" (25mm)</option>
              <option value="hx-32" <?= $formatoGradeSelecionado === 'hx-32' ? 'selected' : '' ?>>Hexágono 1.25" (32mm)</option>
              <option value="hx-12" <?= $formatoGradeSelecionado === 'hx-12' ? 'selected' : '' ?>>Hexágono Hexcrawl (12mm)</option>
              <option value="hx-30" <?= $formatoGradeSelecionado === 'hx-30' ? 'selected' : '' ?>>Escaramuça (30mm)</option>
              <option value="dt-25" <?= $formatoGradeSelecionado === 'dt-25' ? 'selected' : '' ?>>Grade de Pontos (Dots)</option>
              <option value="none" <?= $formatoGradeSelecionado === 'none' ? 'selected' : '' ?>>Sem Grade (Liso)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tamanho</label>
            <div class="row">
              <div class="col-md-6 mb-2 mb-md-0">
                <input type="number" class="form-control" id="largura" name="largura" step="0.01" min="0" placeholder="Largura" required value="<?= htmlspecialchars((string) ($dadosFormulario['largura'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <input type="number" class="form-control" id="comprimento" name="comprimento" step="0.01" min="0" placeholder="Comprimento" required value="<?= htmlspecialchars((string) ($dadosFormulario['comprimento'] ?? '')) ?>">
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="custo">Custo</label>
              <input type="number" class="form-control" id="custo" name="custo" step="0.01" min="0" required value="<?= htmlspecialchars((string) ($dadosFormulario['custo'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="unidades_produzidas">Unidades Produzidas</label>
              <input type="number" class="form-control" id="unidades_produzidas" name="unidades_produzidas" min="1" step="1" required value="<?= htmlspecialchars((string) ($dadosFormulario['unidades_produzidas'] ?? '1')) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="markup">Markup</label>
              <select class="form-control" id="markup" name="markup" required>
                <?php $markupSelecionado = (string) ($dadosFormulario['markup'] ?? '2'); ?>
                <option value="1" <?= $markupSelecionado === '1' ? 'selected' : '' ?>>1</option>
                <option value="1.5" <?= $markupSelecionado === '1.5' ? 'selected' : '' ?>>1.5</option>
                <option value="2" <?= $markupSelecionado === '2' ? 'selected' : '' ?>>2</option>
                <option value="2.5" <?= $markupSelecionado === '2.5' ? 'selected' : '' ?>>2.5</option>
                <option value="3" <?= $markupSelecionado === '3' ? 'selected' : '' ?>>3</option>
                <option value="3.5" <?= $markupSelecionado === '3.5' ? 'selected' : '' ?>>3.5</option>
                <option value="4" <?= $markupSelecionado === '4' ? 'selected' : '' ?>>4</option>
                <option value="4.5" <?= $markupSelecionado === '4.5' ? 'selected' : '' ?>>4.5</option>
                <option value="5" <?= $markupSelecionado === '5' ? 'selected' : '' ?>>5</option>
                <option value="5.5" <?= $markupSelecionado === '5.5' ? 'selected' : '' ?>>5.5</option>
                <option value="6" <?= $markupSelecionado === '6' ? 'selected' : '' ?>>6</option>
                <option value="6.5" <?= $markupSelecionado === '6.5' ? 'selected' : '' ?>>6.5</option>
                <option value="7" <?= $markupSelecionado === '7' ? 'selected' : '' ?>>7</option>
                <option value="7.5" <?= $markupSelecionado === '7.5' ? 'selected' : '' ?>>7.5</option>
                <option value="8" <?= $markupSelecionado === '8' ? 'selected' : '' ?>>8</option>
                <option value="8.5" <?= $markupSelecionado === '8.5' ? 'selected' : '' ?>>8.5</option>
                <option value="9" <?= $markupSelecionado === '9' ? 'selected' : '' ?>>9</option>
                <option value="9.5" <?= $markupSelecionado === '9.5' ? 'selected' : '' ?>>9.5</option>
                <option value="10" <?= $markupSelecionado === '10' ? 'selected' : '' ?>>10</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="link_compra">Link de Compra do Produto</label>
        <input type="url" class="form-control" id="link_compra" name="link_compra" placeholder="https://exemplo.com/produto" value="<?= htmlspecialchars((string) ($dadosFormulario['link_compra'] ?? '')) ?>">
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
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars((string) ($dadosFormulario['descricao'] ?? '')) ?></textarea>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=mapas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var fotoExistenteInput = document.getElementById('foto_existente');
  var inputFotos = document.getElementById('fotos');
  var labelFotos = document.querySelector('label.custom-file-label[for="fotos"]');
  var previewImagensContainer = document.getElementById('preview-imagens-container');
  var imagensExistentesInput = document.getElementById('imagens_existentes');
  var inputFornecedor = document.getElementById('fornecedor');
  var fornecedorSugestoesList = document.getElementById('fornecedor-sugestoes');
  var fornecedoresDisponiveis = <?= json_encode($fornecedores_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  var fornecedoresNomesDisponiveis = Array.isArray(fornecedoresDisponiveis)
    ? Array.from(new Set(fornecedoresDisponiveis
      .map(function (itemFornecedor) {
        return itemFornecedor && typeof itemFornecedor.nome_fantasia === 'string' ? itemFornecedor.nome_fantasia.trim() : '';
      })
      .filter(function (nomeFornecedor) {
        return nomeFornecedor !== '';
      })))
    : [];

  var initAutocompleteFornecedor = function (inputElement, sugestoesList, options) {
    if (!inputElement || !sugestoesList) {
      return;
    }

    options = options || {};
    var minChars = Number.isFinite(Number(options.minChars)) ? Number(options.minChars) : 2;
    var showOnFocus = options.showOnFocus === true;

    var indiceSelecionado = -1;

    var fecharSugestoes = function () {
      sugestoesList.classList.remove('d-block');
      sugestoesList.classList.add('d-none');
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;
    };

    var renderizarSugestoes = function (dadosFiltrados) {
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;

      if (!Array.isArray(dadosFiltrados) || !dadosFiltrados.length) {
        fecharSugestoes();
        return;
      }

      dadosFiltrados.forEach(function (sugestao, index) {
        var li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action py-2';
        li.textContent = sugestao;
        li.addEventListener('mousedown', function (evento) {
          evento.preventDefault();
          inputElement.value = sugestao;
          fecharSugestoes();
        });
        li.addEventListener('mouseenter', function () {
          indiceSelecionado = index;
          atualizarSelecao();
        });
        sugestoesList.appendChild(li);
      });

      sugestoesList.classList.remove('d-none');
      sugestoesList.classList.add('d-block');
    };

    var atualizarSelecao = function () {
      var itens = sugestoesList.querySelectorAll('li');
      itens.forEach(function (li, index) {
        li.classList.toggle('active', index === indiceSelecionado);
      });
    };

    var renderizarSugestoesLocais = function (termo) {
      if (!Array.isArray(options.localSuggestions)) {
        return false;
      }

      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = options.localSuggestions
        .filter(function (item) {
          return typeof item === 'string' && item.trim() !== '';
        })
        .filter(function (item) {
          if (!termoNormalizado) {
            return true;
          }
          return item.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 30);

      renderizarSugestoes(dadosFiltrados);
      return true;
    };

    var buscarSugestoes = function (termo) {
      if (termo.length < minChars) {
        fecharSugestoes();
        return;
      }

      if (renderizarSugestoesLocais(termo)) {
        return;
      }

      var url = new URL(window.location.href);
      url.searchParams.set('action', 'sugerir');
      url.searchParams.set('campo', 'fornecedor');
      url.searchParams.set('termo', termo);

      fetch(url.toString())
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Falha ao buscar sugestões de fornecedores.');
          }
          return response.json();
        })
        .then(function (dados) {
          renderizarSugestoes(Array.isArray(dados) ? dados : []);
        })
        .catch(function () {
          fecharSugestoes();
        });
    };

    inputElement.addEventListener('input', function () {
      buscarSugestoes(this.value.trim());
    });

    inputElement.addEventListener('focus', function () {
      if (!showOnFocus) {
        return;
      }
      renderizarSugestoesLocais(this.value.trim());
    });

    inputElement.addEventListener('keydown', function (e) {
      var listaItens = sugestoesList.querySelectorAll('li');
      if (!listaItens.length) {
        return;
      }

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceSelecionado = (indiceSelecionado + 1) % listaItens.length;
        atualizarSelecao();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceSelecionado = (indiceSelecionado - 1 + listaItens.length) % listaItens.length;
        atualizarSelecao();
      } else if (e.key === 'Enter') {
        if (indiceSelecionado >= 0 && indiceSelecionado < listaItens.length) {
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

  initAutocompleteFornecedor(inputFornecedor, fornecedorSugestoesList, {
    localSuggestions: fornecedoresNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

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
    window.jQuery('#descricao').summernote({
      placeholder: 'Place some text here',
      height: 180
    });
  }
});
</script>

<div class="modal fade" id="modal-info-formato-grade" tabindex="-1" role="dialog" aria-labelledby="modalInfoFormatoGradeLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalInfoFormatoGradeLabel">Formato da Grade - Referência</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead>
              <tr>
                <th>Nome da Opção (Label)</th>
                <th>Valor (Value/SKU)</th>
                <th>Uso Comum e Contexto</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Quadrado 1" (25.4mm)</td>
                <td>sq-25</td>
                <td>Padrão D&amp;D, Pathfinder e RPGs clássicos.</td>
              </tr>
              <tr>
                <td>Quadrado 1.5" (38mm)</td>
                <td>sq-38</td>
                <td>Miniaturas de tamanho Grande (L) ou Chefões.</td>
              </tr>
              <tr>
                <td>Hexágono 1" (25mm)</td>
                <td>hx-25</td>
                <td>Wargames táticos, Daggerheart e exploração.</td>
              </tr>
              <tr>
                <td>Hexágono 1.25" (32mm)</td>
                <td>hx-32</td>
                <td>BattleTech, Mechas e miniaturas de 32mm+.</td>
              </tr>
              <tr>
                <td>Hexágono Hexcrawl (12mm)</td>
                <td>hx-12</td>
                <td>Mapas de região, continentes e viagens longas.</td>
              </tr>
              <tr>
                <td>Escaramuça (30mm)</td>
                <td>hx-30</td>
                <td>Intermediário para bases de 28mm a 30mm.</td>
              </tr>
              <tr>
                <td>Grade de Pontos (Dots)</td>
                <td>dt-25</td>
                <td>Visual limpo, preferido por cartógrafos modernos.</td>
              </tr>
              <tr>
                <td>Sem Grade (Liso)</td>
                <td>none</td>
                <td>Uso livre com régua ou fita métrica (Wargames).</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>
