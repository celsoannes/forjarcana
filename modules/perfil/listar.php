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

// Atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $sobrenome = trim($_POST['sobrenome']);
    $email = trim($_POST['email']);
    $celular = trim($_POST['celular']);
    $cpf = trim($_POST['cpf']);

    // Atualiza dados básicos
    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, celular = ?, cpf = ? WHERE id = ?");
    $stmt->execute([$nome, $sobrenome, $email, $celular, $cpf, $usuario_id]);

    // Atualiza dados do array para exibir na tela
    $usuario['nome'] = $nome;
    $usuario['sobrenome'] = $sobrenome;
    $usuario['email'] = $email;
    $usuario['celular'] = $celular;
    $usuario['cpf'] = $cpf;

    echo '<div class="alert alert-success">Perfil atualizado com sucesso!</div>';
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Meu Perfil</h3>
    </div>
    <div class="card-body">
        <form method="POST">
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
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>">
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
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>