<?php
require __DIR__ . '/../app/db.php';

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? null;

if ($id && $usuario_id) {
    // Busca o caminho relativo da imagem (ex: uuid/insumo_xxx_m.png)
    $stmt = $pdo->prepare("SELECT imagem FROM insumos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $imagem_media = $stmt->fetchColumn();

    // Exclui o insumo do banco apenas se for do usuário
    $stmt = $pdo->prepare("DELETE FROM insumos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);

    // Exclui os arquivos de imagem (média e thumbnail), se existirem
    if ($imagem_media) {
        // $imagem_media = 'uuid/insumo_xxx_m.png'
        $base = str_replace('_m.png', '', $imagem_media);
        $media_path = __DIR__ . '/../uploads/' . $base . '_m.png';
        $thumb_path = __DIR__ . '/../uploads/' . $base . '_t.png';

        if (file_exists($media_path)) {
            unlink($media_path);
        }
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
    }
}
header("Location: ?pagina=insumos");