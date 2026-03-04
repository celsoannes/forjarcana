<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';
require_once __DIR__ . '/../../app/validacoes_documentos.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca dados do usuário logado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo '<div class="alert alert-danger">Usuário não encontrado.</div>';
    exit;
}

$alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_tipo = $_POST['form_tipo'] ?? '';

    if ($form_tipo === 'perfil') {
        // Atualização do perfil
        $nome = trim($_POST['nome']);
        $sobrenome = trim($_POST['sobrenome']);
        $email = trim($_POST['email']);
        $celular = trim($_POST['celular']);
        $cpf = trim($_POST['cpf']);
        $erroDocumento = '';
        $erroUploadFoto = '';
        $fotoExistente = trim((string) ($_POST['foto_existente'] ?? ''));
        $foto_nome = $fotoExistente !== '' ? $fotoExistente : null;

        if ($cpf === '' || !validarCpf($cpf)) {
            $erroDocumento = 'Informe um CPF válido.';
        } elseif (!validarEmail($email, false, 150)) {
            $erroDocumento = 'Informe um e-mail válido.';
        }

        // Upload da foto se enviada
        if ($erroDocumento === '' && isset($_FILES['foto']) && (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $codigoErroUpload = (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($codigoErroUpload !== UPLOAD_ERR_OK) {
                if ($codigoErroUpload === UPLOAD_ERR_INI_SIZE || $codigoErroUpload === UPLOAD_ERR_FORM_SIZE) {
                    $limiteUpload = (string) ini_get('upload_max_filesize');
                    $limitePost = (string) ini_get('post_max_size');
                    $erroUploadFoto = 'Falha no upload da foto: o servidor recusou o arquivo por limite de tamanho (upload_max_filesize=' . htmlspecialchars($limiteUpload) . ', post_max_size=' . htmlspecialchars($limitePost) . ').';
                } elseif ($codigoErroUpload === UPLOAD_ERR_PARTIAL) {
                    $erroUploadFoto = 'Falha no upload da foto: o envio foi interrompido antes de concluir.';
                } elseif ($codigoErroUpload === UPLOAD_ERR_NO_TMP_DIR) {
                    $erroUploadFoto = 'Falha no upload da foto: diretório temporário do servidor não está configurado.';
                } elseif ($codigoErroUpload === UPLOAD_ERR_CANT_WRITE) {
                    $erroUploadFoto = 'Falha no upload da foto: não foi possível gravar o arquivo no servidor.';
                } elseif ($codigoErroUpload === UPLOAD_ERR_EXTENSION) {
                    $erroUploadFoto = 'Falha no upload da foto: uma extensão do PHP bloqueou o envio.';
                } else {
                    $erroUploadFoto = 'Falha no upload da foto. Verifique o arquivo (máx. 5MB).';
                }
            } else {
                // Recupera o uuid do usuário
                $stmtUuid = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ?");
                $stmtUuid->execute([$usuario_id]);
                $uuid = (string) ($stmtUuid->fetchColumn() ?: '');

                if ($uuid === '') {
                    $erroUploadFoto = 'Não foi possível identificar o usuário para salvar a foto.';
                } else {
                    $fotoUpload = uploadImagem($_FILES['foto'], $uuid, 'usuarios', null, 'foto', true);
                    if ($fotoUpload !== false) {
                        $foto_nome = $fotoUpload;
                    } else {
                        $erroUploadFoto = 'Não foi possível salvar a foto. Verifique formato permitido e limite de 5MB.';
                    }
                }
            }
        }

        if ($erroDocumento !== '') {
            $alerta = '<div class="alert alert-danger">' . htmlspecialchars($erroDocumento) . '</div>';
        } elseif ($erroUploadFoto !== '') {
            $alerta = '<div class="alert alert-danger">' . htmlspecialchars($erroUploadFoto) . '</div>';
        }

        if ($erroDocumento === '' && $erroUploadFoto === '') {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, celular = ?, cpf = ?, foto = ? WHERE id = ?");
            $stmt->execute([$nome, $sobrenome, $email, $celular, $cpf, $foto_nome, $usuario_id]);

            $usuario['nome'] = $nome;
            $usuario['sobrenome'] = $sobrenome;
            $usuario['email'] = $email;
            $usuario['celular'] = $celular;
            $usuario['cpf'] = $cpf;
            $usuario['foto'] = $foto_nome;

            // Atualiza nome e sobrenome na sessão
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_sobrenome'] = $sobrenome;
            $_SESSION['usuario_foto'] = $foto_nome ?: '';

            $alerta = '<div class="alert alert-success">Perfil atualizado com sucesso!</div>';
        }
    }

    if ($form_tipo === 'senha') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        // Verifica se a senha atual está correta
        if (!password_verify($senha_atual, $usuario['senha'])) {
            $alerta = '<div class="alert alert-danger">Senha atual incorreta!</div>';
        } elseif ($nova_senha !== $confirma_senha) {
            $alerta = '<div class="alert alert-danger">Nova senha e confirmação não conferem!</div>';
        } else {
            // Atualiza a senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$senha_hash, $usuario_id]);
            $alerta = '<div class="alert alert-success">Senha alterada com sucesso!</div>';
        }
    }
}
?>

<?php if ($alerta): ?>
    <?= $alerta ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Meu Perfil</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_tipo" value="perfil">
            <input type="hidden" id="foto_existente" name="foto_existente" value="<?= htmlspecialchars((string) ($usuario['foto'] ?? '')) ?>">
            <div class="form-row align-items-stretch">
                <!-- Foto do perfil ocupa 1/3 da linha -->
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
                <!-- Nome e Sobrenome ocupam 2/3 da linha -->
                <div class="form-group col-md-9">
                    <label for="nome">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($usuario['nome']) ?>">
                    <label for="sobrenome" class="mt-3">Sobrenome</label>
                    <input type="text" class="form-control" id="sobrenome" name="sobrenome" required value="<?= htmlspecialchars($usuario['sobrenome']) ?>">
                    <div class="form-row mt-3">
                        <div class="form-group col-md-12 mb-0">
                            <label for="celular">Celular</label>
                            <input type="text" class="form-control" id="celular" name="celular" required value="<?= htmlspecialchars($usuario['celular']) ?>">
                        </div>
                    </div>
                    <div class="form-row mt-3">
                        <div class="form-group col-md-12 mb-0">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row mt-3">
                        <div class="form-group col-md-12 mb-0">
                            <label for="cpf">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" required maxlength="14" inputmode="numeric" value="<?= htmlspecialchars($usuario['cpf']) ?>">
                        </div>
                    </div>
                    <div class="form-row mt-3">
                        <div class="form-group col-md-12 mb-0">
                            <label for="data_expiracao">Conta ativa até</label>
                            <input type="text" class="form-control" id="data_expiracao" name="data_expiracao"
                                   value="<?= isset($usuario['data_expiracao']) ? htmlspecialchars(date('d/m/Y', strtotime($usuario['data_expiracao']))) : '' ?>"
                                   readonly>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div class="card-footer">
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Alterar senha</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="?pagina=perfil&acao=alterar_senha">
            <input type="hidden" name="form_tipo" value="senha">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="senha_atual">Senha atual</label>
                    <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="confirma_senha">Confirme a nova senha</label>
                    <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" required>
                </div>
            </div>
    </div>
    <div class="card-footer">
            <button type="submit" class="btn btn-warning">Alterar senha</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var formPerfil = document.querySelector('form[enctype="multipart/form-data"]');
    var inputFoto = document.getElementById('foto');
    var inputEmail = document.getElementById('email');
    var inputCpf = document.getElementById('cpf');
    var previewImagem = document.getElementById('preview-capa');
    var capaPlaceholder = document.getElementById('capa-placeholder');
    var removeCapaBtn = document.getElementById('remove-capa-btn');
    var fotoExistenteInput = document.getElementById('foto_existente');

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

    if (inputEmail) {
        inputEmail.addEventListener('input', function () {
            this.value = (this.value || '').replace(/\s+/g, '').toLowerCase();
            this.setCustomValidity('');
        });
    }

    if (inputCpf) {
        inputCpf.addEventListener('input', function () {
            this.value = aplicarMascaraCpf(this.value);
            this.setCustomValidity('');
        });

        inputCpf.addEventListener('blur', function () {
            this.value = aplicarMascaraCpf(this.value);
            this.setCustomValidity(cpfEhValido(this.value) ? '' : 'Informe um CPF válido.');
        });

        inputCpf.value = aplicarMascaraCpf(inputCpf.value);
    }

    if (formPerfil && inputCpf) {
        formPerfil.addEventListener('submit', function (e) {
            if (inputEmail && !emailEhValido(inputEmail.value)) {
                e.preventDefault();
                inputEmail.setCustomValidity('Informe um e-mail válido.');
                inputEmail.reportValidity();
                inputEmail.focus();
                return;
            }

            inputCpf.value = aplicarMascaraCpf(inputCpf.value);
            if (!cpfEhValido(inputCpf.value)) {
                e.preventDefault();
                inputCpf.setCustomValidity('Informe um CPF válido.');
                inputCpf.reportValidity();
                inputCpf.focus();
            }
        });
    }

    if (!inputFoto || !previewImagem || !capaPlaceholder || !removeCapaBtn || !fotoExistenteInput) {
        return;
    }

    var renderizarCapaExistente = function () {
        var caminhoCapaExistente = typeof fotoExistenteInput.value === 'string'
            ? fotoExistenteInput.value.trim()
            : '';

        if (caminhoCapaExistente !== '') {
            var caminhoCapaGrande = caminhoCapaExistente.replace(/_(thumbnail|pequena|media|thumb)\.(png|webp)$/i, '_grande.$2');
            previewImagem.onerror = function () {
                previewImagem.onerror = null;
                previewImagem.src = caminhoCapaExistente;
            };
            previewImagem.src = caminhoCapaGrande;
            previewImagem.classList.remove('d-none');
            capaPlaceholder.classList.add('d-none');
            capaPlaceholder.style.display = 'none';
            removeCapaBtn.classList.remove('d-none');
        } else {
            previewImagem.src = '';
            previewImagem.classList.add('d-none');
            capaPlaceholder.classList.remove('d-none');
            capaPlaceholder.style.display = 'flex';
            removeCapaBtn.classList.add('d-none');
        }
    };

    renderizarCapaExistente();

    inputFoto.addEventListener('change', function () {
        var arquivo = this.files && this.files[0] ? this.files[0] : null;

        if (!arquivo) {
            renderizarCapaExistente();
            return;
        }

        if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
            renderizarCapaExistente();
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
        if (inputFoto.value) {
            inputFoto.value = '';
            renderizarCapaExistente();
            return;
        }

        fotoExistenteInput.value = '';
        renderizarCapaExistente();
    });
});
</script>