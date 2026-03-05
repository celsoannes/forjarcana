<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;

// Busca o filamento do usuário
$stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$filamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$filamento) {
    echo '<div class="alert alert-danger">Filamento não encontrado ou você não tem permissão!</div>';
    exit;
}

// Excluir imagens associadas (thumbnail, media, grande) se houver capa
$capa = $filamento['capa'] ?? '';
if ($capa && strpos($capa, 'uploads/') === 0) {
    $caminhoAbsoluto = __DIR__ . '/../../' . $capa;
    $padroes = [
        preg_replace('/_media\\.webp$/', '_media.webp', $caminhoAbsoluto),
        preg_replace('/_media\\.webp$/', '_thumbnail.webp', $caminhoAbsoluto),
        preg_replace('/_media\\.webp$/', '_grande.webp', $caminhoAbsoluto),
    ];
    foreach ($padroes as $arquivo) {
        if ($arquivo && file_exists($arquivo)) {
            @unlink($arquivo);
        }
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM filamento WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo '<script>window.location.href="?pagina=filamentos";</script>';
    exit;
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>