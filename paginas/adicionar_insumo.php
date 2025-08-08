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

    // Salva imagem cortada se existir
    if (!empty($_POST['imagem_cortada'])) {
        $data = $_POST['imagem_cortada'];
        // Remove prefixo data:image/jpeg;base64,
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
        $data = base64_decode($data);

        $imagem_nome = uniqid('insumo_') . '.jpg';
        file_put_contents(__DIR__ . '/../uploads/' . $imagem_nome, $data);

    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem_nome = uniqid('insumo_') . '.' . $extensao;
        $destino = __DIR__ . '/../uploads/' . $imagem_nome;
        move_uploaded_file($_FILES['imagem']['tmp_name'], $destino);
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />

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
        <div id="croppy-container" style="display:none; margin-top:15px;">
            <img id="image-crop" style="max-width:100%;">
        </div>
        <button type="button" id="btnCrop" class="btn btn-success mt-2" style="display:none;">Cortar e Usar</button>
        <!-- Campo oculto para enviar imagem cortada -->
        <input type="hidden" name="imagem_cortada" id="imagem_cortada">
    </div>
    <button type="submit" class="btn btn-success">Adicionar</button>
    <a href="?pagina=insumos" class="btn btn-secondary">Cancelar</a>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper;

document.getElementById('inputImagem').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const imgElement = document.getElementById('image-crop');
        imgElement.src = event.target.result;
        document.getElementById('croppy-container').style.display = 'block';
        document.getElementById('btnCrop').style.display = 'inline-block';

        // Destroi cropper antigo, se existir
        if (cropper) cropper.destroy();

        // Inicializa cropper
        cropper = new Cropper(imgElement, {
            aspectRatio: 1, // Ajuste conforme necessário
            viewMode: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false
        });
    };
    reader.readAsDataURL(file);
});

document.getElementById('btnCrop').addEventListener('click', function() {
    if (!cropper) return;

    // Converte o recorte para base64
    const canvas = cropper.getCroppedCanvas({
        width: 500, // largura final
        height: 500 // altura final
    });

    canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
            document.getElementById('imagem_cortada').value = reader.result; // base64
            document.getElementById('croppy-container').style.display = 'none';
            document.getElementById('btnCrop').style.display = 'none';
        };
        reader.readAsDataURL(blob);
    });
});
</script>