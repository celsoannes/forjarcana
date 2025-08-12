<?php
// filepath: /var/www/html/forjarcana/modules/filamentos/listar.php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca os filamentos do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$filamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Filamentos</h3>
    <div class="card-tools">
      <a href="?pagina=filamentos&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar filamento
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($filamentos): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Cor</th>
            <th>Tipo</th>
            <th>Preço/Kg (R$)</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filamentos as $filamento): ?>
            <tr>
              <td><?= htmlspecialchars($filamento['nome']) ?></td>
              <td><?= htmlspecialchars($filamento['marca']) ?></td>
              <td>
                <?php if (!empty($filamento['cor'])): ?>
                  <i class="fas fa-circle nav-icon" style="color:<?= htmlspecialchars($filamento['cor']) ?>;"></i>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($filamento['tipo']) ?></td>
              <td><?= number_format($filamento['preco_kilo'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($filamento['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=filamentos&acao=editar&id=<?= $filamento['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-filamento" href="?pagina=filamentos&acao=excluir&id=<?= $filamento['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhum filamento cadastrado.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-filamento" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelFilamento" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelFilamento">Excluir Filamento</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-filamento">Tem certeza que deseja excluir este filamento? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-filamento" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-filamento">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let filamentoExcluirId = null;
document.querySelectorAll('.btn-excluir-filamento').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    filamentoExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-filamento').classList.add('d-none');
    $('#modal-danger-excluir-filamento').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-filamento').addEventListener('click', function() {
  if (filamentoExcluirId) {
    fetch('modules/filamentos/excluir.php?id=' + encodeURIComponent(filamentoExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=filamentos';
      } else {
        document.getElementById('modal-excluir-erro-filamento').textContent = result;
        document.getElementById('modal-excluir-erro-filamento').classList.remove('d-none');
      }
    });
  }
});
</script>
