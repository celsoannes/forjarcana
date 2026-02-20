<?php
require_once __DIR__ . '/../../app/db.php';

$id = intval($_GET['id'] ?? 0);
$erro = '';

$stmt = $pdo->prepare("SELECT * FROM miniaturas WHERE id = ?");
$stmt->execute([$id]);
$miniatura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$miniatura) {
    header('Location: ?pagina=miniaturas');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $estudio = trim($_POST['estudio'] ?? '');
    $tematica = trim($_POST['tematica'] ?? '');
    $colecao = trim($_POST['colecao'] ?? '');
    $raca = trim($_POST['raca'] ?? '');
    $classe = trim($_POST['classe'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $criatura = trim($_POST['criatura'] ?? '');
    $papel = trim($_POST['papel'] ?? '');
    $tamanho = trim($_POST['tamanho'] ?? '');
    $base = trim($_POST['base'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $pintada = isset($_POST['pintada']) ? 1 : 0;
    $arma_principal = trim($_POST['arma_principal'] ?? '');
    $arma_secundaria = trim($_POST['arma_secundaria'] ?? '');
    $armadura = trim($_POST['armadura'] ?? '');
    $outras_caracteristicas = trim($_POST['outras_caracteristicas'] ?? '');
    $foto = trim($_POST['foto'] ?? '');

    if (!$sku || !$estudio) {
        $erro = 'Preencha os campos obrigatórios: SKU e Estúdio.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE miniaturas SET nome = ?, sku = ?, estudio = ?, tematica = ?, colecao = ?, raca = ?, classe = ?, genero = ?, criatura = ?, papel = ?, tamanho = ?, base = ?, material = ?, pintada = ?, arma_principal = ?, arma_secundaria = ?, armadura = ?, outras_caracteristicas = ?, foto = ? WHERE id = ?");
            $stmt->execute([
                $nome ?: null,
                $sku,
                $estudio,
                $tematica ?: null,
                $colecao ?: null,
                $raca ?: null,
                $classe ?: null,
                $genero ?: null,
                $criatura ?: null,
                $papel ?: null,
                $tamanho ?: null,
                $base ?: null,
                $material ?: null,
                $pintada,
                $arma_principal ?: null,
                $arma_secundaria ?: null,
                $armadura ?: null,
                $outras_caracteristicas ?: null,
                $foto ?: null,
                $id
            ]);

            echo '<script>window.location.href="?pagina=miniaturas";</script>';
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'SKU já cadastrado. Informe um SKU único.';
            } else {
                $erro = 'Erro ao editar miniatura: ' . $e->getMessage();
            }
        }
    }

    $miniatura = array_merge($miniatura, [
        'nome' => $nome,
        'sku' => $sku,
        'estudio' => $estudio,
        'tematica' => $tematica,
        'colecao' => $colecao,
        'raca' => $raca,
        'classe' => $classe,
        'genero' => $genero,
        'criatura' => $criatura,
        'papel' => $papel,
        'tamanho' => $tamanho,
        'base' => $base,
        'material' => $material,
        'pintada' => $pintada,
        'arma_principal' => $arma_principal,
        'arma_secundaria' => $arma_secundaria,
        'armadura' => $armadura,
        'outras_caracteristicas' => $outras_caracteristicas,
        'foto' => $foto
    ]);
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Miniatura</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="nome">Nome</label>
          <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($miniatura['nome'] ?? '') ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="sku">SKU *</label>
          <input type="text" class="form-control" id="sku" name="sku" required value="<?= htmlspecialchars($miniatura['sku']) ?>">
        </div>
        <div class="form-group col-md-3">
          <label for="estudio">Estúdio *</label>
          <input type="text" class="form-control" id="estudio" name="estudio" required value="<?= htmlspecialchars($miniatura['estudio']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3"><label for="tematica">Temática</label><input type="text" class="form-control" id="tematica" name="tematica" value="<?= htmlspecialchars($miniatura['tematica'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="colecao">Coleção</label><input type="text" class="form-control" id="colecao" name="colecao" value="<?= htmlspecialchars($miniatura['colecao'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="raca">Raça</label><input type="text" class="form-control" id="raca" name="raca" value="<?= htmlspecialchars($miniatura['raca'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="classe">Classe</label><input type="text" class="form-control" id="classe" name="classe" value="<?= htmlspecialchars($miniatura['classe'] ?? '') ?>"></div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3"><label for="genero">Gênero</label><input type="text" class="form-control" id="genero" name="genero" value="<?= htmlspecialchars($miniatura['genero'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="criatura">Criatura</label><input type="text" class="form-control" id="criatura" name="criatura" value="<?= htmlspecialchars($miniatura['criatura'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="papel">Papel</label><input type="text" class="form-control" id="papel" name="papel" value="<?= htmlspecialchars($miniatura['papel'] ?? '') ?>"></div>
        <div class="form-group col-md-3"><label for="tamanho">Tamanho</label><input type="text" class="form-control" id="tamanho" name="tamanho" value="<?= htmlspecialchars($miniatura['tamanho'] ?? '') ?>"></div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4"><label for="base">Base</label><input type="text" class="form-control" id="base" name="base" value="<?= htmlspecialchars($miniatura['base'] ?? '') ?>"></div>
        <div class="form-group col-md-4"><label for="material">Material</label><input type="text" class="form-control" id="material" name="material" value="<?= htmlspecialchars($miniatura['material'] ?? '') ?>"></div>
        <div class="form-group col-md-4 d-flex align-items-center pt-4">
          <div class="icheck-primary">
            <input type="checkbox" id="pintada" name="pintada" <?= !empty($miniatura['pintada']) ? 'checked' : '' ?>>
            <label for="pintada">Pintada</label>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4"><label for="arma_principal">Arma Principal</label><input type="text" class="form-control" id="arma_principal" name="arma_principal" value="<?= htmlspecialchars($miniatura['arma_principal'] ?? '') ?>"></div>
        <div class="form-group col-md-4"><label for="arma_secundaria">Arma Secundária</label><input type="text" class="form-control" id="arma_secundaria" name="arma_secundaria" value="<?= htmlspecialchars($miniatura['arma_secundaria'] ?? '') ?>"></div>
        <div class="form-group col-md-4"><label for="armadura">Armadura</label><input type="text" class="form-control" id="armadura" name="armadura" value="<?= htmlspecialchars($miniatura['armadura'] ?? '') ?>"></div>
      </div>

      <div class="form-group">
        <label for="outras_caracteristicas">Outras Características</label>
        <textarea class="form-control" id="outras_caracteristicas" name="outras_caracteristicas" rows="2"><?= htmlspecialchars($miniatura['outras_caracteristicas'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="foto">Foto (caminho)</label>
        <input type="text" class="form-control" id="foto" name="foto" placeholder="uploads/miniaturas/foto.jpg" value="<?= htmlspecialchars($miniatura['foto'] ?? '') ?>">
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=miniaturas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
