<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
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
    $imagem_nome = '';

    if (!empty($_POST['imagem_cortada'])) {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $_POST['imagem_cortada']);
        $data = base64_decode($data);

        $imagem_nome = uniqid('insumo_') . '.jpg';
        file_put_contents(__DIR__ . '/../uploads/' . $imagem_nome, $data);

    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem_nome = uniqid('insumo_') . '.' . $extensao;
        move_uploaded_file($_FILES['imagem']['tmp_name'], __DIR__ . '/../uploads/' . $imagem_nome);
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

<head>
    <!-- ...outros links e metas... -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
</head>

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
        <input type="file" name="imagem" class="form-control" accept="image/*" id="inputImagem">
    </div>
    <button type="submit" class="btn btn-success">Adicionar</button>
    <a href="?pagina=insumos" class="btn btn-secondary">Cancelar</a>
</form>

<!-- Modal para Crop -->
<div class="modal fade" id="modalCrop" tabindex="-1" aria-labelledby="modalCropLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCropLabel">Recortar Imagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="image-crop" style="max-width:100%; display:block; margin:auto;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnCrop" class="btn btn-success">Cortar e Usar</button>
      </div>
    </div>
  </div>
</div>

<!-- Campo oculto para guardar imagem cortada -->
<input type="hidden" name="imagem_cortada" id="imagem_cortada">

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cropper;
const modalCrop = new bootstrap.Modal(document.getElementById('modalCrop'));

document.getElementById('inputImagem').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const imgElement = document.getElementById('image-crop');
        imgElement.src = event.target.result;

        // Abre modal
        modalCrop.show();

        // Destroi cropper antigo, se existir
        if (cropper) cropper.destroy();

        // Inicializa Cropper.js quando imagem carregar
        imgElement.onload = function() {
            cropper = new Cropper(imgElement, {
                aspectRatio: 1, // ajusta conforme necessidade
                viewMode: 1,
                movable: true,
                zoomable: true
            });
        };
    };
    reader.readAsDataURL(file);
});

document.getElementById('btnCrop').addEventListener('click', function() {
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({
        width: 500,
        height: 500
    });

    canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
            document.getElementById('imagem_cortada').value = reader.result; // base64
            modalCrop.hide();
        };
        reader.readAsDataURL(blob);
    }, 'image/jpeg');
});
</script>
