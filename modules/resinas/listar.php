<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca as resinas do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Resinas</h3>
    <div class="card-tools">
      <a href="?pagina=resinas&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar resina
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($resinas): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Cor</th>
            <th>Preço/Litro (R$)</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resinas as $resina): ?>
            <tr>
              <td><?= htmlspecialchars($resina['nome']) ?></td>
              <td><?= htmlspecialchars($resina['marca']) ?></td>
              <td>
                <?php if (!empty($resina['cor'])): ?>
                  <i class="fas fa-circle nav-icon" style="color:<?= htmlspecialchars($resina['cor']) ?>; border:1px solid #ddd; border-radius:50%;"></i>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= number_format($resina['preco_litro'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($resina['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=resinas&acao=editar&id=<?= $resina['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-resina" href="?pagina=resinas&acao=excluir&id=<?= $resina['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhuma resina cadastrada.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-resina" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelResina" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelResina">Excluir Resina</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-resina">Tem certeza que deseja excluir esta resina? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-resina" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-resina">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let resinaExcluirId = null;
document.querySelectorAll('.btn-excluir-resina').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    resinaExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-resina').classList.add('d-none');
    $('#modal-danger-excluir-resina').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-resina').addEventListener('click', function() {
  if (resinaExcluirId) {
    fetch('modules/resinas/excluir.php?id=' + encodeURIComponent(resinaExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=resinas';
      } else {
        document.getElementById('modal-excluir-erro-resina').textContent = result;
        document.getElementById('modal-excluir-erro-resina').classList.remove('d-none');
      }
    });
  }
});
</script>