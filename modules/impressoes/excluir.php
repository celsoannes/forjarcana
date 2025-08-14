<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? 0;

// Busca a impressão do usuário
$stmt = $pdo->prepare("SELECT imagem_capa FROM impressoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$impressao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$impressao) {
    echo '<div class="alert alert-danger">Impressão não encontrada ou você não tem permissão!</div>';
    exit;
}

// Exclui imagem de capa associada
if (!empty($impressao['imagem_capa'])) {
    $baseDir = __DIR__ . "/../../" . dirname($impressao['imagem_capa']);
    $prefix = pathinfo($impressao['imagem_capa'], PATHINFO_FILENAME);
    $prefix = preg_replace('/_media$/', '', $prefix); // Remove o sufixo _media

    // Exclui todos os tamanhos
    $tipos = ['thumb', 'pequena', 'media', 'grande'];
    foreach ($tipos as $tipo) {
        $arquivo = "$baseDir/{$prefix}_{$tipo}.png";
        if (file_exists($arquivo)) {
            unlink($arquivo);
        }
    }
    // Exclui o arquivo original também
    $arquivoOriginal = __DIR__ . "/../../" . $impressao['imagem_capa'];
    if (file_exists($arquivoOriginal)) {
        unlink($arquivoOriginal);
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM impressoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    echo '<script>window.location.href="?pagina=impressoes";</script>';
    exit;
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>