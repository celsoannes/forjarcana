<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Usuarios\UsuarioController;

// Apenas admins podem acessar
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito!</div>';
    exit;
}

$erro = '';
$usuarioController = new UsuarioController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $usuarioController->processarCadastro($_POST, $_FILES);
    if (!empty($resultado['sucesso'])) {
        echo '<script>window.location.href="?pagina=usuarios";</script>';
        exit;
    }

    $erro = (string) ($resultado['erro'] ?? 'Erro ao cadastrar.');
    if (!empty($resultado['foto_nome'])) {
        error_log('Foto salva no banco: ' . (string) $resultado['foto_nome']);
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
      <div class="form-row align-items-stretch">
        <div class="col-md-3 d-flex">
          <div class="form-group h-100 w-100">
            <label for="foto">Foto do Perfil</label>
            <div id="capa-preview-area" class="border rounded bg-light position-relative d-flex align-items-center justify-content-center w-100" style="width: 100%; height: calc(100% - 32px); min-height: 220px; cursor: pointer;" onclick="document.getElementById('foto').click();">
              <img id="preview-capa" src="" alt="Pré-visualização da foto" class="img-fluid d-none" style="width: 100%; aspect-ratio: 1 / 1; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6;">
              <button type="button" id="remove-capa-btn" class="btn btn-danger btn-sm rounded-circle d-none" style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; line-height:26px;" onclick="event.stopPropagation();">&times;</button>
              <div id="capa-placeholder" class="align-items-center justify-content-center text-muted" style="position:absolute; top:0; right:0; bottom:0; left:0; display:flex;">
                Clique para selecionar a foto
              </div>
            </div>
            <input type="file" id="foto" name="foto" accept=".jpg,.png,.webp" style="display:none;">
          </div>
        </div>
        <div class="col-md-9">
          <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>
          <div class="form-group">
            <label for="sobrenome">Sobrenome</label>
            <input type="text" class="form-control" id="sobrenome" name="sobrenome" required>
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
            <label for="email">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="senha">Senha</label>
              <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            <div class="form-group col-md-6">
              <label for="confirmar_senha">Confirmar Senha</label>
              <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
            </div>
          </div>
          <div class="form-group">
            <label for="cargo">Cargo</label>
            <select class="form-control" id="cargo" name="cargo" required>
              <option value="">Selecione</option>
              <option value="user">Usuário</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div class="form-group">
            <label for="cpf">CPF</label>
            <input type="text" class="form-control" id="cpf" name="cpf" required maxlength="14" inputmode="numeric" data-inputmask='"mask": "999.999.999-99"' data-mask>
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
  var inputFoto = document.getElementById('foto');
  var inputEmail = document.getElementById('email');
  var inputSenha = document.getElementById('senha');
  var inputConfirmarSenha = document.getElementById('confirmar_senha');
  var inputCpf = document.getElementById('cpf');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var modalWarning = $('#modal-warning');
  var modalWarningTexto = document.getElementById('modal-warning-texto');

  var aplicarMascaraCpf = function(valor) {
    var apenasNumeros = (valor || '').replace(/\D/g, '').slice(0, 11);
    if (apenasNumeros.length <= 3) return apenasNumeros;
    if (apenasNumeros.length <= 6) return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3);
    if (apenasNumeros.length <= 9) return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3, 6) + '.' + apenasNumeros.slice(6);
    return apenasNumeros.slice(0, 3) + '.' + apenasNumeros.slice(3, 6) + '.' + apenasNumeros.slice(6, 9) + '-' + apenasNumeros.slice(9);
  };

  var cpfEhValido = function(valor) {
    var cpf = (valor || '').replace(/\D/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
      return false;
    }

    for (var t = 9; t < 11; t++) {
      var soma = 0;
      for (var i = 0; i < t; i++) {
        soma += parseInt(cpf.charAt(i), 10) * ((t + 1) - i);
      }
      var digito = ((10 * soma) % 11) % 10;
      if (parseInt(cpf.charAt(t), 10) !== digito) {
        return false;
      }
    }

    return true;
  };

  var emailEhValido = function(valor) {
    var email = (valor || '').trim().toLowerCase();
    if (email === '' || email.length > 150) {
      return false;
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  };

  var renderizarSemImagem = function() {
    if (!previewImagem || !capaPlaceholder || !removeCapaBtn) {
      return;
    }
    previewImagem.src = '';
    previewImagem.classList.add('d-none');
    capaPlaceholder.classList.remove('d-none');
    capaPlaceholder.style.display = 'flex';
    removeCapaBtn.classList.add('d-none');
  };

  renderizarSemImagem();

  if (inputFoto && previewImagem && capaPlaceholder && removeCapaBtn) {
    inputFoto.addEventListener('change', function () {
      var arquivo = this.files && this.files[0] ? this.files[0] : null;

      if (!arquivo) {
        renderizarSemImagem();
        return;
      }

      if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
        renderizarSemImagem();
        return;
      }

      var leitor = new FileReader();
      leitor.onload = function (evento) {
        previewImagem.src = evento.target.result;
        previewImagem.classList.remove('d-none');
        capaPlaceholder.classList.add('d-none');
        capaPlaceholder.style.display = 'none';
        removeCapaBtn.classList.remove('d-none');
      };
      leitor.readAsDataURL(arquivo);
    });

    removeCapaBtn.addEventListener('click', function () {
      inputFoto.value = '';
      renderizarSemImagem();
    });
  }

  if (inputCpf) {
    inputCpf.addEventListener('input', function () {
      this.value = aplicarMascaraCpf(this.value);
    });

    inputCpf.value = aplicarMascaraCpf(inputCpf.value);
  }

  if (inputEmail) {
    inputEmail.addEventListener('input', function () {
      this.value = (this.value || '').replace(/\s+/g, '').toLowerCase();
    });
  }

  form.addEventListener('submit', function(e) {
    if (inputSenha && inputConfirmarSenha && inputSenha.value !== inputConfirmarSenha.value) {
      e.preventDefault();
      modalWarningTexto.innerText = 'Senha e confirmação de senha não conferem.';
      modalWarning.modal('show');
      inputConfirmarSenha.focus();
      return false;
    }

    if (inputEmail && !emailEhValido(inputEmail.value)) {
      e.preventDefault();
      modalWarningTexto.innerText = 'Informe um e-mail válido.';
      modalWarning.modal('show');
      inputEmail.focus();
      return false;
    }

    if (inputCpf && !cpfEhValido(inputCpf.value)) {
      e.preventDefault();
      modalWarningTexto.innerText = 'Informe um CPF válido.';
      modalWarning.modal('show');
      inputCpf.focus();
      return false;
    }

    if (inputFoto && inputFoto.files.length > 0) {
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