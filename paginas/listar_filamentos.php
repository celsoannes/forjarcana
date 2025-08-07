<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra os filamentos do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar seus filamentos.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$filamentos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM filamento WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $filamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao buscar filamentos.</div>';
}
?>

<h2 class="mb-4">Filamentos</h2>
<a href="?pagina=filamentos&acao=adicionar" class="btn btn-adicionar mb-3">Adicionar Filamento</a>

<?php if (!empty($filamentos)): ?>
<table class="custom-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Cor</th>
            <th>Tipo</th>
            <th>Preço por quilo (R$)</th>
            <th>Última atualização</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($filamentos as $filamento): ?>
            <tr>
                <td><?= htmlspecialchars($filamento['nome']) ?></td>
                <td><?= htmlspecialchars($filamento['marca']) ?></td>
                <td>
                    <span style="display:inline-block;width:24px;height:24px;background:<?= htmlspecialchars($filamento['cor']) ?>;border-radius:4px;border:1px solid #ccc;" title="<?= htmlspecialchars($filamento['cor']) ?>"></span>
                </td>
                <td><?= htmlspecialchars($filamento['tipo']) ?></td>
                <td><?= number_format($filamento['preco_kilo'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($filamento['ultima_atualizacao']) ?></td>
                <td>
                    <a href="?pagina=filamentos&acao=editar&id=<?= $filamento['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    <a href="#"
                       class="btn btn-sm btn-excluir"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirFilamento"
                       data-id="<?= $filamento['id'] ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="alert alert-info text-center">Nenhum filamento cadastrado.</div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirFilamento" tabindex="-1" aria-labelledby="modalExcluirFilamentoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirFilamentoLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir este filamento?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirFilamento" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalExcluir = document.getElementById('modalExcluirFilamento');
  modalExcluir.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var btnConfirmar = document.getElementById('btnConfirmarExcluirFilamento');
    btnConfirmar.href = '?pagina=excluir_filamento&id=' + id;
  });
});
</script>