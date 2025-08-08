<?php
require __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/imagem.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_uuid'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar insumos.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$usuario_uuid = $_SESSION['usuario_uuid'];
$mensagem = "";

// Busca os dados atuais do insumo
$stmt = $pdo->prepare("SELECT * FROM insumos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$insumo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$insumo) {
    echo '<div class="alert alert-danger">Insumo não encontrado ou você não tem permissão para editá-lo.</div>';
    return;
}

// Atualiza os dados se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_material = trim($_POST['nome_material'] ?? '');
    $tipo_material = trim($_POST['tipo_material'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $unidade_medida = $_POST['unidade_medida'] ?? '';
    $valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '');
    $fornecedor = trim($_POST['fornecedor'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $imagem_nome = $insumo['imagem'];

    // Upload de nova imagem (opcional)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        // Remove imagens antigas
        if (!empty($imagem_nome)) {
            $base = str_replace('_m.png', '', $imagem_nome);
            $media_path = __DIR__ . '/../uploads/' . $base . '_m.png';
            $thumb_path = __DIR__ . '/../uploads/' . $base . '_t.png';
            if (file_exists($media_path)) unlink($media_path);
            if (file_exists($thumb_path)) unlink($thumb_path);
        }
        // Salva nova imagem
        $imagem_nome = salvarImagemUsuario($_FILES['imagem'], $usuario_uuid, __DIR__ . '/../uploads');
    }

    if (!$nome_material || !$tipo_material || !$unidade_medida || $valor_unitario === '') {
        $mensagem = "Preencha todos os campos obrigatórios!";
    } else {
        $stmt = $pdo->prepare("UPDATE insumos SET nome_material=?, tipo_material=?, descricao=?, unidade_medida=?, valor_unitario=?, fornecedor=?, observacoes=?, imagem=? WHERE id=? AND usuario_id=?");
        $stmt->execute([
            $nome_material, $tipo_material, $descricao, $unidade_medida, $valor_unitario,
            $fornecedor, $observacoes, $imagem_nome, $id, $usuario_id
        ]);
        echo '<script>window.location.href="?pagina=insumos";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Editar Insumo</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" class="mb-4" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Nome do material *</label>
        <input type="text" name="nome_material" class="form-control" required value="<?= htmlspecialchars($_POST['nome_material'] ?? $insumo['nome_material']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Tipo do material *</label>
        <input type="text" name="tipo_material" class="form-control" required value="<?= htmlspecialchars($_POST['tipo_material'] ?? $insumo['tipo_material']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control"><?= htmlspecialchars($_POST['descricao'] ?? $insumo['descricao']) ?></textarea>
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
            $valor_selecionado = $_POST['unidade_medida'] ?? $insumo['unidade_medida'];
            foreach ($unidades as $valor => $label) {
                $selected = ($valor_selecionado === $valor) ? 'selected' : '';
                echo "<option value=\"$valor\" $selected>$label</option>";
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Valor unitário (R$) *</label>
        <input type="number" step="0.01" name="valor_unitario" class="form-control" required value="<?= htmlspecialchars($_POST['valor_unitario'] ?? $insumo['valor_unitario']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Fornecedor</label>
        <input type="text" name="fornecedor" class="form-control" value="<?= htmlspecialchars($_POST['fornecedor'] ?? $insumo['fornecedor']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control"><?= htmlspecialchars($_POST['observacoes'] ?? $insumo['observacoes']) ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Imagem atual</label><br>
        <?php if (!empty($insumo['imagem'])): 
            $thumb = str_replace('_m.png', '_t.png', $insumo['imagem']);
        ?>
            <img src="/forjarcana/uploads/<?= htmlspecialchars($thumb) ?>" alt="Thumb" style="max-width:80px;max-height:80px;border-radius:8px;">
        <?php else: ?>
            <span class="text-muted">Sem imagem</span>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label">Nova imagem (opcional)</label>
        <input type="file" name="imagem" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp" id="inputImagem">
    </div>
    <button type="submit" class="btn btn-success">Salvar alterações</button>
    <a href="?pagina=insumos" class="btn btn-secondary">Cancelar</a>
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