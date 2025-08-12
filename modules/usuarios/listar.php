<?php
require_once __DIR__ . '/../../app/db.php';

// Apenas admins podem acessar
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    header('Location: /404.php');
    exit;
}

// Excluir usuário via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $id = $_POST['excluir_id'];
    // Busca o uuid do usuário
    $stmt = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $uuid = $stmt->fetchColumn();

    if ($uuid) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        // Exclui a pasta de imagens do usuário
        $dir = __DIR__ . "/../../uploads/usuarios/$uuid";
        if (is_dir($dir)) {
            function excluirPasta($pasta) {
                $arquivos = array_diff(scandir($pasta), ['.', '..']);
                foreach ($arquivos as $arquivo) {
                    $caminho = "$pasta/$arquivo";
                    if (is_dir($caminho)) {
                        excluirPasta($caminho);
                    } else {
                        unlink($caminho);
                    }
                }
                rmdir($pasta);
            }
            excluirPasta($dir);
        }
        echo 'ok';
        exit;
    }
    echo 'erro';
    exit;
}

// Busca todos os usuários
$stmt = $pdo->query("SELECT id, nome, sobrenome, email, cargo, celular, cpf, foto, data_expiracao FROM usuarios ORDER BY nome, sobrenome");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Usuários</h3>
    <div class="card-tools">
      <a href="?pagina=usuarios&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar usuário
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover text-nowrap">
      <thead>
        <tr>
          <th>ID</th>
          <th>Foto</th>
          <th>Nome</th>
          <th>Email</th>
          <th>Cargo</th>
          <th>Celular</th>
          <th>CPF</th>
          <th>Expira em</th> <!-- Nova coluna -->
          <th class="text-right">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($usuarios): ?>
          <?php foreach ($usuarios as $usuario): ?>
            <tr>
              <td><?= htmlspecialchars($usuario['id']) ?></td>
              <td>
                <?php if (!empty($usuario['foto'])): ?>
                  <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto" style="width:32px;height:32px;border-radius:50%;">
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($usuario['nome'] . ' ' . $usuario['sobrenome']) ?></td>
              <td><?= htmlspecialchars($usuario['email']) ?></td>
              <td><?= htmlspecialchars($usuario['cargo']) ?></td>
              <td><?= htmlspecialchars($usuario['celular']) ?></td>
              <td><?= htmlspecialchars($usuario['cpf']) ?></td>
              <td>
                <?php
                  if (!empty($usuario['data_expiracao'])) {
                    echo date('d/m/Y', strtotime($usuario['data_expiracao']));
                  } else {
                    echo '<span class="text-muted">-</span>';
                  }
                ?>
              </td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=usuarios&acao=editar&id=<?= $usuario['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <button type="button" class="btn btn-danger btn-sm btn-excluir-usuario" data-id="<?= $usuario['id'] ?>" data-nome="<?= htmlspecialchars($usuario['nome'] . ' ' . $usuario['sobrenome']) ?>">
                  <i class="fas fa-trash"></i> Excluir
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center">Nenhum usuário cadastrado.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Danger Modal -->
<div class="modal fade" id="modal-danger-excluir" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabel">Excluir Usuário</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto">Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita e todas as imagens do usuário serão removidas.</p>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let usuarioExcluirId = null;
let usuarioExcluirNome = '';
document.querySelectorAll('.btn-excluir-usuario').forEach(function(btn) {
  btn.addEventListener('click', function() {
    usuarioExcluirId = this.getAttribute('data-id');
    usuarioExcluirNome = this.getAttribute('data-nome');
    document.getElementById('modal-excluir-texto').innerHTML =
      'Tem certeza que deseja excluir o usuário <b>' + usuarioExcluirNome + '</b>?<br>Esta ação não pode ser desfeita e todas as imagens do usuário serão removidas.';
    $('#modal-danger-excluir').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir').addEventListener('click', function() {
  if (usuarioExcluirId) {
    fetch('modules/usuarios/excluir.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id=' + encodeURIComponent(usuarioExcluirId)
    })
    .then(response => response.text())
    .then(result => {
      $('#modal-danger-excluir').modal('hide');
      if (result.trim() === 'ok') {
        location.reload();
      } else {
        alert('Erro ao excluir usuário!');
      }
    });
  }
});
</script>
<!-- /.card -->