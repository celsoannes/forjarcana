<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem visualizar esta página.</div>';
    return;
}

$stmt = $pdo->query("SELECT id, nome, email, cargo, data_expiracao FROM usuarios ORDER BY nome");
$usuarios = $stmt->fetchAll();
?>
<h2 class="mb-4">Usuários do Sistema</h2>
<a href="?pagina=adicionar_usuario" class="btn btn-adicionar mb-3">Adicionar Usuário</a>
<table class="custom-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Cargo</th>
            <th>Expiração</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= $usuario['id'] ?></td>
                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                <td><?= htmlspecialchars($usuario['email']) ?></td>
                <td><?= $usuario['cargo'] ?></td>
                <td><?= $usuario['data_expiracao'] ? date('d/m/Y H:i', strtotime($usuario['data_expiracao'])) : '-' ?></td>
                <td>
                    <a href="?pagina=editar_usuario&id=<?= $usuario['id'] ?>" class="btn btn-sm btn-editar">Editar</a>
                    <a href="#"
                       class="btn btn-sm btn-excluir"
                       data-bs-toggle="modal"
                       data-bs-target="#modalExcluirUsuario"
                       data-id="<?= $usuario['id'] ?>">
                       Excluir
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluirUsuario" tabindex="-1" aria-labelledby="modalExcluirUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExcluirUsuarioLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir este usuário?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" id="btnConfirmarExcluirUsuario" class="btn btn-excluir">Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var modalExcluir = document.getElementById('modalExcluirUsuario');
  modalExcluir.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var btnConfirmar = document.getElementById('btnConfirmarExcluirUsuario');
    btnConfirmar.href = '?pagina=excluir_usuario&id=' + id;
  });
});
</script>