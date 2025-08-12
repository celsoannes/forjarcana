<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;

// Busca a resina do usuário
$stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$resina = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resina) {
    echo '<div class="alert alert-danger">Resina não encontrada ou você não tem permissão!</div>';
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM resinas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo '<script>window.location.href="?pagina=resinas";</script>';
    exit;
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>