<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../../app/componentes/impressora_material_cards.php';

use App\Torres\TorreController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$usuario_uuid = trim((string) ($_SESSION['usuario_uuid'] ?? ''));
$erro = '';

if ($usuario_id <= 0 || $id <= 0) {
    header('Location: /404.php');
    exit;
}

$torreController = new TorreController($pdo);
$torre = $torreController->buscarParaEdicao($id, $usuario_id);

if (!$torre) {
    header('Location: /404.php');
    exit;
}

$torreVisualizacao = $torreController->buscarParaVisualizacao($id, $usuario_id) ?? [];
$contextoAdicao = $torreController->carregarContextoAdicao($usuario_id, $usuario_uuid, 0, 0, 0);
$estudios_disponiveis = is_array($contextoAdicao['estudios_disponiveis'] ?? null)
  ? $contextoAdicao['estudios_disponiveis']
  : [];
$colecoes_disponiveis = is_array($contextoAdicao['colecoes_disponiveis'] ?? null)
  ? $contextoAdicao['colecoes_disponiveis']
  : [];
$outras_caracteristicas_disponiveis = is_array($contextoAdicao['outras_caracteristicas_disponiveis'] ?? null)
  ? $contextoAdicao['outras_caracteristicas_disponiveis']
  : [];

$compatibilidade = (array) ($torre['__compatibilidade'] ?? []);
unset($torre['__compatibilidade']);

$tempoMinutos = (int) ($torreVisualizacao['tempo_impressao'] ?? 0);
$tempoDias = intdiv($tempoMinutos, 1440);
$tempoResto = $tempoMinutos % 1440;
$tempoHoras = intdiv($tempoResto, 60);
$tempoMin = $tempoResto % 60;

$markupAtualBruto = (float) ($torre['markup_consumidor_final'] ?? $torreVisualizacao['markup_impressao'] ?? 5);
$markupAtualNormalizado = (int) round($markupAtualBruto);
if ($markupAtualNormalizado < 1) {
  $markupAtualNormalizado = 1;
}
if ($markupAtualNormalizado > 10) {
  $markupAtualNormalizado = 10;
}

$imagensExistentesLista = [];
$imagensExistentesRaw = json_decode((string) ($torreVisualizacao['imagens'] ?? ''), true);
if (is_array($imagensExistentesRaw)) {
    foreach ($imagensExistentesRaw as $itemImagem) {
        if (is_string($itemImagem) && trim($itemImagem) !== '') {
            $imagensExistentesLista[] = trim($itemImagem);
            continue;
        }

        if (is_array($itemImagem) && !empty($itemImagem['grande']) && is_string($itemImagem['grande'])) {
            $imagensExistentesLista[] = trim($itemImagem['grande']);
        }
    }
}

$dadosFormulario = [
    'nome' => (string) ($torre['nome'] ?? ''),
    'nome_original' => (string) ($torre['nome_original'] ?? ''),
    'estudio' => (string) ($torreVisualizacao['estudio_nome'] ?? ''),
    'colecao' => (string) ($torreVisualizacao['colecao_nome'] ?? ''),
    'tematica' => (string) (($torreVisualizacao['tematica_nome'] ?? '') !== '' ? $torreVisualizacao['tematica_nome'] : ($torreVisualizacao['tematica'] ?? '')),
    'outras_caracteristicas' => (string) ($torreVisualizacao['outras_caracteristicas'] ?? ''),
    'descricao' => (string) ($torre['descricao'] ?? ''),
    'gramas' => (string) (($torreVisualizacao['peso_material'] ?? '') !== '' ? $torreVisualizacao['peso_material'] : ''),
    'tempo_dias' => (string) $tempoDias,
    'tempo_horas' => (string) $tempoHoras,
    'tempo_minutos' => (string) $tempoMin,
    'unidades_produzidas' => (string) (($torreVisualizacao['unidades_produzidas'] ?? '') !== '' ? $torreVisualizacao['unidades_produzidas'] : ''),
    'taxa_falha' => (string) (($torreVisualizacao['taxa_falha'] ?? '') !== '' ? $torreVisualizacao['taxa_falha'] : '10'),
    'markup' => (string) $markupAtualNormalizado,
    'preco_lojista' => (string) ($torre['preco_lojista'] ?? 0),
    'preco_consumidor_final' => (string) ($torre['preco_consumidor_final'] ?? 0),
    'observacoes' => (string) (($torre['observacoes'] ?? '') !== '' ? $torre['observacoes'] : ($torreVisualizacao['observacoes'] ?? '')),
];

$imagemCapa = trim((string) ($torreVisualizacao['capa'] ?? ''));

$tipoImpressoraBruto = strtoupper(trim((string) ($torreVisualizacao['impressora_tipo'] ?? '')));
$tipoImpressoraExibicao = $tipoImpressoraBruto === 'FDM'
  ? 'Filamento'
  : ($tipoImpressoraBruto === 'RESINA' ? 'Resina' : ((string) ($torreVisualizacao['impressora_tipo'] ?? '-')));

$materialTipoCard = '-';
$materialNomeCard = '-';
$materialMarcaCard = '-';
$materialCorCard = '-';
$materialSubtipoCard = '';

if (!empty($torreVisualizacao['filamento_nome'])) {
  $materialTipoCard = 'Filamento';
  $materialNomeCard = trim((string) ($torreVisualizacao['filamento_nome'] ?? '-'));
  $materialMarcaCard = trim((string) ($torreVisualizacao['filamento_marca'] ?? '-'));
  $materialCorCard = trim((string) ($torreVisualizacao['filamento_cor'] ?? '-'));
  $materialSubtipoCard = trim((string) ($torreVisualizacao['filamento_tipo'] ?? ''));
} elseif (!empty($torreVisualizacao['resina_nome'])) {
  $materialTipoCard = 'Resina';
  $materialNomeCard = trim((string) ($torreVisualizacao['resina_nome'] ?? '-'));
  $materialMarcaCard = trim((string) ($torreVisualizacao['resina_marca'] ?? '-'));
  $materialCorCard = trim((string) ($torreVisualizacao['resina_cor'] ?? '-'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $torreController->processarEdicao(
        $id,
        $usuario_id,
        (int) ($torre['produto_id'] ?? 0),
        $_POST,
        $compatibilidade,
        $_FILES,
        $usuario_uuid
    );

    if (!empty($resultado['sucesso'])) {
        echo '<script>window.location.href="?pagina=torres&acao=visualizar&id=' . $id . '";</script>';
        exit;
    }

    $erro = (string) ($resultado['erro'] ?? 'Erro ao editar torre.');

    $dadosFormulario['nome'] = (string) ($_POST['nome'] ?? $dadosFormulario['nome']);
    $dadosFormulario['nome_original'] = (string) ($_POST['nome_original'] ?? $dadosFormulario['nome_original']);
    $dadosFormulario['estudio'] = (string) ($_POST['estudio'] ?? $dadosFormulario['estudio']);
    $dadosFormulario['colecao'] = (string) ($_POST['colecao'] ?? $dadosFormulario['colecao']);
    $dadosFormulario['tematica'] = (string) ($_POST['tematica'] ?? $dadosFormulario['tematica']);
    $dadosFormulario['outras_caracteristicas'] = (string) ($_POST['outras_caracteristicas'] ?? $dadosFormulario['outras_caracteristicas']);
    $dadosFormulario['descricao'] = (string) ($_POST['descricao'] ?? $dadosFormulario['descricao']);
    $dadosFormulario['gramas'] = (string) ($_POST['gramas'] ?? $dadosFormulario['gramas']);
    $dadosFormulario['tempo_dias'] = (string) ($_POST['tempo_dias'] ?? $dadosFormulario['tempo_dias']);
    $dadosFormulario['tempo_horas'] = (string) ($_POST['tempo_horas'] ?? $dadosFormulario['tempo_horas']);
    $dadosFormulario['tempo_minutos'] = (string) ($_POST['tempo_minutos'] ?? $dadosFormulario['tempo_minutos']);
    $dadosFormulario['unidades_produzidas'] = (string) ($_POST['unidades_produzidas'] ?? $dadosFormulario['unidades_produzidas']);
    $dadosFormulario['taxa_falha'] = (string) ($_POST['taxa_falha'] ?? $dadosFormulario['taxa_falha']);
    $markupPost = (int) ($_POST['markup_consumidor_final'] ?? (int) $dadosFormulario['markup']);
    if ($markupPost < 1) {
      $markupPost = 1;
    }
    if ($markupPost > 10) {
      $markupPost = 10;
    }
    $dadosFormulario['markup'] = (string) $markupPost;
    $dadosFormulario['preco_lojista'] = (string) ($_POST['preco_lojista'] ?? $dadosFormulario['preco_lojista']);
    $dadosFormulario['preco_consumidor_final'] = (string) ($_POST['preco_consumidor_final'] ?? $dadosFormulario['preco_consumidor_final']);
    $dadosFormulario['observacoes'] = (string) ($_POST['observacoes'] ?? $dadosFormulario['observacoes']);

    $imagemCapa = trim((string) ($_POST['foto_existente'] ?? $imagemCapa));
    $imagensPostRaw = trim((string) ($_POST['imagens_existentes'] ?? ''));
    if ($imagensPostRaw !== '') {
        $imagensPost = json_decode($imagensPostRaw, true);
        if (is_array($imagensPost)) {
            $imagensExistentesLista = array_values(array_filter($imagensPost, static function ($img) {
                return is_string($img) && trim($img) !== '';
            }));
        }
    }
}
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Torre de Dados</h3>
  </div>

  <style>
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

    .outras_caracteristicas-input,
    .colecao-input {
      border: none;
      outline: none;
      flex: 1;
      min-width: 180px;
      padding: 0.2rem;
    }

    .outras_caracteristicas-tag,
    .colecao-tag {
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

    .outras_caracteristicas-tag small,
    .colecao-tag small {
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

  <form method="POST" enctype="multipart/form-data" id="form-editar-torre">
    <input type="hidden" id="foto_existente" name="foto_existente" value="<?= htmlspecialchars((string) $imagemCapa) ?>">
    <input type="hidden" id="imagens_existentes" name="imagens_existentes" value="<?= htmlspecialchars(json_encode($imagensExistentesLista, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">

    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <?php
        renderImpressoraMaterialCards([
          'impressora_nome' => trim((string) (($torreVisualizacao['impressora_marca'] ?? '') . ' ' . ($torreVisualizacao['impressora_modelo'] ?? ''))),
          'impressora_tipo' => $tipoImpressoraExibicao,
          'impressora_detalhe_label' => 'Custo Hora',
          'impressora_detalhe_valor' => 'R$ ' . number_format((float) ($torreVisualizacao['impressora_custo_hora'] ?? 0), 4, ',', '.'),
          'material_nome' => ($materialNomeCard !== '' ? $materialNomeCard : '-'),
          'material_tipo' => $materialTipoCard,
          'material_marca' => ($materialMarcaCard !== '' ? $materialMarcaCard : '-'),
          'material_cor' => ($materialCorCard !== '' ? $materialCorCard : '-'),
          'material_subtipo' => $materialSubtipoCard,
        ], 'mb-4');
      ?>

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
              <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($dadosFormulario['nome']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="nome_original">Nome Original</label>
              <input type="text" class="form-control" id="nome_original" name="nome_original" value="<?= htmlspecialchars($dadosFormulario['nome_original']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="estudio">Estúdio</label>
              <input type="text" class="form-control" id="estudio" name="estudio" value="<?= htmlspecialchars($dadosFormulario['estudio']) ?>" placeholder="Digite ou selecione um estúdio" autocomplete="off">
              <ul id="estudio-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="colecao-input">Coleção</label>
              <div class="colecao-container">
                <div id="colecao-tags" class="colecao-tags"></div>
                <input type="text" class="colecao-input" id="colecao-input" placeholder="Digite uma coleção..." autocomplete="off">
                <input type="hidden" id="colecao" name="colecao" value="<?= htmlspecialchars($dadosFormulario['colecao']) ?>">
              </div>
              <ul id="colecao-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="tematica">Temática</label>
              <select class="form-control" id="tematica" name="tematica">
                <option value="">-- Selecione --</option>
                <option value="Cyberpunk" <?= ($dadosFormulario['tematica'] === 'Cyberpunk') ? 'selected' : '' ?>>Cyberpunk</option>
                <option value="Fantasia" <?= ($dadosFormulario['tematica'] === 'Fantasia') ? 'selected' : '' ?>>Fantasia</option>
                <option value="Faroeste" <?= ($dadosFormulario['tematica'] === 'Faroeste') ? 'selected' : '' ?>>Faroeste</option>
                <option value="Horror Cósmico" <?= ($dadosFormulario['tematica'] === 'Horror Cósmico') ? 'selected' : '' ?>>Horror Cósmico</option>
                <option value="Paranormal" <?= ($dadosFormulario['tematica'] === 'Paranormal') ? 'selected' : '' ?>>Paranormal</option>
                <option value="Space Opera" <?= ($dadosFormulario['tematica'] === 'Space Opera') ? 'selected' : '' ?>>Space Opera</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="outras_caracteristicas-input">Outras Características</label>
              <div class="outras_caracteristicas-container">
                <div id="outras_caracteristicas-tags" class="outras_caracteristicas-tags"></div>
                <input type="text" class="outras_caracteristicas-input" id="outras_caracteristicas-input" placeholder="Digite uma característica..." autocomplete="off">
                <input type="hidden" id="outras_caracteristicas" name="outras_caracteristicas" value="<?= htmlspecialchars($dadosFormulario['outras_caracteristicas']) ?>">
              </div>
              <ul id="outras_caracteristicas-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>
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

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="descricao">Descrição</label>
          <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars($dadosFormulario['descricao']) ?></textarea>
        </div>
      </div>

      <hr>
      <h5>Dados Técnicos da Impressão</h5>

      <div class="form-row">
        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="gramas">Gramas (g)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="gramas" name="gramas" value="<?= htmlspecialchars((string) $dadosFormulario['gramas']) ?>">
            </div>
            <div class="form-group col-md-8">
              <label>Tempo de impressão (dias, horas, minutos)</label>
              <div class="form-row">
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_dias" name="tempo_dias" placeholder="Dias" value="<?= htmlspecialchars((string) $dadosFormulario['tempo_dias']) ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_horas" name="tempo_horas" placeholder="Horas" value="<?= htmlspecialchars((string) $dadosFormulario['tempo_horas']) ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_minutos" name="tempo_minutos" placeholder="Min" value="<?= htmlspecialchars((string) $dadosFormulario['tempo_minutos']) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="unidades_produzidas">Unidades Produzidas</label>
              <input type="number" min="0" class="form-control" id="unidades_produzidas" name="unidades_produzidas" value="<?= htmlspecialchars((string) $dadosFormulario['unidades_produzidas']) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="taxa_falha">Taxa de Falha (%)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="taxa_falha" name="taxa_falha" value="<?= htmlspecialchars((string) $dadosFormulario['taxa_falha']) ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="markup_consumidor_final">Markup</label>
              <select class="form-control" id="markup_consumidor_final" name="markup_consumidor_final">
                <option value="">-- Selecione --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <option value="<?= $i ?>" <?= (string) $dadosFormulario['markup'] === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="observacoes">Observações</label>
          <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($dadosFormulario['observacoes']) ?></textarea>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary" id="btn-salvar-edicao-torre">Salvar</button>
      <a href="?pagina=torres&acao=visualizar&id=<?= (int) $id ?>" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var estudiosDisponiveis = <?= json_encode($estudios_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var colecoesDisponiveis = <?= json_encode($colecoes_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var outrasCaracteristicasDisponiveis = <?= json_encode($outras_caracteristicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.summernote === 'function') {
    window.jQuery('#descricao').summernote({
      placeholder: 'Place some text here',
      height: 180
    });
  }

  var inputEstudio = document.getElementById('estudio');
  var estudioSugestoesList = document.getElementById('estudio-sugestoes');
  var outrasCaracteristicasInput = document.getElementById('outras_caracteristicas-input');
  var outrasCaracteristicasTagsContainer = document.getElementById('outras_caracteristicas-tags');
  var outrasCaracteristicasHiddenInput = document.getElementById('outras_caracteristicas');
  var outrasCaracteristicasSugestoesList = document.getElementById('outras_caracteristicas-sugestoes');
  var inputColecao = document.getElementById('colecao-input');
  var colecaoTagsContainer = document.getElementById('colecao-tags');
  var colecaoHiddenInput = document.getElementById('colecao');
  var colecaoSugestoesList = document.getElementById('colecao-sugestoes');

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

  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var fotoExistenteInput = document.getElementById('foto_existente');

  var inputFotos = document.getElementById('fotos');
  var labelFotos = document.querySelector('label.custom-file-label[for="fotos"]');
  var previewImagensContainer = document.getElementById('preview-imagens-container');
  var imagensExistentesInput = document.getElementById('imagens_existentes');

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

      if (!arquivo || !arquivo.type || arquivo.type.indexOf('image/') !== 0) {
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
      if (imagensExistentesInput) {
        imagensExistentesInput.value = JSON.stringify(imagensPersistidas);
      }
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

  var formEditarTorre = document.getElementById('form-editar-torre');
  var btnSalvarEdicaoTorre = document.getElementById('btn-salvar-edicao-torre');

  if (formEditarTorre && btnSalvarEdicaoTorre) {
    formEditarTorre.addEventListener('submit', function () {
      btnSalvarEdicaoTorre.disabled = true;
      btnSalvarEdicaoTorre.textContent = 'Salvando...';
    });
  }
});
</script>