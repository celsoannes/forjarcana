<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Miniaturas\MiniaturaController;

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$erro = '';
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$usuario_uuid = trim($_SESSION['usuario_uuid'] ?? '');

$miniaturaController = new MiniaturaController($pdo);

if (empty($_SESSION['miniatura_form_token'])) {
  $_SESSION['miniatura_form_token'] = bin2hex(random_bytes(16));
}
$miniatura_form_token = (string) $_SESSION['miniatura_form_token'];

if (($_GET['action'] ?? '') === 'sugerir') {
  header('Content-Type: application/json; charset=UTF-8');

  $campo = trim((string) ($_GET['campo'] ?? ''));
  $termo = trim((string) ($_GET['termo'] ?? ''));
  $estudioNome = trim((string) ($_GET['estudio'] ?? ''));
  $sugestoes = $miniaturaController->sugerirCampo($usuario_id, $campo, $termo, $estudioNome);
  echo json_encode($sugestoes, JSON_UNESCAPED_UNICODE);

  exit;
}

$impressora_id = (int) ($_GET['impressora_id'] ?? 0);
$filamento_id = (int) ($_GET['filamento_id'] ?? 0);
$resina_id = (int) ($_GET['resina_id'] ?? 0);

$selecao_confirmacao = null;
$aviso_selecao = '';
$avisos_upload = [];
$estudios_disponiveis = [];
$colecoes_disponiveis = [];
$tematicas_disponiveis = [];
$racas_disponiveis = [];
$classes_disponiveis = [];
$armaduras_disponiveis = [];
$armas_disponiveis = [];
$outras_caracteristicas_disponiveis = [];
$contextoAdicao = $miniaturaController->carregarContextoAdicao($usuario_id, $usuario_uuid, $impressora_id, $filamento_id, $resina_id);
$usuario_uuid = (string) ($contextoAdicao['usuario_uuid'] ?? $usuario_uuid);
$selecao_confirmacao = $contextoAdicao['selecao_confirmacao'] ?? null;
$aviso_selecao = (string) ($contextoAdicao['aviso_selecao'] ?? '');
$estudios_disponiveis = is_array($contextoAdicao['estudios_disponiveis'] ?? null) ? $contextoAdicao['estudios_disponiveis'] : [];
$colecoes_disponiveis = is_array($contextoAdicao['colecoes_disponiveis'] ?? null) ? $contextoAdicao['colecoes_disponiveis'] : [];
$tematicas_disponiveis = is_array($contextoAdicao['tematicas_disponiveis'] ?? null) ? $contextoAdicao['tematicas_disponiveis'] : [];
$racas_disponiveis = is_array($contextoAdicao['racas_disponiveis'] ?? null) ? $contextoAdicao['racas_disponiveis'] : [];
$classes_disponiveis = is_array($contextoAdicao['classes_disponiveis'] ?? null) ? $contextoAdicao['classes_disponiveis'] : [];
$armaduras_disponiveis = is_array($contextoAdicao['armaduras_disponiveis'] ?? null) ? $contextoAdicao['armaduras_disponiveis'] : [];
$armas_disponiveis = is_array($contextoAdicao['armas_disponiveis'] ?? null) ? $contextoAdicao['armas_disponiveis'] : [];
$outras_caracteristicas_disponiveis = is_array($contextoAdicao['outras_caracteristicas_disponiveis'] ?? null) ? $contextoAdicao['outras_caracteristicas_disponiveis'] : [];
$dadosFormulario = $miniaturaController->montarEstadoFormularioAdicao($_POST ?? []);

$foto = null;
$imagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form_token = (string) ($dadosFormulario['form_token'] ?? '');

    if (!isset($_SESSION['miniatura_form_token']) || $form_token === '' || !hash_equals((string) $_SESSION['miniatura_form_token'], $form_token)) {
      $erro = 'Este formulário já foi enviado. Atualize a página e tente novamente.';
    } else {
      $_SESSION['miniatura_form_token'] = bin2hex(random_bytes(16));
      $miniatura_form_token = (string) $_SESSION['miniatura_form_token'];
    }

    $dadosPost = $miniaturaController->parseDadosAdicao($_POST);
    $nome = (string) ($dadosPost['nome'] ?? '');
    $nome_original = (string) ($dadosPost['nome_original'] ?? '');
    $estudio = (string) ($dadosPost['estudio'] ?? '');
    $colecao = (string) ($dadosPost['colecao'] ?? '');
    $tematica = (string) ($dadosPost['tematica'] ?? '');
    $raca = (string) ($dadosPost['raca'] ?? '');
    $classe = (string) ($dadosPost['classe'] ?? '');
    $genero = (string) ($dadosPost['genero'] ?? '');
    $criatura = (string) ($dadosPost['criatura'] ?? '');
    $papel = (string) ($dadosPost['papel'] ?? '');
    $tamanho = (string) ($dadosPost['tamanho'] ?? '');
    $base = (string) ($dadosPost['base'] ?? '');
    $pintada = $dadosPost['pintada'] ?? '';
    $arma_principal = (string) ($dadosPost['arma_principal'] ?? '');
    $arma_secundaria = (string) ($dadosPost['arma_secundaria'] ?? '');
    $armadura = (string) ($dadosPost['armadura'] ?? '');
    $outras_caracteristicas = (string) ($dadosPost['outras_caracteristicas'] ?? '');
    $gramas = (float) ($dadosPost['gramas'] ?? 0);
    $tempo_dias = (int) ($dadosPost['tempo_dias'] ?? 0);
    $tempo_horas = (int) ($dadosPost['tempo_horas'] ?? 0);
    $tempo_minutos = (int) ($dadosPost['tempo_minutos'] ?? 0);
    $tempo_total_min = (int) ($dadosPost['tempo_total_min'] ?? 0);
    $unidades_produzidas = (int) ($dadosPost['unidades_produzidas'] ?? 0);
    $taxa_falha = (float) ($dadosPost['taxa_falha'] ?? 0);
    $markup_lojista = (string) ($dadosPost['markup_lojista'] ?? '2');
    $markup_consumidor_final = (string) ($dadosPost['markup_consumidor_final'] ?? '5');
    $observacoes = (string) ($dadosPost['observacoes'] ?? '');
    $descricao_produto = (string) ($dadosPost['descricao_produto'] ?? '');

    if (!$erro) {
      $resultadoFluxo = $miniaturaController->processarFluxoAdicao(
        $usuario_id,
        $usuario_uuid,
        $_POST,
        $_FILES,
        $selecao_confirmacao
      );

      $foto = $resultadoFluxo['foto'] ?? $foto;
      $imagens = is_array($resultadoFluxo['imagens'] ?? null) ? $resultadoFluxo['imagens'] : $imagens;
      $avisos_upload = is_array($resultadoFluxo['avisos_upload'] ?? null) ? $resultadoFluxo['avisos_upload'] : $avisos_upload;

      if (($resultadoFluxo['sucesso'] ?? false) === true) {
        echo '<script>window.location.href="?pagina=produtos";</script>';
        exit;
      }

      $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar miniatura/produto.');
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Miniatura</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="form-adicionar-miniatura">
    <input type="hidden" name="form_token" value="<?= htmlspecialchars($miniatura_form_token) ?>">
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

      <?php if ($selecao_confirmacao): ?>
        <h5 class="mb-3">Seleção confirmada</h5>
        <div class="selecao-grid mb-4">
          <div class="selecao-card">
            <div class="selecao-icon"><i class="fas fa-microscope"></i></div>
            <div class="selecao-content">
              <h2><?= htmlspecialchars($selecao_confirmacao['impressora']['marca'] . ' ' . $selecao_confirmacao['impressora']['modelo']) ?></h2>
              <p>
                <strong>Tipo:</strong> <?= htmlspecialchars($selecao_confirmacao['impressora']['tipo']) ?><br>
                <strong>Etapa:</strong> Impressora selecionada
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

        .outras_caracteristicas-container {
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

        .colecao-input {
          border: none;
          outline: none;
          flex: 1;
          min-width: 180px;
          padding: 0.2rem;
        }

        .classe-container {
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

        .classe-input {
          border: none;
          outline: none;
          flex: 1;
          min-width: 180px;
          padding: 0.2rem;
        }

        .classe-tag {
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

        .classe-tag small {
          cursor: pointer;
          font-size: 1rem;
          line-height: 1;
          font-weight: 700;
        }

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

        .colecao-tag small {
          cursor: pointer;
          font-size: 1rem;
          line-height: 1;
          font-weight: 700;
        }

        .outras_caracteristicas-input {
          border: none;
          outline: none;
          flex: 1;
          min-width: 180px;
          padding: 0.2rem;
        }

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
              <label for="nome">Nome</label>
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
            <div class="form-group col-md-4 position-relative">
              <label for="estudio">Estúdio *</label>
              <input type="text" class="form-control" id="estudio" name="estudio" required value="<?= htmlspecialchars((string) ($dadosFormulario['estudio'] ?? '')) ?>" placeholder="Digite ou selecione um estúdio" autocomplete="off">
              <ul id="estudio-sugestoes" class="autocomplete-list"></ul>
            </div>
            <div class="form-group col-md-4 position-relative">
              <label for="colecao-input">Coleção *</label>
              <div class="colecao-container">
                <div id="colecao-tags" class="colecao-tags"></div>
                <input type="text" class="colecao-input" id="colecao-input" placeholder="Digite uma coleção..." autocomplete="off">
                <input type="hidden" id="colecao" name="colecao" value="<?= htmlspecialchars((string) ($dadosFormulario['colecao'] ?? '')) ?>">
              </div>
              <ul id="colecao-sugestoes" class="autocomplete-list"></ul>
            </div>
            <div class="form-group col-md-4">
              <label for="tematica">Temática *
                <i id="tematica-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="tematica" name="tematica" required>
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
            <div class="form-group col-md-4"><label for="raca">Raça</label><input type="text" class="form-control" id="raca" name="raca" list="lista-racas" value="<?= htmlspecialchars((string) ($dadosFormulario['raca'] ?? '')) ?>"></div>
            <div class="form-group col-md-4 position-relative">
              <label for="classe-input">Classe</label>
              <div class="classe-container">
                <div id="classe-tags" class="classe-tags"></div>
                <input type="text" class="classe-input" id="classe-input" placeholder="Digite uma classe..." autocomplete="off">
                <input type="hidden" id="classe" name="classe" value="<?= htmlspecialchars((string) ($dadosFormulario['classe'] ?? '')) ?>">
              </div>
              <ul id="classe-sugestoes" class="autocomplete-list"></ul>
            </div>
            <div class="form-group col-md-4">
              <label for="genero">Gênero
                <i id="genero-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="genero" name="genero">
                <option value="">-- Selecione --</option>
                <option value="Masculino" <?= (($dadosFormulario['genero'] ?? '') === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                <option value="Feminino" <?= (($dadosFormulario['genero'] ?? '') === 'Feminino') ? 'selected' : '' ?>>Feminino</option>
                <option value="Neutro / Unissex" <?= in_array(($dadosFormulario['genero'] ?? ''), ['Neutro / Unissex', 'Neutro'], true) ? 'selected' : '' ?>>Neutro / Unissex</option>
                <option value="Não Especificado" <?= (($dadosFormulario['genero'] ?? '') === 'Não Especificado') ? 'selected' : '' ?>>Não Especificado</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="criatura">Criatura
                <i id="criatura-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="criatura" name="criatura">
                <option value="">-- Selecione --</option>
                <option value="Aberração" <?= (($dadosFormulario['criatura'] ?? '') === 'Aberração') ? 'selected' : '' ?>>Aberração</option>
                <option value="Alienígena" <?= (($dadosFormulario['criatura'] ?? '') === 'Alienígena') ? 'selected' : '' ?>>Alienígena</option>
                <option value="Celestial" <?= (($dadosFormulario['criatura'] ?? '') === 'Celestial') ? 'selected' : '' ?>>Celestial</option>
                <option value="Ciborgue" <?= (($dadosFormulario['criatura'] ?? '') === 'Ciborgue') ? 'selected' : '' ?>>Ciborgue</option>
                <option value="Constructo" <?= (($dadosFormulario['criatura'] ?? '') === 'Constructo') ? 'selected' : '' ?>>Constructo</option>
                <option value="Ínfero / Demônio" <?= (($dadosFormulario['criatura'] ?? '') === 'Ínfero / Demônio') ? 'selected' : '' ?>>Ínfero / Demônio</option>
                <option value="Dragão" <?= (($dadosFormulario['criatura'] ?? '') === 'Dragão') ? 'selected' : '' ?>>Dragão</option>
                <option value="Elemental" <?= (($dadosFormulario['criatura'] ?? '') === 'Elemental') ? 'selected' : '' ?>>Elemental</option>
                <option value="Fada" <?= (($dadosFormulario['criatura'] ?? '') === 'Fada') ? 'selected' : '' ?>>Fada</option>
                <option value="Fera" <?= (($dadosFormulario['criatura'] ?? '') === 'Fera') ? 'selected' : '' ?>>Fera</option>
                <option value="Gigante" <?= (($dadosFormulario['criatura'] ?? '') === 'Gigante') ? 'selected' : '' ?>>Gigante</option>
                <option value="Humanoide" <?= (($dadosFormulario['criatura'] ?? '') === 'Humanoide') ? 'selected' : '' ?>>Humanoide</option>
                <option value="Monstruosidade" <?= (($dadosFormulario['criatura'] ?? '') === 'Monstruosidade') ? 'selected' : '' ?>>Monstruosidade</option>
                <option value="Morto-vivo" <?= (($dadosFormulario['criatura'] ?? '') === 'Morto-vivo') ? 'selected' : '' ?>>Morto-vivo</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="papel">Papel
                <i id="papel-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="papel" name="papel">
                <option value="">-- Selecione --</option>
                <option value="Acessório / Objeto" <?= (($dadosFormulario['papel'] ?? '') === 'Acessório / Objeto') ? 'selected' : '' ?>>Acessório / Objeto</option>
                <option value="Baú" <?= (($dadosFormulario['papel'] ?? '') === 'Baú') ? 'selected' : '' ?>>Baú</option>
                <option value="Cenário Modular" <?= (($dadosFormulario['papel'] ?? '') === 'Cenário Modular') ? 'selected' : '' ?>>Cenário Modular</option>
                <option value="Cosplay" <?= (($dadosFormulario['papel'] ?? '') === 'Cosplay') ? 'selected' : '' ?>>Cosplay</option>
                <option value="Diorama" <?= (($dadosFormulario['papel'] ?? '') === 'Diorama') ? 'selected' : '' ?>>Diorama</option>
                <option value="Elementos de Cenário" <?= (($dadosFormulario['papel'] ?? '') === 'Elementos de Cenário') ? 'selected' : '' ?>>Elementos de Cenário</option>
                <option value="Herói" <?= (($dadosFormulario['papel'] ?? '') === 'Herói') ? 'selected' : '' ?>>Herói</option>
                <option value="Inimigo" <?= (($dadosFormulario['papel'] ?? '') === 'Inimigo') ? 'selected' : '' ?>>Inimigo</option>
                <option value="Monstro" <?= (($dadosFormulario['papel'] ?? '') === 'Monstro') ? 'selected' : '' ?>>Monstro</option>
                <option value="NPC / PNJ" <?= (($dadosFormulario['papel'] ?? '') === 'NPC / PNJ') ? 'selected' : '' ?>>NPC / PNJ</option>
                <option value="Personagem Jogável" <?= (($dadosFormulario['papel'] ?? '') === 'Personagem Jogável') ? 'selected' : '' ?>>Personagem Jogável</option>
                <option value="Pet / Familiar" <?= (($dadosFormulario['papel'] ?? '') === 'Pet / Familiar') ? 'selected' : '' ?>>Pet / Familiar</option>
                <option value="Veículo" <?= (($dadosFormulario['papel'] ?? '') === 'Veículo') ? 'selected' : '' ?>>Veículo</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="tamanho">Tamanho</label>
              <select class="form-control" id="tamanho" name="tamanho">
                <option value="">-- Selecione --</option>
                <option value="Pequeno" <?= (($dadosFormulario['tamanho'] ?? '') === 'Pequeno') ? 'selected' : '' ?>>Pequeno</option>
                <option value="Médio" <?= (($dadosFormulario['tamanho'] ?? '') === 'Médio') ? 'selected' : '' ?>>Médio</option>
                <option value="Grande" <?= (($dadosFormulario['tamanho'] ?? '') === 'Grande') ? 'selected' : '' ?>>Grande</option>
                <option value="Gigantesco" <?= (($dadosFormulario['tamanho'] ?? '') === 'Gigantesco') ? 'selected' : '' ?>>Gigantesco</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4 position-relative">
              <label for="arma_principal">Arma Principal</label>
              <input type="text" class="form-control" id="arma_principal" name="arma_principal" placeholder="Ex: Espada Longa" value="<?= htmlspecialchars((string) ($dadosFormulario['arma_principal'] ?? '')) ?>" autocomplete="off">
              <ul id="arma-principal-sugestoes" class="autocomplete-list"></ul>
            </div>
            <div class="form-group col-md-4 position-relative">
              <label for="arma_secundaria">Arma Secundária</label>
              <input type="text" class="form-control" id="arma_secundaria" name="arma_secundaria" placeholder="Ex: Escudo" value="<?= htmlspecialchars((string) ($dadosFormulario['arma_secundaria'] ?? '')) ?>" autocomplete="off">
              <ul id="arma-secundaria-sugestoes" class="autocomplete-list"></ul>
            </div>
            <div class="form-group col-md-4 position-relative">
              <label for="armadura">Armadura</label>
              <input type="text" class="form-control" id="armadura" name="armadura" placeholder="Ex: Couro" value="<?= htmlspecialchars((string) ($dadosFormulario['armadura'] ?? '')) ?>" autocomplete="off">
              <ul id="armadura-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>
        </div>
      </div>

      <datalist id="lista-racas">
        <?php foreach ($racas_disponiveis as $itemRaca): ?>
          <option value="<?= htmlspecialchars($itemRaca['nome']) ?>"></option>
        <?php endforeach; ?>
      </datalist>

      <div class="form-group position-relative">
        <label for="outras_caracteristicas-input">Outras Características</label>
        <div class="outras_caracteristicas-container">
          <div id="outras_caracteristicas-tags" class="outras_caracteristicas-tags"></div>
          <input type="text" class="outras_caracteristicas-input" id="outras_caracteristicas-input" placeholder="Digite uma característica..." autocomplete="off">
          <input type="hidden" id="outras_caracteristicas" name="outras_caracteristicas" value="<?= htmlspecialchars((string) ($dadosFormulario['outras_caracteristicas'] ?? '')) ?>">
        </div>
        <ul id="outras_caracteristicas-sugestoes" class="autocomplete-list"></ul>
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

      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="base">Base</label>
          <select class="form-control" id="base" name="base">
            <option value="">-- Selecione --</option>
            <option value="25mm" <?= (($dadosFormulario['base'] ?? '') === '25mm') ? 'selected' : '' ?>>25mm</option>
            <option value="32mm" <?= (($dadosFormulario['base'] ?? '') === '32mm') ? 'selected' : '' ?>>32mm</option>
            <option value="51mm" <?= (($dadosFormulario['base'] ?? '') === '51mm') ? 'selected' : '' ?>>51mm</option>
            <option value="75mm" <?= (($dadosFormulario['base'] ?? '') === '75mm') ? 'selected' : '' ?>>75mm</option>
            <option value="100mm" <?= (($dadosFormulario['base'] ?? '') === '100mm') ? 'selected' : '' ?>>100mm</option>
            <option value="Outra" <?= (($dadosFormulario['base'] ?? '') === 'Outra') ? 'selected' : '' ?>>Outra</option>
          </select>
        </div>
        <div class="form-group col-md-6">
          <label for="pintada">Pintada</label>
          <select class="form-control" id="pintada" name="pintada">
            <option value="" <?= (($dadosFormulario['pintada'] ?? '') === '') ? 'selected' : '' ?>>-- Selecione --</option>
            <option value="0" <?= (string)($dadosFormulario['pintada'] ?? '') === '0' ? 'selected' : '' ?>>Não</option>
            <option value="1" <?= (string)($dadosFormulario['pintada'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option>
          </select>
        </div>
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

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="observacoes">Observações</label>
          <input type="text" class="form-control" id="observacoes" name="observacoes" value="<?= htmlspecialchars((string) ($dadosFormulario['observacoes'] ?? '')) ?>">
        </div>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary" id="btn-salvar-miniatura">Salvar</button>
      <a href="?pagina=miniaturas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<div class="modal fade" id="criaturaInfoModal" tabindex="-1" role="dialog" aria-labelledby="criaturaInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="criaturaInfoModalLabel">Tipos de Criatura</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="criatura-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="papelInfoModal" tabindex="-1" role="dialog" aria-labelledby="papelInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="papelInfoModalLabel">Tipos de Papel</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="papel-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="generoInfoModal" tabindex="-1" role="dialog" aria-labelledby="generoInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="generoInfoModalLabel">Tipos de Gênero</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="genero-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
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
  var armadurasDisponiveis = <?= json_encode($armaduras_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var armasDisponiveis = <?= json_encode($armas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var classesDisponiveis = <?= json_encode($classes_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var tematicasDisponiveis = <?= json_encode($tematicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var outrasCaracteristicasDisponiveis = <?= json_encode($outras_caracteristicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var inputEstudio = document.getElementById('estudio');
  var estudioSugestoesList = document.getElementById('estudio-sugestoes');
  var inputColecao = document.getElementById('colecao-input');
  var selectTematica = document.getElementById('tematica');
  var inputClasse = document.getElementById('classe-input');
  var inputArmaPrincipal = document.getElementById('arma_principal');
  var inputArmaSecundaria = document.getElementById('arma_secundaria');
  var inputArmadura = document.getElementById('armadura');
  var colecaoTagsContainer = document.getElementById('colecao-tags');
  var colecaoHiddenInput = document.getElementById('colecao');
  var colecaoSugestoesList = document.getElementById('colecao-sugestoes');
  var classeTagsContainer = document.getElementById('classe-tags');
  var classeHiddenInput = document.getElementById('classe');
  var classeSugestoesList = document.getElementById('classe-sugestoes');
  var armaPrincipalSugestoesList = document.getElementById('arma-principal-sugestoes');
  var armaSecundariaSugestoesList = document.getElementById('arma-secundaria-sugestoes');
  var armaduraSugestoesList = document.getElementById('armadura-sugestoes');
  var selectGenero = document.getElementById('genero');
  var generoModalBody = document.getElementById('genero-modal-body');
  var tooltipTematica = document.getElementById('tematica-tooltip-trigger');
  var tematicaModalBody = document.getElementById('tematica-modal-body');
  var selectCriatura = document.getElementById('criatura');
  var tooltipCriatura = document.getElementById('criatura-tooltip-trigger');
  var criaturaModalBody = document.getElementById('criatura-modal-body');
  var selectPapel = document.getElementById('papel');
  var tooltipPapel = document.getElementById('papel-tooltip-trigger');
  var tooltipGenero = document.getElementById('genero-tooltip-trigger');
  var papelModalBody = document.getElementById('papel-modal-body');
  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var inputFotos = document.getElementById('fotos');
  var formAdicionarMiniatura = document.getElementById('form-adicionar-miniatura');
  var btnSalvarMiniatura = document.getElementById('btn-salvar-miniatura');
  var labelFotos = document.querySelector('label.custom-file-label[for="fotos"]');
  var previewImagensContainer = document.getElementById('preview-imagens-container');
  var outrasCaracteristicasInput = document.getElementById('outras_caracteristicas-input');
  var outrasCaracteristicasTagsContainer = document.getElementById('outras_caracteristicas-tags');
  var outrasCaracteristicasHiddenInput = document.getElementById('outras_caracteristicas');
  var outrasCaracteristicasSugestoesList = document.getElementById('outras_caracteristicas-sugestoes');
  var fotoExistenteInput = document.getElementById('foto_existente');
  var imagensExistentesInput = document.getElementById('imagens_existentes');

  var colecoesNomesDisponiveis = Array.isArray(colecoesDisponiveis)
    ? Array.from(new Set(colecoesDisponiveis
      .map(function (itemColecao) {
        return itemColecao && typeof itemColecao.nome === 'string' ? itemColecao.nome.trim() : '';
      })
      .filter(function (nomeColecao) {
        return nomeColecao !== '';
      })))
    : [];

  var classesNomesDisponiveis = [];
  if (Array.isArray(classesDisponiveis)) {
    var classesControle = {};
    classesDisponiveis.forEach(function (itemClasse) {
      var valorClasse = itemClasse && typeof itemClasse.nome === 'string' ? itemClasse.nome : '';
      if (!valorClasse) {
        return;
      }

      valorClasse.split(',').forEach(function (parteClasse) {
        var classeNormalizada = parteClasse.trim();
        if (!classeNormalizada) {
          return;
        }

        var chaveClasse = classeNormalizada.toLocaleLowerCase();
        if (classesControle[chaveClasse]) {
          return;
        }

        classesControle[chaveClasse] = true;
        classesNomesDisponiveis.push(classeNormalizada);
      });
    });
  }

  var estudiosNomesDisponiveis = Array.isArray(estudiosDisponiveis)
    ? Array.from(new Set(estudiosDisponiveis
      .map(function (itemEstudio) {
        return itemEstudio && typeof itemEstudio.nome === 'string' ? itemEstudio.nome.trim() : '';
      })
      .filter(function (nomeEstudio) {
        return nomeEstudio !== '';
      })))
    : [];

  var armasNomesDisponiveis = Array.isArray(armasDisponiveis)
    ? Array.from(new Set(armasDisponiveis
      .map(function (itemArma) {
        return itemArma && typeof itemArma.nome === 'string' ? itemArma.nome.trim() : '';
      })
      .filter(function (nomeArma) {
        return nomeArma !== '';
      })))
    : [];

  var armadurasNomesDisponiveis = Array.isArray(armadurasDisponiveis)
    ? Array.from(new Set(armadurasDisponiveis
      .map(function (itemArmadura) {
        return itemArmadura && typeof itemArmadura.nome === 'string' ? itemArmadura.nome.trim() : '';
      })
      .filter(function (nomeArmadura) {
        return nomeArmadura !== '';
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

      if (renderizarSugestoesLocais(termo)) {
        return;
      }

      var url = new URL(window.location.href);
      url.searchParams.set('action', 'sugerir');
      url.searchParams.set('campo', fieldName);
      url.searchParams.set('termo', termo);

      if (typeof options.getExtraParams === 'function') {
        var extras = options.getExtraParams();
        if (extras && typeof extras === 'object') {
          Object.keys(extras).forEach(function (chave) {
            var valor = extras[chave];
            if (typeof valor === 'string' && valor.trim() !== '') {
              url.searchParams.set(chave, valor.trim());
            }
          });
        }
      }

      fetch(url.toString())
        .then(function (response) { return response.json(); })
        .then(function (data) {
          sugestoesList.innerHTML = '';
          indiceSelecionado = -1;

          if (!Array.isArray(data) || data.length === 0) {
            fecharSugestoes();
            return;
          }

          data.forEach(function (sugestao, index) {
            var li = document.createElement('li');
            li.textContent = sugestao;
            li.dataset.index = String(index);
            li.addEventListener('click', function () {
              adicionarItem(sugestao);
            });
            sugestoesList.appendChild(li);
          });

          sugestoesList.classList.add('active');
        })
        .catch(function () {
          fecharSugestoes();
        });
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
    inputClasse,
    classeTagsContainer,
    classeHiddenInput,
    classeSugestoesList,
    'classe',
    {
      localSuggestions: classesNomesDisponiveis,
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

  initAutocompleteField(inputEstudio, estudioSugestoesList, {
    localSuggestions: estudiosNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  initAutocompleteField(inputArmaPrincipal, armaPrincipalSugestoesList, {
    localSuggestions: armasNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  initAutocompleteField(inputArmaSecundaria, armaSecundariaSugestoesList, {
    localSuggestions: armasNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  initAutocompleteField(inputArmadura, armaduraSugestoesList, {
    localSuggestions: armadurasNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  if (selectGenero && tooltipGenero) {
    var descricoesGenero = {
      'Masculino': 'Padrão biológico e de identidade.',
      'Feminino': 'Padrão biológico e de identidade.',
      'Neutro / Unissex': 'Ótimo para miniaturas de monstros ou itens que não possuem características sexuais aparentes.',
      'Não Especificado': 'A opção mais segura para quando o gênero não é relevante para o modelo ou ficha.'
    };

    var opcoesGenero = Array.from(selectGenero.options)
      .filter(function (opcao) { return opcao.value !== ''; })
      .map(function (opcao) { return opcao.text; });

    var listaGeneroHtml = '<ul class="mb-0 pl-3">' + opcoesGenero
      .map(function (item) {
        var descricao = descricoesGenero[item] || '';
        return '<li><strong>' + item + ':</strong> <span>' + descricao + '</span></li>';
      })
      .join('') + '</ul>';

    if (generoModalBody) {
      generoModalBody.innerHTML = listaGeneroHtml;
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(tooltipGenero).off('click.generoModal').on('click.generoModal', function () {
        window.jQuery('#generoInfoModal').modal('show');
      });
    }
  }

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

  if (selectCriatura && tooltipCriatura) {
    var descricoesCriatura = {
      'Humanoide': 'Seres de forma humana, geralmente com cultura, linguagem e inteligência sociável.',
      'Constructo': 'Seres artificiais, máquinas ou estátuas trazidas à vida por magia ou tecnologia.',
      'Morto-vivo': 'Cadáveres reanimados ou espíritos que permanecem no mundo após a morte.',
      'Monstruosidade': 'Criaturas não naturais, mutantes ou resultados de experimentos mágicos instáveis.',
      'Fera': 'Animais naturais que fazem parte do ecossistema comum (lobos, cavalos, ursos).',
      'Alienígena': 'Seres originários de outros planetas ou galáxias, com biologia desconhecida.',
      'Aberração': 'Entidades bizarras de planos distantes que desafiam as leis da natureza e da lógica.',
      'Ínfero / Demônio': 'Seres malignos nativos de planos inferiores (infernos ou abismos).',
      'Fada': 'Criaturas mágicas ligadas à natureza e ao plano feérico; travessas e encantadas.',
      'Dragão': 'Répteis alados e antigos, geralmente poderosos, inteligentes e coletores de tesouros.',
      'Gigante': 'Seres colossais de força descomunal, geralmente organizados em sociedades tribais.',
      'Celestial': 'Entidades divinas e puras, geralmente servas de deuses ou guardiãs da ordem.',
      'Ciborgue': 'Seres orgânicos aprimorados com partes mecânicas ou eletrônicas integradas.',
      'Elemental': 'Criaturas formadas pela essência pura de um elemento (Fogo, Água, Ar ou Terra).'
    };

    var opcoesCriatura = Array.from(selectCriatura.options)
      .filter(function (opcao) { return opcao.value !== ''; })
      .map(function (opcao) { return opcao.text; });

    var listaHtml = '<ul class="mb-0 pl-3">' + opcoesCriatura
      .map(function (item) {
        var descricao = descricoesCriatura[item] || '';
        return '<li><strong>' + item + ':</strong> <span>' + descricao + '</span></li>';
      })
      .join('') + '</ul>';

    if (criaturaModalBody) {
      criaturaModalBody.innerHTML = listaHtml;
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(tooltipCriatura).off('click.criaturaModal').on('click.criaturaModal', function () {
        window.jQuery('#criaturaInfoModal').modal('show');
      });
    }
  }

  if (selectPapel && tooltipPapel) {
    var descricoesPapel = {
      'Personagem Jogável': 'Protagonista principal controlado por um jogador durante a partida.',
      'Monstro': 'Criatura hostil, fera ou entidade perigosa que serve como desafio em combate.',
      'Inimigo': 'Qualquer oponente ou adversário que se opõe diretamente aos jogadores.',
      'NPC / PNJ': 'Personagem Não Jogável que povoa o mundo e interage com os jogadores para guiar a história.',
      'Acessório / Objeto': 'Itens de cena pequenos, como ferramentas, móveis ou utensílios, que compõem o ambiente.',
      'Veículo': 'Meios de transporte, como carroças, barcos, montarias mecânicas ou naves.',
      'Pet / Familiar': 'Animal de estimação ou criatura mágica vinculada a um personagem para auxílio ou companhia.',
      'Baú': 'Recipiente para armazenamento de tesouros, saques ou itens importantes.',
      'Cosplay': 'Peças de vestuário, acessórios em tamanho real ou réplicas para uso humano.',
      'Herói': 'Personagem lendário ou de destaque, muitas vezes utilizado como miniatura principal ou colecionável.',
      'Cenário Modular': 'Peças de terreno (paredes, pisos) que se encaixam para criar mapas customizáveis.',
      'Elementos de Cenário': 'Objetos de decoração (Scatter Terrain) espalhados pelo mapa para dar realismo (pedras, barris).',
      'Diorama': 'Uma cena estática completa e detalhada, geralmente montada para exibição artística ou exposição.'
    };

    var opcoesPapel = Array.from(selectPapel.options)
      .filter(function (opcao) { return opcao.value !== ''; })
      .map(function (opcao) { return opcao.text; });

    var listaPapelHtml = '<ul class="mb-0 pl-3">' + opcoesPapel
      .map(function (item) {
        var descricao = descricoesPapel[item] || '';
        return '<li><strong>' + item + ':</strong> <span>' + descricao + '</span></li>';
      })
      .join('') + '</ul>';

    if (papelModalBody) {
      papelModalBody.innerHTML = listaPapelHtml;
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(tooltipPapel).off('click.papelModal').on('click.papelModal', function () {
        window.jQuery('#papelInfoModal').modal('show');
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

  if (formAdicionarMiniatura && btnSalvarMiniatura) {
    formAdicionarMiniatura.addEventListener('submit', function () {
      btnSalvarMiniatura.disabled = true;
      btnSalvarMiniatura.textContent = 'Salvando...';
    });
  }
});
</script>
