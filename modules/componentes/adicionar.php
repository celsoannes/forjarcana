<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_uuid = $_SESSION['usuario_uuid'] ?? '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_material = trim($_POST['nome_material'] ?? '');
    $tipo_material = trim($_POST['tipo_material'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $unidade_medida = $_POST['unidade_medida'] ?? '';
    $valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '');
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $imagem = '';

    // Validação dos campos obrigatórios
    if (!$nome_material || !$tipo_material || !$unidade_medida || !$valor_unitario) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        // Upload da imagem (opcional)
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $imagem = uploadImagem($_FILES['imagem'], $usuario_uuid, 'usuarios', null, 'componente', false);
            if (!$imagem) {
                $erro = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
            }
        }

        if (!$erro) {
            try {
                $stmt = $pdo->prepare("INSERT INTO componentes 
                    (usuario_id, nome_material, tipo_material, descricao, unidade_medida, valor_unitario, fornecedor, observacoes, imagem) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $usuario_id,
                    $nome_material,
                    $tipo_material,
                    $descricao,
                    $unidade_medida,
                    $valor_unitario,
                    $fornecedor,
                    $observacoes,
                    $imagem
                ]);
                echo '<script>window.location.href="?pagina=componentes";</script>';
                exit;
            } catch (PDOException $e) {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Componente</h3>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome_material">Nome do Material</label>
        <input type="text" class="form-control" id="nome_material" name="nome_material" required>
      </div>
      <div class="form-group">
        <label for="tipo_material">Tipo do Material</label>
        <input type="text" class="form-control" id="tipo_material" name="tipo_material" required>
      </div>
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao"></textarea>
      </div>
      <div class="form-group">
        <label for="unidade_medida">Unidade de Medida</label>
        <select class="form-control" id="unidade_medida" name="unidade_medida" required>
          <option value="">Selecione</option>
          <option value="un">Unidade</option>
          <option value="m">Metro</option>
          <option value="cm">Centímetro</option>
          <option value="mm">Milímetro</option>
          <option value="kg">Quilograma</option>
          <option value="g">Grama</option>
          <option value="L">Litro</option>
          <option value="mL">Mililitro</option>
        </select>
      </div>
      <div class="form-group">
        <label for="valor_unitario">Valor Unitário (R$)</label>
        <input type="number" step="0.01" class="form-control" id="valor_unitario" name="valor_unitario" required>
      </div>
      <div class="form-group">
        <label for="fornecedor">Fornecedor</label>
        <input type="text" class="form-control" id="fornecedor" name="fornecedor">
      </div>
      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes"></textarea>
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