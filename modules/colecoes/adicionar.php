<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Colecoes\ColecaoController;

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

$colecaoController = new ColecaoController($pdo);
$contextoAdicao = $colecaoController->carregarContextoAdicao((int) $usuario_id);
$estudios = is_array($contextoAdicao['estudios'] ?? null) ? $contextoAdicao['estudios'] : [];
$dadosFormulario = $colecaoController->montarEstadoFormularioAdicao($_POST ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $resultadoFluxo = $colecaoController->processarFluxoAdicao((int) $usuario_id, $_POST);

  if (!empty($resultadoFluxo['sucesso'])) {
    echo '<script>window.location.href="?pagina=colecoes";</script>';
    exit;
    }

  $erro = (string) ($resultadoFluxo['erro'] ?? 'Erro ao cadastrar coleção.');
}
?>
<!-- Select2 CSS já está correto -->
<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Coleção</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars((string) ($dadosFormulario['nome'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="estudio_nome">Estudio</label>
        <select class="form-control select2" id="estudio_nome" name="estudio_nome" required style="width: 100%;">
          <option value="">Selecione...</option>
          <?php foreach ($estudios as $estudio): ?>
            <?php $nomeEstudio = (string) ($estudio['nome'] ?? ''); ?>
            <option value="<?= htmlspecialchars($nomeEstudio) ?>" <?= ((string) ($dadosFormulario['estudio_nome'] ?? '') === $nomeEstudio) ? 'selected' : '' ?>><?= htmlspecialchars($nomeEstudio) ?></option>
          <?php endforeach; ?>
        </select>
        <small id="estudio-msg" class="form-text text-danger" style="display:none;"></small>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=colecoes" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/select2/js/select2.full.min.js"></script>
<script>
  $(function () {
    var estudios = [
      <?php foreach ($estudios as $estudio): ?>
        "<?= addslashes($estudio['nome']) ?>",
      <?php endforeach; ?>
    ];

    $('.select2').select2({
      width: '100%',
      placeholder: 'Selecione...',
      allowClear: true,
      tags: true,
      language: {
        noResults: function() {
          return "Nenhum resultado encontrado";
        }
      }
    });

    var estudioInicial = <?= json_encode((string) ($dadosFormulario['estudio_nome'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    if (estudioInicial) {
      var existe = estudios.some(function (e) { return e.toLowerCase() === estudioInicial.toLowerCase(); });
      if (!existe) {
        var novaOpcao = new Option(estudioInicial, estudioInicial, true, true);
        $('#estudio_nome').append(novaOpcao);
      }
      $('#estudio_nome').val(estudioInicial).trigger('change');
    }

    $('#estudio_nome').on('change input', function() {
      var valor = $(this).val().trim();
      var msg = $('#estudio-msg');
      if (valor.length > 0 && !estudios.some(e => e.toLowerCase() === valor.toLowerCase())) {
        msg.text('Este estudio ainda não está cadastrado. Será criado ao salvar.');
        msg.show();
      } else {
        msg.hide();
      }
    });
  });
</script>