<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca as impressoras do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Impressoras 3D</h3>
    <div class="card-tools">
      <a href="?pagina=impressoras3d&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar impressora
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($impressoras): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Capa</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Tipo</th>
            <th>Preço Aquisição</th>
            <th>Potência (W)</th>
            <th>Depreciação (%)</th>
            <th>Fator de Uso (%)</th>
            <th>Tempo Vida Útil (h)</th>
            <th>Custo Hora</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($impressoras as $imp): ?>
            <tr>
                <td>
                  <?php
                    $capa = $imp['capa'] ?? '';
                    if ($capa) {
                      $thumbnail = preg_replace('/_media\.webp$/', '_thumbnail.webp', $capa);
                      $media = preg_replace('/_thumbnail\.webp$/', '_media.webp', $thumbnail);
                      echo '<img src="' . htmlspecialchars($thumbnail) . '" data-preview-src="' . htmlspecialchars($media) . '" alt="Capa" class="img-capa-thumb" style="max-width:60px; max-height:60px; border-radius:6px; object-fit:cover;">';
                    } else {
                      echo '<span class="text-muted">Sem capa</span>';
                    }
                  
                  ?>
                </td>
              <td><?= htmlspecialchars($imp['marca']) ?></td>
              <td><?= htmlspecialchars($imp['modelo']) ?></td>
              <td><?= htmlspecialchars($imp['tipo']) ?></td>
              <td>R$ <?= number_format($imp['preco_aquisicao'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars($imp['potencia']) ?> W</td>
              <td><?= htmlspecialchars($imp['depreciacao']) ?>%</td>
              <td><?= htmlspecialchars($imp['fator_uso']) ?>%</td>
              <td><?= htmlspecialchars($imp['tempo_vida_util']) ?> h</td>
              <td>R$ <?= number_format($imp['custo_hora'], 4, ',', '.') ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($imp['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=impressoras3d&acao=editar&id=<?= $imp['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-impressora" href="?pagina=impressoras3d&acao=excluir&id=<?= $imp['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhuma impressora cadastrada.</div>
    <?php endif; ?>
  </div>
<!-- Preview da capa ao passar o mouse -->
<div id="preview-capa-hover" class="preview-capa-hover" style="position: fixed; display: none; z-index: 1080; pointer-events: none; background: #fff; padding: 6px; border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);">
  <img src="" alt="Pré-visualização da capa" style="display: block; width: 220px; height: 220px; object-fit: cover; border-radius: 4px;">
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var preview = document.getElementById('preview-capa-hover');
  if (!preview) return;
  var previewImg = preview.querySelector('img');
  if (!previewImg) return;

  function posicionarPreview(evento) {
    var offsetX = 18;
    var offsetY = 18;
    var largura = 232;
    var altura = 232;
    var x = evento.clientX + offsetX;
    var y = evento.clientY + offsetY;
    if (x + largura > window.innerWidth) x = evento.clientX - largura - 12;
    if (y + altura > window.innerHeight) y = evento.clientY - altura - 12;
    preview.style.left = x + 'px';
    preview.style.top = y + 'px';
  }

  document.querySelectorAll('.img-capa-thumb[data-preview-src]').forEach(function (thumb) {
    thumb.addEventListener('mouseenter', function (evento) {
      var src = this.getAttribute('data-preview-src') || '';
      if (!src) return;
      previewImg.src = src;
      preview.style.display = 'block';
      posicionarPreview(evento);
    });
    thumb.addEventListener('mousemove', function (evento) {
      if (preview.style.display === 'block') posicionarPreview(evento);
    });
    thumb.addEventListener('mouseleave', function () {
      preview.style.display = 'none';
      previewImg.src = '';
    });
  });
});
</script>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-impressora" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelImpressora" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelImpressora">Excluir Impressora</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-impressora">Tem certeza que deseja excluir esta impressora? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-impressora" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-impressora">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let impressoraExcluirId = null;
document.querySelectorAll('.btn-excluir-impressora').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    impressoraExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-impressora').classList.add('d-none');
    $('#modal-danger-excluir-impressora').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-impressora').addEventListener('click', function() {
  if (impressoraExcluirId) {
    fetch('modules/impressoras3d/excluir.php?id=' + encodeURIComponent(impressoraExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=impressoras3d';
      } else {
        document.getElementById('modal-excluir-erro-impressora').textContent = result;
        document.getElementById('modal-excluir-erro-impressora').classList.remove('d-none');
      }
    });
  }
});
</script>