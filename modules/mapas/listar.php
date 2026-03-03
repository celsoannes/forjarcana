<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM mapas WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario_id]);
$mapas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Mapas</h3>
    <div class="card-tools">
      <a href="?pagina=mapas&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar mapa
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($mapas): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mapas as $mapa): ?>
            <tr>
              <td><?= htmlspecialchars($mapa['nome']) ?></td>
              <td>
                <?php if (!empty($mapa['descricao'])): ?>
                  <?= htmlspecialchars($mapa['descricao']) ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($mapa['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=mapas&acao=editar&id=<?= $mapa['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-mapa" href="?pagina=mapas&acao=excluir&id=<?= $mapa['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhum mapa cadastrado.</div>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="modal-danger-excluir-mapa" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelMapa" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelMapa">Excluir Mapa</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-mapa">Tem certeza que deseja excluir este mapa? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-mapa" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-mapa">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let mapaExcluirId = null;
document.querySelectorAll('.btn-excluir-mapa').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    mapaExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-mapa').classList.add('d-none');
    $('#modal-danger-excluir-mapa').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-mapa').addEventListener('click', function() {
  if (mapaExcluirId) {
    fetch('modules/mapas/excluir.php?id=' + encodeURIComponent(mapaExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=mapas';
      } else {
        document.getElementById('modal-excluir-erro-mapa').textContent = result;
        document.getElementById('modal-excluir-erro-mapa').classList.remove('d-none');
      }
    });
  }
});
</script>
