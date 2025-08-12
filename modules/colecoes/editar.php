<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';
$id = intval($_GET['id'] ?? 0);

// Busca dados da coleção
$stmt = $pdo->prepare("SELECT * FROM colecoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$colecao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colecao) {
    echo '<div class="alert alert-danger">Coleção não encontrada.</div>';
    exit;
}

// Busca estudios do usuário para o select
$stmt = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca nome do estudio atual
$stmt = $pdo->prepare("SELECT nome FROM estudios WHERE id = ? AND usuario_id = ?");
$stmt->execute([$colecao['estudio_id'], $usuario_id]);
$estudio_atual = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $estudio_nome = trim($_POST['estudio_nome'] ?? '');

    // Busca o id do estudio pelo nome
    $stmt = $pdo->prepare("SELECT id FROM estudios WHERE nome = ? AND usuario_id = ?");
    $stmt->execute([$estudio_nome, $usuario_id]);
    $estudio = $stmt->fetch(PDO::FETCH_ASSOC);
    $estudio_id = $estudio ? $estudio['id'] : 0;

    // Se não existe, cria o estudio e pega o id
    if (!$estudio_id && $estudio_nome) {
        try {
            $stmt = $pdo->prepare("INSERT INTO estudios (usuario_id, nome, site, ultima_atualizacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario_id, $estudio_nome, '']);
            $estudio_id = $pdo->lastInsertId();
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar estudio: ' . $e->getMessage();
        }
    }

    if (!$nome || !$estudio_id) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE colecoes SET estudio_id = ?, nome = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$estudio_id, $nome, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=colecoes";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar coleção: ' . $e->getMessage();
        }
    }
}
?>
<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Coleção</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($colecao['nome']) ?>">
      </div>
      <div class="form-group">
        <label for="estudio_nome">Estudio</label>
        <select class="form-control select2" id="estudio_nome" name="estudio_nome" required style="width: 100%;">
          <option value="">Selecione...</option>
          <?php foreach ($estudios as $estudio): ?>
            <option value="<?= htmlspecialchars($estudio['nome']) ?>"
              <?= ($estudio_atual === $estudio['nome']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($estudio['nome']) ?>
            </option>
          <?php endforeach; ?>
          <?php if ($estudio_atual && !in_array($estudio_atual, array_column($estudios, 'nome'))): ?>
            <option value="<?= htmlspecialchars($estudio_atual) ?>" selected><?= htmlspecialchars($estudio_atual) ?></option>
          <?php endif; ?>
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
    // Fim do evento
  });
});
// Fim do script
</script>