<?php
// excluir_impressora.php
require __DIR__ . '/../app/db.php';

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? null;

if ($id && $usuario_id) {
    $stmt = $pdo->prepare("DELETE FROM impressoras WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
}

header("Location: ?pagina=impressoras");
exit;