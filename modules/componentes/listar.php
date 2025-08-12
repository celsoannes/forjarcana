<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca os componentes do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM componentes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$componentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Componentes</h3>
    <div class="card-tools">
      <a href="?pagina=componentes&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar componente
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($componentes): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Descrição</th>
            <th>Unidade</th>
            <th>Valor Unitário (R$)</th>
            <th>Fornecedor</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($componentes as $componente): ?>
            <tr>
              <td>
                <?php
                  if (!empty($componente['imagem'])) {
                    $thumb = str_replace('_media.png', '_thumb.png', $componente['imagem']);
                    echo '<img src="' . htmlspecialchars($thumb) . '" alt="Thumb" style="width:32px;height:32px;border-radius:4px;">';
                  } else {
                    echo '<span class="text-muted">-</span>';
                  }
                ?>
              </td>
              <td><?= htmlspecialchars($componente['nome_material']) ?></td>
              <td><?= htmlspecialchars($componente['tipo_material']) ?></td>
              <td><?= htmlspecialchars($componente['descricao']) ?></td>
              <td><?= htmlspecialchars($componente['unidade_medida']) ?></td>
              <td><?= number_format($componente['valor_unitario'], 2, ',', '.') ?></td>
              <td><?= htmlspecialchars($componente['fornecedor']) ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($componente['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=componentes&acao=editar&id=<?= $componente['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-componente" href="?pagina=componentes&acao=excluir&id=<?= $componente['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhum componente cadastrado.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Adicione este modal ao final do arquivo listar.php dos componentes -->
<div class="modal fade" id="modal-danger-excluir-componente" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelComponente" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelComponente">Excluir Componente</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-componente">Tem certeza que deseja excluir este componente? Esta ação não pode ser desfeita e todas as imagens associadas serão removidas.</p>
        <div id="modal-excluir-erro-componente" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-componente">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let componenteExcluirId = null;
document.querySelectorAll('.btn-excluir-componente').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    componenteExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-componente').classList.add('d-none');
    $('#modal-danger-excluir-componente').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-componente').addEventListener('click', function() {
  if (componenteExcluirId) {
    fetch('modules/componentes/excluir.php?id=' + encodeURIComponent(componenteExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=componentes';
      } else {
        document.getElementById('modal-excluir-erro-componente').textContent = result;
        document.getElementById('modal-excluir-erro-componente').classList.remove('d-none');
      }
    });
  }
});
</script>