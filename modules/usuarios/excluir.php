<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Usuarios\UsuarioController;

if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo 'erro';
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo 'erro';
    exit;
}

try {
    $usuarioController = new UsuarioController($pdo);
    $excluiu = $usuarioController->excluir($id, __DIR__ . '/../../uploads');

    if (!$excluiu) {
        echo 'erro';
        exit;
    }

    echo 'ok';
    exit;
} catch (PDOException $e) {
    echo 'erro';
    exit;
}
?>