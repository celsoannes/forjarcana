<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? '';
$erro = '';

// Busca resina
$stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$resina = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resina) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $cor = trim($_POST['cor'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || !$cor || !$preco_litro) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE resinas SET nome = ?, marca = ?, cor = ?, preco_litro = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $marca, $cor, $preco_litro, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=resinas";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Resina</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($resina['nome']) ?>">
      </div>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars($resina['marca']) ?>">
      </div>
      <div class="form-group">
        <label for="cor">Cor (HEX, ex: #FF0000)</label>
        <div class="input-group my-colorpicker2">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-square"></i></span>
          </div>
          <input type="text" class="form-control" id="cor" name="cor" required placeholder="#RRGGBB" value="<?= htmlspecialchars($resina['cor']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="preco_litro">Preço por Litro (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_litro" name="preco_litro" required value="<?= htmlspecialchars($resina['preco_litro']) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=resinas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<link rel="stylesheet" href="<?= $baseUrl ?>/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
<script src="<?= $baseUrl ?>/plugins/jquery/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<script>
  $(function () {
    $('.my-colorpicker2').colorpicker();

    // Aguarda o colorpicker inicializar e define a cor do ícone
    var corInicial = $('#cor').val();
    if (corInicial) {
      $('.my-colorpicker2 .fa-square').css('color', corInicial);
    }

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    });

    // Ao clicar em qualquer parte do grupo, abre o colorpicker
    $('.my-colorpicker2').on('click', function(e) {
      if (!$(e.target).is('input')) {
        $(this).find('input').focus().trigger('click');
      }
    });
  });
</script>