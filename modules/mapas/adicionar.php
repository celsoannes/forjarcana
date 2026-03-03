<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = trim($_SESSION['usuario_uuid'] ?? '');
$erro = '';
$foto = null;
$imagens = [];
$avisos_upload = [];

function descreverErroUploadMapa(int $codigoErro): string {
  $mapa = [
    UPLOAD_ERR_INI_SIZE => 'Arquivo maior que o limite configurado no servidor.',
    UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite permitido pelo formulário.',
    UPLOAD_ERR_PARTIAL => 'Upload foi enviado parcialmente.',
    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
    UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
    UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco.',
    UPLOAD_ERR_EXTENSION => 'Upload bloqueado por uma extensão do PHP.',
  ];

  return $mapa[$codigoErro] ?? 'Erro desconhecido no envio do arquivo.';
}

if ($usuario_uuid === '' && $usuario_id > 0) {
  try {
    $stmtUuid = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ? LIMIT 1");
    $stmtUuid->execute([$usuario_id]);
    $usuario_uuid = (string) ($stmtUuid->fetchColumn() ?: '');
  } catch (Throwable $e) {
    $usuario_uuid = '';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $formato_grade = trim($_POST['formato_grade'] ?? '');
    $largura = (float) ($_POST['largura'] ?? 0);
    $comprimento = (float) ($_POST['comprimento'] ?? 0);
    $material = trim($_POST['material'] ?? '');
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $custo = (float) ($_POST['custo'] ?? 0);
    $markup = (float) ($_POST['markup'] ?? 2);
    $markup_valido = ($markup >= 1 && $markup <= 10 && ((int) round($markup * 2)) === (int) ($markup * 2));
    $fotoExistente = trim((string) ($_POST['foto_existente'] ?? ''));
    $foto = $fotoExistente !== '' ? $fotoExistente : null;
    $imagens = [];
    $imagensExistentesRaw = trim((string) ($_POST['imagens_existentes'] ?? ''));
    if ($imagensExistentesRaw !== '') {
      $imagensExistentes = json_decode($imagensExistentesRaw, true);
      if (is_array($imagensExistentes)) {
        foreach ($imagensExistentes as $imagemExistente) {
          if (is_string($imagemExistente) && trim($imagemExistente) !== '') {
            $imagens[] = trim($imagemExistente);
          }
        }
      }
    }

    if (!$erro && $usuario_uuid === '') {
      $erro = 'Não foi possível identificar o UUID do usuário para upload da imagem de capa.';
    }

    $tamanhosUpload = [
      'thumbnail' => [64, 64],
      'pequena' => [128, 128],
      'media' => [256, 256],
      'grande' => [512, 512],
    ];

    if (!$erro && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
      $fotoUpload = uploadImagem($_FILES['foto'], $usuario_uuid, 'usuarios', $tamanhosUpload, 'mapa_CAPA', false);
      if ($fotoUpload === false) {
        $erro = 'Erro ao enviar a imagem de capa. Verifique formato e tamanho do arquivo.';
      } else {
        $foto = $fotoUpload;
      }
    }

    if (!$erro && isset($_FILES['fotos']) && isset($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
      $totalArquivos = count($_FILES['fotos']['name']);
      for ($i = 0; $i < $totalArquivos; $i++) {
        $nomeArquivo = trim((string) ($_FILES['fotos']['name'][$i] ?? ''));
        $erroArquivo = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

        if ($nomeArquivo === '' || $erroArquivo === UPLOAD_ERR_NO_FILE) {
          continue;
        }

        if ($erroArquivo !== UPLOAD_ERR_OK) {
          $avisos_upload[] = 'A imagem adicional "' . $nomeArquivo . '" não foi enviada: ' . descreverErroUploadMapa((int) $erroArquivo);
          continue;
        }

        $arquivoImagem = [
          'name' => $nomeArquivo,
          'type' => $_FILES['fotos']['type'][$i] ?? '',
          'tmp_name' => $_FILES['fotos']['tmp_name'][$i] ?? '',
          'error' => $erroArquivo,
          'size' => $_FILES['fotos']['size'][$i] ?? 0,
        ];

        $imagemUpload = uploadImagem($arquivoImagem, $usuario_uuid, 'usuarios', $tamanhosUpload, 'mapa_IMAGEM', false);
        if ($imagemUpload === false) {
          $avisos_upload[] = 'A imagem adicional "' . $nomeArquivo . '" não pôde ser processada (formato ou conteúdo inválido).';
          continue;
        }

        $imagens[] = $imagemUpload;
      }
    }

    if (!$nome || !$formato_grade || $largura <= 0 || $comprimento <= 0 || !$material || $custo < 0 || !$markup_valido) {
        $erro = 'Preencha todos os campos obrigatórios corretamente.';
    } else {
        try {
      $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;
      $stmt = $pdo->prepare("INSERT INTO mapas (usuario_id, nome, descricao, imagem_capa, imagens, formato_grade, largura, comprimento, material, fornecedor, custo, markup, ultima_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
      $stmt->execute([$usuario_id, $nome, $descricao, $foto, $imagensJson, $formato_grade, $largura, $comprimento, $material, $fornecedor !== '' ? $fornecedor : null, $custo, $markup]);
            echo '<script>window.location.href="?pagina=mapas";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Mapa</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="form-adicionar-mapa">
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
            <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="formato_grade">
              Formato da Grade
              <button type="button" class="btn btn-xs btn-outline-info ml-2" data-toggle="modal" data-target="#modal-info-formato-grade">
                <i class="fas fa-info-circle"></i> Ver opções
              </button>
            </label>
            <select class="form-control" id="formato_grade" name="formato_grade" required>
              <option value="">Selecione...</option>
              <option value="sq-25">Quadrado 1" (25.4mm)</option>
              <option value="sq-38">Quadrado 1.5" (38mm)</option>
              <option value="hx-25">Hexágono 1" (25mm)</option>
              <option value="hx-32">Hexágono 1.25" (32mm)</option>
              <option value="hx-12">Hexágono Hexcrawl (12mm)</option>
              <option value="hx-30">Escaramuça (30mm)</option>
              <option value="dt-25">Grade de Pontos (Dots)</option>
              <option value="none">Sem Grade (Liso)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tamanho</label>
            <div class="row">
              <div class="col-md-6 mb-2 mb-md-0">
                <input type="number" class="form-control" id="largura" name="largura" step="0.01" min="0" placeholder="Largura" required>
              </div>
              <div class="col-md-6">
                <input type="number" class="form-control" id="comprimento" name="comprimento" step="0.01" min="0" placeholder="Comprimento" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="material">Material</label>
            <input type="text" class="form-control" id="material" name="material" required>
          </div>
          <div class="form-group">
            <label for="fornecedor">Fornecedor</label>
            <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?= htmlspecialchars($_POST['fornecedor'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="custo">Custo</label>
            <input type="number" class="form-control" id="custo" name="custo" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label for="markup">Markup</label>
            <select class="form-control" id="markup" name="markup" required>
              <option value="1">1</option>
              <option value="1.5">1.5</option>
              <option value="2" selected>2</option>
              <option value="2.5">2.5</option>
              <option value="3">3</option>
              <option value="3.5">3.5</option>
              <option value="4">4</option>
              <option value="4.5">4.5</option>
              <option value="5">5</option>
              <option value="5.5">5.5</option>
              <option value="6">6</option>
              <option value="6.5">6.5</option>
              <option value="7">7</option>
              <option value="7.5">7.5</option>
              <option value="8">8</option>
              <option value="8.5">8.5</option>
              <option value="9">9</option>
              <option value="9.5">9.5</option>
              <option value="10">10</option>
            </select>
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
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
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
