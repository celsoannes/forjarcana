<?php
require __DIR__ . '/../app/db.php';

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? null;

if ($id && $usuario_id) {
    // Busca o nome da imagem antes de excluir, garantindo que seja do usuário
    $stmt = $pdo->prepare("SELECT imagem FROM insumos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $imagem = $stmt->fetchColumn();

    // Exclui o insumo do banco apenas se for do usuário
    $stmt = $pdo->prepare("DELETE FROM insumos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);

    // Exclui o arquivo da imagem, se existir
    if ($imagem && file_exists(__DIR__ . '/../uploads/' . $imagem)) {
        unlink(__DIR__ . '/../uploads/' . $imagem);
    }
}
header("Location: ?pagina=insumos");