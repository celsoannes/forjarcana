<?php
require __DIR__ . '/../app/db.php';

// Garante que só mostra os insumos do usuário logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para visualizar seus insumos.</div>';
    return;
}

$usuario_id = $_SESSION['usuario_id'];
$insumos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM insumos WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao buscar insumos.</div>';
}
?>

<h2 class="mb-4">Insumos</h2>
<a href="?pagina=insumos&acao=adicionar" class="btn btn-success mb-3">Adicionar Insumo</a>

<?php if (!empty($insumos)): ?>
<table class="custom-table">
    <thead>
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Descrição</th>
            <th>Unidade</th>
            <th>Valor unitário (R$)</th>
            <th>Fornecedor</th>
            <th>Última atualização</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($insumos as $insumo): ?>
            <tr>
                <td>
                    <?php
                    if (!empty($insumo['imagem'])) {
                        // Exibe a thumbnail
                        $thumb = str_replace('_m.png', '_t.png', $insumo['imagem']);
                        ?>
                        <img src="/forjarcana/uploads/<?= htmlspecialchars($thumb) ?>" alt="Imagem" style="max-width:48px;max-height:48px;border-radius:4px;">
                    <?php } else { ?>
                        <span class="text-muted">Sem imagem</span>
                    <?php } ?>
                </td>
                <td><?= htmlspecialchars($insumo['nome_material']) ?></td>
                <td><?= htmlspecialchars($insumo['tipo_material']) ?></td>
                <td><?= htmlspecialchars($insumo['descricao']) ?></td>
                <td><?= htmlspecialchars($insumo['unidade_medida']) ?></td>
                <td><?= number_format($insumo['valor_unitario'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($insumo['fornecedor']) ?></td>
                <td><?= htmlspecialchars($insumo['ultima_atualizacao']) ?></td>
                <td>
                    <a href="?pagina=insumos&acao=editar&id=<?= $insumo['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    <a href="#"
                       class="btn btn-sm btn-excluir"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirInsumo"
                       data-id="<?= $insumo['id'] ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="alert alert-info text-center">Nenhum insumo cadastrado.</div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirInsumo" tabindex="-1" aria-labelledby="modalExcluirInsumoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirInsumoLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir este insumo?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-voltar" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirInsumo" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalExcluir = document.getElementById('modalExcluirInsumo');
  modalExcluir.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var btnConfirmar = document.getElementById('btnConfirmarExcluirInsumo');
    btnConfirmar.href = '?pagina=excluir_insumo&id=' + id;
  });
});
</script>