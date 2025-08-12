<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

if (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin') {
    echo 'erro';
    exit;
}

$id = $_POST['id'] ?? 0;

// Busca o uuid do usuário
$stmt = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$uuid = $stmt->fetchColumn();

if (!$uuid) {
    echo 'erro';
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    // Exclui a pasta de imagens do usuário
    $dir = __DIR__ . "/../../uploads/usuarios/$uuid";
    if (is_dir($dir)) {
        function excluirPasta($pasta) {
            $arquivos = array_diff(scandir($pasta), ['.', '..']);
            foreach ($arquivos as $arquivo) {
                $caminho = "$pasta/$arquivo";
                if (is_dir($caminho)) {
                    excluirPasta($caminho);
                } else {
                    unlink($caminho);
                }
            }
            rmdir($pasta);
        }
        excluirPasta($dir);
    }

    echo 'ok';
    exit;
} catch (PDOException $e) {
    echo 'erro';
    exit;
}
?>