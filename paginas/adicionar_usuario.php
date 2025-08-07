<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem adicionar usuários.</div>';
    return;
}

$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $cargo = $_POST['cargo'] ?? 'user';
    $data_expiracao = $_POST['data_expiracao'] ?? null;

    if (!$nome || !$email || !$senha || !in_array($cargo, ['user', 'admin'])) {
        $mensagem = "Preencha todos os campos obrigatórios corretamente.";
    } else {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, cargo, data_expiracao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $hash, $cargo, $data_expiracao ?: null]);
            $mensagem = "Usuário cadastrado com sucesso!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensagem = "E-mail já cadastrado.";
            } else {
                $mensagem = "Erro ao cadastrar: " . $e->getMessage();
            }
        }
    }
}
?>
<h2 class="mb-4">Adicionar Usuário</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST" class="mb-4">
    <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Cargo</label>
        <select name="cargo" class="form-select" required>
            <option value="user">Usuário</option>
            <option value="admin">Administrador</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Data de Expiração (opcional)</label>
        <input type="datetime-local" name="data_expiracao" class="form-control">
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
    <a href="?pagina=usuarios" class="btn btn-voltar">Voltar</a>
</form>