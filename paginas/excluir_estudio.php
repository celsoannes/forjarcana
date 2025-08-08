<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para excluir estudios.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM estudios WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
}
echo '<script>window.location.href="?pagina=estudios";</script>';
exit;