<?php
session_start();
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Fornecedores\FornecedorController;

$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);

if ($usuario_id <= 0) {
	echo '<div class="alert alert-danger">Usuário não autenticado.</div>';
	exit;
}

if ($id <= 0) {
	echo '<div class="alert alert-danger">Fornecedor inválido.</div>';
	exit;
}

$fornecedorController = new FornecedorController($pdo);
$resultado = $fornecedorController->excluir($id, $usuario_id);

if (!empty($resultado['sucesso'])) {
	echo '<script>window.location.href="?pagina=fornecedores";</script>';
	exit;
}

$erro = (string) ($resultado['erro'] ?? 'Não foi possível excluir o fornecedor.');
echo '<div class="alert alert-danger">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>';
exit;

