<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem editar usuários.</div>';
    return;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">Usuário não encontrado.</div>';
    return;
}

$stmt = $pdo->prepare("SELECT id, nome, email, cargo, data_expiracao FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo '<div class="alert alert-danger">Usuário não encontrado.</div>';
    return;
}

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = $_POST['cargo'] ?? 'user';
    $data_expiracao = $_POST['data_expiracao'] ?? null;

    if (!$nome || !$email || !in_array($cargo, ['user', 'admin'])) {
        $mensagem = "Preencha todos os campos obrigatórios corretamente.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, cargo = ?, data_expiracao = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $cargo, $data_expiracao ?: null, $id]);
        $mensagem = "Usuário atualizado com sucesso!";
        // Atualiza os dados exibidos
        $usuario['nome'] = $nome;
        $usuario['email'] = $email;
        $usuario['cargo'] = $cargo;
        $usuario['data_expiracao'] = $data_expiracao;
    }
}
?>
<h2 class="mb-4">Editar Usuário</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Cargo</label>
        <select name="cargo" class="form-select" required>
            <option value="user" <?= $usuario['cargo'] === 'user' ? 'selected' : '' ?>>Usuário</option>
            <option value="admin" <?= $usuario['cargo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Data de Expiração (opcional)</label>
        <input type="datetime-local" name="data_expiracao" class="form-control"
            value="<?= $usuario['data_expiracao'] ? date('Y-m-d\TH:i', strtotime($usuario['data_expiracao'])) : '' ?>">
    </div>
    <button type="submit" class="btn btn-warning">Salvar</button>
    <a href="?pagina=usuarios" class="btn btn-secondary">Voltar</a>
</form>