<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca as impressões do usuário autenticado
$stmt = $pdo->prepare("SELECT i.*, e.nome AS estudio_nome, c.nome AS colecao_nome, imp.marca AS impressora_marca, imp.modelo AS impressora_modelo
    FROM impressoes i
    LEFT JOIN estudios e ON i.estudio_id = e.id
    LEFT JOIN colecoes c ON i.colecao_id = c.id
    LEFT JOIN impressoras imp ON i.impressora_id = imp.id
    WHERE i.usuario_id = ?");
$stmt->execute([$usuario_id]);
$impressoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Impressões</h3>
    <div class="card-tools">
      <a href="?pagina=impressoes&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar impressão
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($impressoes): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Estudio</th>
            <th>Coleção</th>
            <th>Impressora</th>
            <th>Tempo Impressão (min)</th>
            <th>Unidades</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($impressoes as $imp): ?>
            <tr>
              <td><?= htmlspecialchars($imp['nome']) ?></td>
              <td><?= htmlspecialchars($imp['estudio_nome'] ?? '-') ?></td>
              <td><?= htmlspecialchars($imp['colecao_nome'] ?? '-') ?></td>
              <td>
                <?php if (!empty($imp['impressora_marca']) || !empty($imp['impressora_modelo'])): ?>
                  <?= htmlspecialchars($imp['impressora_marca'] . ' ' . $imp['impressora_modelo']) ?>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($imp['tempo_impressao']) ?></td>
              <td><?= htmlspecialchars($imp['unidades_produzidas']) ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($imp['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=impressoes&acao=editar&id=<?= $imp['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-impressao" href="?pagina=impressoes&acao=excluir&id=<?= $imp['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhuma impressão cadastrada.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modal-danger-excluir-impressao" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelImpressao" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelImpressao">Excluir Impressão</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-impressao">Tem certeza que deseja excluir esta impressão? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-impressao" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-impressao">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let impressaoExcluirId = null;
document.querySelectorAll('.btn-excluir-impressao').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    impressaoExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-impressao').classList.add('d-none');
    $('#modal-danger-excluir-impressao').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-impressao').addEventListener('click', function() {
  if (impressaoExcluirId) {
    fetch('modules/impressoes/excluir.php?id=' + encodeURIComponent(impressaoExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=impressoes';
      } else {
        document.getElementById('modal-excluir-erro-impressao').textContent = result;
        document.getElementById('modal-excluir-erro-impressao').classList.remove('d-none');
      }
    });
  }
});
</script>