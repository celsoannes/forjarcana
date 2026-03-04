<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Miniaturas\MiniaturaController;

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $miniaturaController = new MiniaturaController($pdo);
    $miniaturaController->excluir($id);
}

echo '<script>window.location.href="?pagina=miniaturas";</script>';
exit;
