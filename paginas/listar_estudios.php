<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar seus estudios.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$estudios = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM estudios WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao buscar estudios.</div>';
}
?>

<h2 class="mb-4">Estudios</h2>
<a href="?pagina=estudios&acao=adicionar" class="btn btn-success mb-3">Adicionar Estudio</a>

<?php if (!empty($estudios)): ?>
<table class="custom-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Site</th>
            <th>Última atualização</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($estudios as $estudio): ?>
            <tr>
                <td><?= htmlspecialchars($estudio['nome']) ?></td>
                <td>
                    <a href="<?= htmlspecialchars($estudio['site']) ?>" target="_blank">
                        <?= htmlspecialchars($estudio['site']) ?>
                    </a>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($estudio['ultima_atualizacao'])) ?></td>
                <td>
                    <a href="?pagina=estudios&acao=editar&id=<?= $estudio['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    <a href="#"
                       class="btn btn-sm btn-excluir"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirEstudio"
                       data-id="<?= $estudio['id'] ?>"
                       data-nome="<?= htmlspecialchars($estudio['nome']) ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="alert alert-info text-center">Nenhum estudio cadastrado.</div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirEstudio" tabindex="-1" aria-labelledby="modalExcluirEstudioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirEstudioLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir o estudio <strong id="nomeEstudioModal"></strong>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-voltar" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirEstudio" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalExcluirEstudio');
    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var estudioId = button.getAttribute('data-id');
        var estudioNome = button.getAttribute('data-nome');
        document.getElementById('nomeEstudioModal').textContent = estudioNome;
        document.getElementById('btnConfirmarExcluirEstudio').href = '?pagina=estudios&acao=excluir&id=' + estudioId;
    });
});
</script>