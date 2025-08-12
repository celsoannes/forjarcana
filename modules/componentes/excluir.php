<?php
// filepath: /var/www/html/forjarcana/modules/componentes/excluir.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;

// Busca o componente do usuário
$stmt = $pdo->prepare("SELECT imagem FROM componentes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$componente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$componente) {
    echo '<div class="alert alert-danger">Componente não encontrado ou você não tem permissão!</div>';
    exit;
}

// Exclui imagens associadas
if (!empty($componente['imagem'])) {
    $baseDir = __DIR__ . "/../../" . dirname($componente['imagem']);
    $prefix = pathinfo($componente['imagem'], PATHINFO_FILENAME);
    $prefix = preg_replace('/_media$/', '', $prefix); // Remove o sufixo _media

    // Exclui todos os tamanhos
    $tipos = ['thumb', 'pequena', 'media', 'grande'];
    foreach ($tipos as $tipo) {
        $arquivo = "$baseDir/{$prefix}_{$tipo}.png";
        if (file_exists($arquivo)) {
            unlink($arquivo);
        }
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM componentes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo '<script>window.location.href="?pagina=componentes";</script>';
    exit;
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>