<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;

// Busca a impressora do usuário
$stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$impressora = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$impressora) {
    echo '<div class="alert alert-danger">Impressora não encontrada ou você não tem permissão!</div>';
    exit;
}

// Excluir imagens de capa, se existirem
if (!empty($impressora['capa'])) {
    $caminhoBase = preg_replace('/_media\.webp$/', '', $impressora['capa']);
    $tamanhos = ['media', 'thumbnail', 'grande'];
    foreach ($tamanhos as $tamanho) {
        $arquivo = __DIR__ . '/../../' . $caminhoBase . "_{$tamanho}.webp";
        if (file_exists($arquivo)) {
            @unlink($arquivo);
        }
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM impressoras WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo '<script>window.location.href="?pagina=impressoras3d";</script>';
    exit;
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}