<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';

// Busca estudios do usuário para o select
$stmt = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $stmt = $pdo->prepare("INSERT INTO colecoes (usuario_id, estudio_id, nome, ultima_atualizacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$usuario_id, $estudio_id, $nome]);
            echo '<script>window.location.href="?pagina=colecoes";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar coleção: ' . $e->getMessage();
        }
    }
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
        <input type="text" class="form-control" id="nome" name="nome" required>
      </div>
      <div class="form-group">
        <label for="estudio_nome">Estudio</label>
        <select class="form-control select2" id="estudio_nome" name="estudio_nome" required style="width: 100%;">
          <option value="">Selecione...</option>
          <?php foreach ($estudios as $estudio): ?>
            <option value="<?= htmlspecialchars($estudio['nome']) ?>"><?= htmlspecialchars($estudio['nome']) ?></option>
          <?php endforeach; ?>
        </select>
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
    $('.select2').select2({
      width: '100%',
      placeholder: 'Selecione...',
      allowClear: true,
      tags: true, // Permite adicionar novo valor digitado
      language: {
        noResults: function() {
          return "Nenhum resultado encontrado";
        }
      }
    });
  });
</script>