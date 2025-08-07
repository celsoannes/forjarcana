<?php
require __DIR__ . '/../app/db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM resinas WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: ?pagina=resina");
exit;