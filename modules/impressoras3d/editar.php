<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$erro = '';
$id = intval($_GET['id'] ?? 0);

// Busca impressora do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$imp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$imp) {
    echo '<div class="alert alert-danger">Impressora não encontrada ou você não tem permissão!</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $preco_aquisicao = floatval($_POST['preco_aquisicao'] ?? 0);
    $potencia = intval($_POST['potencia'] ?? 0);
    $depreciacao = intval($_POST['depreciacao'] ?? 0);
    $tempo_vida_util = intval($_POST['tempo_vida_util'] ?? 0);

    if (!$marca || !$modelo || !$tipo || !$preco_aquisicao || !$potencia || !$depreciacao || !$tempo_vida_util) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE impressoras SET marca = ?, modelo = ?, tipo = ?, preco_aquisicao = ?, potencia = ?, depreciacao = ?, tempo_vida_util = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$marca, $modelo, $tipo, $preco_aquisicao, $potencia, $depreciacao, $tempo_vida_util, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=impressoras3d";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Impressora 3D</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars($imp['marca']) ?>">
      </div>
      <div class="form-group">
        <label for="modelo">Modelo</label>
        <input type="text" class="form-control" id="modelo" name="modelo" required value="<?= htmlspecialchars($imp['modelo']) ?>">
      </div>
      <div class="form-group">
        <label for="tipo">Tipo</label>
        <select class="form-control" id="tipo" name="tipo" required>
          <option value="">Selecione...</option>
          <option value="FDM" <?= ($imp['tipo'] === 'FDM') ? 'selected' : '' ?>>FDM</option>
          <option value="Resina" <?= ($imp['tipo'] === 'Resina') ? 'selected' : '' ?>>Resina</option>
        </select>
      </div>
      <div class="form-group">
        <label for="preco_aquisicao">Preço de Aquisição (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_aquisicao" name="preco_aquisicao" required value="<?= htmlspecialchars($imp['preco_aquisicao']) ?>">
      </div>
      <div class="form-group">
        <label for="potencia">Potência (W)</label>
        <input type="number" class="form-control" id="potencia" name="potencia" required value="<?= htmlspecialchars($imp['potencia']) ?>">
      </div>
      <div class="form-group">
        <label for="depreciacao">Depreciação (%)</label>
        <input type="number" class="form-control" id="depreciacao" name="depreciacao" required value="<?= htmlspecialchars($imp['depreciacao']) ?>">
      </div>
      <div class="form-group">
        <label for="tempo_vida_util">Tempo Vida Útil (h)</label>
        <input type="number" class="form-control" id="tempo_vida_util" name="tempo_vida_util" required value="<?= htmlspecialchars($imp['tempo_vida_util']) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=impressoras3d" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>