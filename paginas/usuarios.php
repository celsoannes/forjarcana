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
<a href="?pagina=adicionar_usuario" class="btn btn-success mb-3">Adicionar Usuário</a>
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
                    <a href="?pagina=editar_usuario&id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="?pagina=excluir_usuario&id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>