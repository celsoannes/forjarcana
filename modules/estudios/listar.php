<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca os estudios do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM estudios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Estudios</h3>
    <div class="card-tools">
      <a href="?pagina=estudios&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar estudio
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($estudios): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Site</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estudios as $estudio): ?>
            <tr>
              <td><?= htmlspecialchars($estudio['nome']) ?></td>
              <td>
                <?php if (!empty($estudio['site'])): ?>
                  <a href="<?= htmlspecialchars($estudio['site']) ?>" target="_blank"><?= htmlspecialchars($estudio['site']) ?></a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($estudio['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=estudios&acao=editar&id=<?= $estudio['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-estudio" href="?pagina=estudios&acao=excluir&id=<?= $estudio['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhum estudio cadastrado.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-estudio" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelEstudio" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelEstudio">Excluir Estudio</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-estudio">Tem certeza que deseja excluir este estudio? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-estudio" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-estudio">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let estudioExcluirId = null;
document.querySelectorAll('.btn-excluir-estudio').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    estudioExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-estudio').classList.add('d-none');
    $('#modal-danger-excluir-estudio').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-estudio').addEventListener('click', function() {
  if (estudioExcluirId) {
    fetch('modules/estudios/excluir.php?id=' + encodeURIComponent(estudioExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=estudios';
      } else {
        document.getElementById('modal-excluir-erro-estudio').textContent = result;
        document.getElementById('modal-excluir-erro-estudio').classList.remove('d-none');
      }
    });
  }
});
</script>