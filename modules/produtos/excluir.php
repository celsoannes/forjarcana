<?php
session_start();
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Produtos\ProdutoController;

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0) {
    echo '<div class="alert alert-danger">Usuário não autenticado.</div>';
    exit;
}

$produtoController = new ProdutoController($pdo);
$raizProjeto = realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
$resultado = $produtoController->excluir($id, $usuario_id, (string) $raizProjeto);

if (!empty($resultado['sucesso'])) {
    echo '<script>window.location.href="?pagina=produtos";</script>';
    exit;
}

$erro = (string) ($resultado['erro'] ?? 'Não foi possível excluir o produto.');
echo '<div class="alert alert-danger">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>';
exit;
