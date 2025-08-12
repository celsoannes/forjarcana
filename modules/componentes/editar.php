<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = $_SESSION['usuario_uuid'] ?? '';
$id = $_GET['id'] ?? '';
$erro = '';

// Busca componente
$stmt = $pdo->prepare("SELECT * FROM componentes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$componente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$componente) {
    header('Location: /404.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_material = trim($_POST['nome_material'] ?? '');
    $tipo_material = trim($_POST['tipo_material'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $unidade_medida = $_POST['unidade_medida'] ?? '';
    $valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '');
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $imagem = $componente['imagem'];

    // Validação dos campos obrigatórios
    if (!$nome_material || !$tipo_material || !$unidade_medida || !$valor_unitario) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        // Upload da imagem (opcional)
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            // Antes do uploadImagem, apague apenas as imagens do componente editado
            if (!empty($componente['imagem'])) {
                $baseDir = __DIR__ . "/../../" . dirname($componente['imagem']);
                $prefix = pathinfo($componente['imagem'], PATHINFO_FILENAME);
                $prefix = preg_replace('/_media$/', '', $prefix); // Remove o sufixo _media

                // Exclui todos os tamanhos do componente editado
                $tipos = ['thumb', 'pequena', 'media', 'grande'];
                foreach ($tipos as $tipo) {
                    $arquivo = "$baseDir/{$prefix}_{$tipo}.png";
                    if (file_exists($arquivo)) {
                        unlink($arquivo);
                    }
                }
            }

            // Agora faz o upload normalmente, sem apagar todas as imagens
            $imagem = uploadImagem($_FILES['imagem'], $usuario_uuid, 'usuarios', null, 'componente', false);
            if (!$imagem) {
                $erro = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
            }
        }

        if (!$erro) {
            try {
                $stmt = $pdo->prepare("UPDATE componentes SET 
                    nome_material = ?, tipo_material = ?, descricao = ?, unidade_medida = ?, valor_unitario = ?, fornecedor = ?, observacoes = ?, imagem = ?
                    WHERE id = ? AND usuario_id = ?");
                $stmt->execute([
                    $nome_material,
                    $tipo_material,
                    $descricao,
                    $unidade_medida,
                    $valor_unitario,
                    $fornecedor,
                    $observacoes,
                    $imagem,
                    $id,
                    $usuario_id
                ]);
                echo '<script>window.location.href="?pagina=componentes";</script>';
                exit;
            } catch (PDOException $e) {
                $erro = 'Erro ao editar: ' . $e->getMessage();
            }
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Componente</h3>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome_material">Nome do Material</label>
        <input type="text" class="form-control" id="nome_material" name="nome_material" required value="<?= htmlspecialchars($componente['nome_material']) ?>">
      </div>
      <div class="form-group">
        <label for="tipo_material">Tipo do Material</label>
        <input type="text" class="form-control" id="tipo_material" name="tipo_material" required value="<?= htmlspecialchars($componente['tipo_material']) ?>">
      </div>
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao"><?= htmlspecialchars($componente['descricao']) ?></textarea>
      </div>
      <div class="form-group">
        <label for="unidade_medida">Unidade de Medida</label>
        <select class="form-control" id="unidade_medida" name="unidade_medida" required>
          <option value="">Selecione</option>
          <option value="un" <?= $componente['unidade_medida'] === 'un' ? 'selected' : '' ?>>Unidade</option>
          <option value="m" <?= $componente['unidade_medida'] === 'm' ? 'selected' : '' ?>>Metro</option>
          <option value="cm" <?= $componente['unidade_medida'] === 'cm' ? 'selected' : '' ?>>Centímetro</option>
          <option value="mm" <?= $componente['unidade_medida'] === 'mm' ? 'selected' : '' ?>>Milímetro</option>
          <option value="kg" <?= $componente['unidade_medida'] === 'kg' ? 'selected' : '' ?>>Quilograma</option>
          <option value="g" <?= $componente['unidade_medida'] === 'g' ? 'selected' : '' ?>>Grama</option>
          <option value="L" <?= $componente['unidade_medida'] === 'L' ? 'selected' : '' ?>>Litro</option>
          <option value="mL" <?= $componente['unidade_medida'] === 'mL' ? 'selected' : '' ?>>Mililitro</option>
        </select>
      </div>
      <div class="form-group">
        <label for="valor_unitario">Valor Unitário (R$)</label>
        <input type="number" step="0.01" class="form-control" id="valor_unitario" name="valor_unitario" required value="<?= htmlspecialchars($componente['valor_unitario']) ?>">
      </div>
      <div class="form-group">
        <label for="fornecedor">Fornecedor</label>
        <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?= htmlspecialchars($componente['fornecedor']) ?>">
      </div>
      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes"><?= htmlspecialchars($componente['observacoes']) ?></textarea>
      </div>
      <div class="form-group">
        <label for="imagem">Imagem do Componente</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="imagem" name="imagem" accept="image/png,image/jpeg,image/webp,image/gif">
          <label class="custom-file-label" for="imagem">Selecione uma imagem</label>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=componentes" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(document).ready(function () {
    bsCustomFileInput.init();
  });
</script>