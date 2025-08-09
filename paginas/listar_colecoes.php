<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar suas coleções.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$colecoes = [];
try {
    $stmt = $pdo->prepare(
        "SELECT c.*, e.nome AS estudio_nome 
         FROM colecoes c 
         LEFT JOIN estudios e ON c.estudio_id = e.id 
         WHERE c.usuario_id = ? 
         ORDER BY c.id DESC"
    );
    $stmt->execute([$usuario_id]);
    $colecoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao buscar coleções.</div>';
}
?>

<h2 class="mb-4">Coleções</h2>
<a href="?pagina=colecoes&acao=adicionar" class="btn btn-adicionar mb-3">Adicionar Coleção</a>

<?php if (!empty($colecoes)): ?>
<table class="custom-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Estudio</th>
            <th>Última atualização</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($colecoes as $colecao): ?>
            <tr>
                <td><?= htmlspecialchars($colecao['nome']) ?></td>
                <td><?= htmlspecialchars($colecao['estudio_nome']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($colecao['ultima_atualizacao'])) ?></td>
                <td>
                    <a href="?pagina=colecoes&acao=editar&id=<?= $colecao['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    <a href="#"
                       class="btn btn-sm btn-excluir"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirColecao"
                       data-id="<?= $colecao['id'] ?>"
                       data-nome="<?= htmlspecialchars($colecao['nome']) ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="alert alert-info text-center">Nenhuma coleção cadastrada.</div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirColecao" tabindex="-1" aria-labelledby="modalExcluirColecaoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirColecaoLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir a coleção <strong id="nomeColecaoModal"></strong>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-voltar" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirColecao" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalExcluirColecao');
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var colecaoId = button.getAttribute('data-id');
        var colecaoNome = button.getAttribute('data-nome');
        document.getElementById('nomeColecaoModal').textContent = colecaoNome;
        document.getElementById('btnConfirmarExcluirColecao').href = '?pagina=colecoes&acao=excluir&id=' + colecaoId;
    });
});
</script>