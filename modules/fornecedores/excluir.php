<?php
session_start();
require_once __DIR__ . '/../../app/db.php';

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

$stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fornecedor) {
	echo '<div class="alert alert-danger">Fornecedor não encontrado ou você não tem permissão!</div>';
	exit;
}

try {
	$stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ? AND usuario_id = ?");
	$stmt->execute([$id, $usuario_id]);

	if ($stmt->rowCount() < 1) {
		echo '<div class="alert alert-danger">Não foi possível excluir o fornecedor.</div>';
		exit;
	}

	echo '<script>window.location.href="?pagina=fornecedores";</script>';
	exit;
} catch (PDOException $e) {
	echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

