<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra impressoras do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar suas impressoras.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario_id]);
$impressoras = $stmt->fetchAll();
?>

<h2 class="mb-4">Impressoras</h2>
<a href="?pagina=impressoras&acao=adicionar" class="btn btn-adicionar mb-3">Adicionar Impressora</a>
<?php if (empty($impressoras)): ?>
    <div class="alert alert-info text-center">Nenhuma impressora cadastrada.</div>
<?php else: ?>
    <table class="custom-table">
        <thead>
            <tr>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Tipo</th>
                <th>Preço de aquisição (R$)</th>
                <th>Potência (W)</th>
                <th>Depreciação (%)</th>
                <th>Tempo de vida útil (horas)</th>
                <th>Custo hora (R$)</th>
                <th>Última atualização</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($impressoras as $imp): ?>
                <tr>
                    <td><?= htmlspecialchars($imp['marca']) ?></td>
                    <td><?= htmlspecialchars($imp['modelo']) ?></td>
                    <td><?= $imp['tipo'] ?></td>
                    <td><?= number_format($imp['preco_aquisicao'], 2, ',', '.') ?></td>
                    <td><?= $imp['consumo'] ?></td>
                    <td><?= $imp['depreciacao'] ?></td>
                    <td><?= $imp['tempo_vida_util'] ?></td>
                    <td><?= number_format($imp['custo_hora'], 4, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($imp['ultima_atualizacao'])) ?></td>
                    <td>
                        <a href="?pagina=impressoras&acao=editar&id=<?= $imp['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                        <a href="#"
                           class="btn btn-sm btn-excluir"
                           data-bs-toggle="modal"
                           data-bs-target="#modalExcluirImpressora"
                           data-id="<?= $imp['id'] ?>">
                           Excluir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirImpressora" tabindex="-1" aria-labelledby="modalExcluirImpressoraLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirImpressoraLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir esta impressora?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirImpressora" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalExcluir = document.getElementById('modalExcluirImpressora');
  modalExcluir.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var btnConfirmar = document.getElementById('btnConfirmarExcluirImpressora');
    btnConfirmar.href = '?pagina=excluir_impressora&id=' + id;
  });
});
</script>