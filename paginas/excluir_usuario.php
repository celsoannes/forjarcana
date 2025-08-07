<?php
require __DIR__ . '/../app/db.php';

// Permite acesso apenas para admin
if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso restrito! Apenas administradores podem excluir usuários.</div>';
    return;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">Usuário não encontrado.</div>';
    return;
}

// Não permite excluir o próprio usuário logado
if ($_SESSION['usuario_id'] == $id) {
    echo '<div class="alert alert-warning">Você não pode excluir seu próprio usuário.</div>';
    return;
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->execute([$id]);

echo '<div class="alert alert-success">Usuário excluído com sucesso!</div>';
echo '<meta http-equiv="refresh" content="2;url=?pagina=usuarios">';