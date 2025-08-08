<?php
require __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/imagem.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_uuid'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para adicionar insumos.</div>';
    return;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_material = trim($_POST['nome_material'] ?? '');
    $tipo_material = trim($_POST['tipo_material'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $unidade_medida = $_POST['unidade_medida'] ?? '';
    $valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '');
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_uuid = $_SESSION['usuario_uuid'];
    $imagem_nome = '';

    // Upload da imagem usando função reutilizável
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem_nome = salvarImagemUsuario($_FILES['imagem'], $usuario_uuid, __DIR__ . '/../uploads');
    }

    if (!$nome_material || !$tipo_material || !$unidade_medida || $valor_unitario === '') {
        $mensagem = "Preencha todos os campos obrigatórios!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO insumos (usuario_id, nome_material, tipo_material, descricao, unidade_medida, valor_unitario, fornecedor, observacoes, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $nome_material, $tipo_material, $descricao, $unidade_medida, $valor_unitario, $fornecedor, $observacoes, $imagem_nome]);
        echo '<script>window.location.href="?pagina=insumos";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Insumo</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Nome do material *</label>
        <input type="text" name="nome_material" class="form-control" required value="<?= htmlspecialchars($_POST['nome_material'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo do material *</label>
        <input type="text" name="tipo_material" class="form-control" required value="<?= htmlspecialchars($_POST['tipo_material'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Unidade de medida *</label>
        <select name="unidade_medida" class="form-select" required>
            <option value="">Selecione...</option>
            <?php
            $unidades = [
                'un' => 'Unidade',
                'm' => 'Metros',
                'cm' => 'Centímetros',
                'mm' => 'Milímetros',
                'kg' => 'Kilos',
                'g' => 'Gramas',
                'L' => 'Litros',
                'mL' => 'Mililitros'
            ];
            foreach ($unidades as $valor => $label) {
                $selected = (isset($_POST['unidade_medida']) && $_POST['unidade_medida'] === $valor) ? 'selected' : '';
                echo "<option value=\"$valor\" $selected>$label</option>";
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Valor unitário (R$) *</label>
        <input type="number" step="0.01" name="valor_unitario" class="form-control" required value="<?= htmlspecialchars($_POST['valor_unitario'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Fornecedor</label>
        <input type="text" name="fornecedor" class="form-control" value="<?= htmlspecialchars($_POST['fornecedor'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control"><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Imagem</label>
        <input type="file" name="imagem" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp" id="inputImagem">
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
    <a href="?pagina=insumos" class="btn btn-voltar">Cancelar</a>
</form>

<div class="modal fade" id="modalFormatoImagem" tabindex="-1" aria-labelledby="modalFormatoImagemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#2c223b;color:#ffd700;">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFormatoImagemLabel">Formato não suportado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Formato de imagem não suportado.<br>
        Use PNG, JPG, JPEG, GIF ou WEBP.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-magic" data-bs-dismiss="modal">Ok</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var input = document.getElementById('inputImagem');
    if (input.files.length > 0) {
        var file = input.files[0];
        var formatosPermitidos = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        var ext = file.name.split('.').pop().toLowerCase();
        if (formatosPermitidos.indexOf(ext) === -1) {
            var modal = new bootstrap.Modal(document.getElementById('modalFormatoImagem'));
            modal.show();
            e.preventDefault();
            return false;
        }
    }
});
</script>