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