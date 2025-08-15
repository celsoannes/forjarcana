<?php
require_once __DIR__ . '/../../app/db.php';

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

        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, celular = ?, cpf = ? WHERE id = ?");
        $stmt->execute([$nome, $sobrenome, $email, $celular, $cpf, $usuario_id]);

        $usuario['nome'] = $nome;
        $usuario['sobrenome'] = $sobrenome;
        $usuario['email'] = $email;
        $usuario['celular'] = $celular;
        $usuario['cpf'] = $cpf;

        $alerta = '<div class="alert alert-success">Perfil atualizado com sucesso!</div>';
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
        <form method="POST">
            <input type="hidden" name="form_tipo" value="perfil">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nome">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($usuario['nome']) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="sobrenome">Sobrenome</label>
                    <input type="text" class="form-control" id="sobrenome" name="sobrenome" required value="<?= htmlspecialchars($usuario['sobrenome']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label for="celular">Celular</label>
                    <input type="text" class="form-control" id="celular" name="celular" required value="<?= htmlspecialchars($usuario['celular']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cpf">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" required value="<?= htmlspecialchars($usuario['cpf']) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="data_expiracao">Conta ativa até</label>
                    <input type="text" class="form-control" id="data_expiracao" name="data_expiracao"
                           value="<?= isset($usuario['data_expiracao']) ? htmlspecialchars(date('d/m/Y', strtotime($usuario['data_expiracao']))) : '' ?>"
                           readonly>
                </div>
            </div>
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
            <button type="submit" class="btn btn-warning">Alterar senha</button>
        </form>
    </div>
</div>