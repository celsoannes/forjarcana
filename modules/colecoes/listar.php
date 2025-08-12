<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca as coleções do usuário autenticado junto com o nome do estudio associado
$stmt = $pdo->prepare("
    SELECT c.*, e.nome AS estudio_nome
    FROM colecoes c
    LEFT JOIN estudios e ON c.estudio_id = e.id
    WHERE c.usuario_id = ?
");
$stmt->execute([$usuario_id]);
$colecoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Coleções</h3>
    <div class="card-tools">
      <a href="?pagina=colecoes&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar coleção
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($colecoes): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Estudio</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($colecoes as $colecao): ?>
            <tr>
              <td><?= htmlspecialchars($colecao['nome']) ?></td>
              <td>
                <?= !empty($colecao['estudio_nome']) ? htmlspecialchars($colecao['estudio_nome']) : '<span class="text-muted">-</span>' ?>
              </td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($colecao['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=colecoes&acao=editar&id=<?= $colecao['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-colecao" href="?pagina=colecoes&acao=excluir&id=<?= $colecao['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhuma coleção cadastrada.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-colecao" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelColecao" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelColecao">Excluir Coleção</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-colecao">Tem certeza que deseja excluir esta coleção? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-colecao" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-colecao">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let colecaoExcluirId = null;
document.querySelectorAll('.btn-excluir-colecao').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    colecaoExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-colecao').classList.add('d-none');
    $('#modal-danger-excluir-colecao').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-colecao').addEventListener('click', function() {
  if (colecaoExcluirId) {
    fetch('modules/colecoes/excluir.php?id=' + encodeURIComponent(colecaoExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=colecoes';
      } else {
        document.getElementById('modal-excluir-erro-colecao').textContent = result;
        document.getElementById('modal-excluir-erro-colecao').classList.remove('d-none');
      }
    });
  }
});
</script>