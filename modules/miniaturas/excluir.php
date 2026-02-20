<?php
require_once __DIR__ . '/../../app/db.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM miniaturas WHERE id = ?");
    $stmt->execute([$id]);
}

echo '<script>window.location.href="?pagina=miniaturas";</script>';
exit;
