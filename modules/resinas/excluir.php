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

// Apaga imagens vinculadas (capa) se existirem
if (!empty($resina['capa'])) {
    $capaPath = $resina['capa'];
    // Exemplo: uploads/usuarios/{uuid}/resina_{hash}_media.webp
    $capaFullPath = __DIR__ . '/../../' . $capaPath;
    $dir = dirname($capaFullPath);
    $basename = basename($capaFullPath);
    // Pega prefixo e hash
    if (preg_match('/^(resina)_([a-f0-9]+)_media\.webp$/', $basename, $m)) {
        $prefix = $m[1];
        $hash = $m[2];
        // Apaga todos os tamanhos conhecidos
        $tipos = ['media', 'thumbnail', 'grande'];
        foreach ($tipos as $tipo) {
            $file = $dir . "/{$prefix}_{$hash}_{$tipo}.webp";
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    } else {
        // Se não bater o padrão, apaga só o arquivo principal
        if (file_exists($capaFullPath)) {
            @unlink($capaFullPath);
        }
    }
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