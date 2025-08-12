<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';

// Apenas admins podem acessar
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito!</div>';
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $celular = trim($_POST['celular'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $data_expiracao = $_POST['data_expiracao'] ?? '';
    $foto_nome = '';

    // Validação dos campos obrigatórios
    if (
        !$nome ||
        !$sobrenome ||
        !$email ||
        !$senha ||
        !$cargo ||
        !$celular ||
        !$cpf ||
        !$data_expiracao
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        $data_expiracao = DateTime::createFromFormat('d/m/Y', $data_expiracao);
        if ($data_expiracao) {
            $data_expiracao = $data_expiracao->format('Y-m-d');
        } else {
            $erro = 'Data de expiração inválida.';
        }

        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, sobrenome, email, senha, cargo, celular, cpf, data_expiracao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $sobrenome, $email, $hash, $cargo, $celular, $cpf, $data_expiracao]);

            // Recupera o uuid do usuário recém-criado
            $usuario_id = $pdo->lastInsertId();
            $stmtUuid = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ?");
            $stmtUuid->execute([$usuario_id]);
            $uuid = $stmtUuid->fetchColumn();

            $dir = __DIR__ . "/../../uploads/usuarios/$uuid";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Processa o upload da foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // Antes de processar, sanitize o nome do arquivo
                $_FILES['foto']['name'] = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_FILES['foto']['name']);
                
                $foto_nome = uploadImagem($_FILES['foto'], $uuid, 'usuarios');
                if (!$foto_nome) {
                    $erro = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
                }
            }

            // Atualiza o campo foto no banco
            if ($foto_nome) {
                $stmtFoto = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                $stmtFoto->execute([$foto_nome, $usuario_id]);
                error_log('Foto salva no banco: ' . $foto_nome);
            }

            echo '<script>window.location.href="?pagina=usuarios";</script>';
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = 'Já existe um usuário com este e-mail ou CPF.';
            } else {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Usuário</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="formUsuario">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required>
      </div>
      <div class="form-group">
        <label for="sobrenome">Sobrenome</label>
        <input type="text" class="form-control" id="sobrenome" name="sobrenome" required>
      </div>
      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" class="form-control" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="senha">Senha</label>
        <input type="password" class="form-control" id="senha" name="senha" required>
      </div>
      <div class="form-group">
        <label for="cargo">Cargo</label>
        <select class="form-control" id="cargo" name="cargo" required>
          <option value="">Selecione</option>
          <option value="user">Usuário</option>
          <option value="admin">Administrador</option>
        </select>
      </div>
      <!-- Campo celular -->
      <div class="form-group">
        <label for="celular">Celular</label>
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-phone"></i></span>
          </div>
          <input type="text" class="form-control" id="celular" name="celular" required data-inputmask='"mask": "(99) 99999-9999"' data-mask>
        </div>
      </div>
      <div class="form-group">
        <label for="cpf">CPF</label>
        <input type="text" class="form-control" id="cpf" name="cpf" required>
      </div>
      <div class="form-group">
        <label for="data_expiracao">Data de Expiração</label>
        <div class="input-group date" id="dataExpiracaoPicker" data-target-input="nearest">
          <div class="input-group-prepend" data-target="#dataExpiracaoPicker" data-toggle="datetimepicker">
            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
          </div>
          <input type="text" class="form-control datetimepicker-input" data-target="#dataExpiracaoPicker" id="data_expiracao" name="data_expiracao" required />
        </div>
      </div>
      <div class="form-group">
        <label for="customFile">Foto (PNG, JPG, WEBP ou GIF)</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="customFile" name="foto" accept="image/png,image/jpeg,image/webp,image/gif">
          <label class="custom-file-label" for="customFile">Selecione uma foto</label>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=usuarios" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<!-- /.card -->

<!-- Warning Modal -->
<div class="modal fade" id="modal-warning" tabindex="-1" role="dialog" aria-labelledby="modalWarningLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-warning">
      <div class="modal-header">
        <h4 class="modal-title" id="modalWarningLabel">Atenção</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-warning-texto"></p>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(function () {
    $('[data-mask]').inputmask();
  });
</script>
<script>
$(function () {
  $('#dataExpiracaoPicker').datetimepicker({
    format: 'DD/MM/YYYY'
  });

  // Ao clicar em qualquer parte do grupo, foca o input e abre o datepicker
  $('#dataExpiracaoPicker').on('click', function(e) {
    $(this).find('input').focus();
    $(this).data('datetimepicker').show();
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('formUsuario');
  var inputFoto = document.getElementById('customFile'); // <-- ajuste aqui!
  var modalWarning = $('#modal-warning');
  var modalWarningTexto = document.getElementById('modal-warning-texto');

  form.addEventListener('submit', function(e) {
    if (inputFoto.files.length > 0) {
      var file = inputFoto.files[0];
      var ext = file.name.split('.').pop().toLowerCase();
      var tiposPermitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
      if (tiposPermitidos.indexOf(ext) === -1) {
        e.preventDefault();
        modalWarningTexto.innerText = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
        modalWarning.modal('show');
        return false;
      }
    }
  });
});
</script>
<script>
$(function () {
  bsCustomFileInput.init();
});
</script>
<link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">