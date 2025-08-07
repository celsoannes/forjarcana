<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra as resinas do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar suas resinas.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$resinas = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM resinas WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $resinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao buscar resinas.</div>';
}
?>

<h2 class="mb-4">Resinas</h2>
<a href="?pagina=adicionar_resina" class="btn btn-success mb-3">Adicionar Resina</a>

<table class="custom-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Cor</th>
            <th>Preço por litro (R$)</th>
            <th>Última atualização</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resinas as $resina): ?>
            <tr>
                <td><?= htmlspecialchars($resina['nome']) ?></td>
                <td><?= htmlspecialchars($resina['marca']) ?></td>
                <td>
                    <span style="display:inline-block;width:24px;height:24px;background:<?= htmlspecialchars($resina['cor']) ?>;border-radius:4px;border:1px solid #ccc;" title="<?= htmlspecialchars($resina['cor']) ?>"></span>
                </td>
                <td><?= number_format($resina['preco_litro'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($resina['ultima_atualizacao']) ?></td>
                <td>
                    <a href="?pagina=editar_resina&id=<?= $resina['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="?pagina=excluir_resina&id=<?= $resina['id'] ?>"
                       class="btn btn-sm btn-danger"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirResina"
                       data-id="<?= $resina['id'] ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($resinas)): ?>
            <tr>
                <td colspan="6" class="text-center">Nenhuma resina cadastrada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirResina" tabindex="-1" aria-labelledby="modalExcluirResinaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirResinaLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir esta resina?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluir" class="btn btn-danger">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalExcluir = document.getElementById('modalExcluirResina');
  modalExcluir.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var btnConfirmar = document.getElementById('btnConfirmarExcluir');
    btnConfirmar.href = '?pagina=excluir_resina&id=' + id;
  });
});
</script>

