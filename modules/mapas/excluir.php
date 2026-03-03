<?php
session_start();
require_once __DIR__ . '/../../app/db.php';

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

function caminhoRelativoSeguroMapa(string $caminho): ?string {
	$caminho = trim(str_replace('\\', '/', $caminho));
	if ($caminho === '') {
		return null;
	}

	if (preg_match('#^https?://#i', $caminho)) {
		$pathUrl = parse_url($caminho, PHP_URL_PATH);
		$caminho = is_string($pathUrl) ? $pathUrl : '';
	}

	$caminho = preg_replace('/[?#].*$/', '', $caminho);
	if (!is_string($caminho) || trim($caminho) === '') {
		return null;
	}

	$posUploads = stripos($caminho, '/uploads/');
	if ($posUploads !== false) {
		$caminho = substr($caminho, $posUploads + 1);
	}

	$caminho = ltrim($caminho, '/');
	if (strpos($caminho, '..') !== false) {
		return null;
	}

	if (strpos($caminho, 'uploads/') !== 0) {
		return null;
	}

	return $caminho;
}

function removerArquivoSeExistirMapa(string $raizProjeto, string $caminhoRelativo): void {
	$arquivo = rtrim($raizProjeto, '/') . '/' . $caminhoRelativo;
	if (is_file($arquivo)) {
		@unlink($arquivo);
	}
}

function removerDerivadosImagemMapa(string $raizProjeto, string $caminhoOriginal): void {
	$caminhoRelativo = caminhoRelativoSeguroMapa($caminhoOriginal);
	if ($caminhoRelativo === null) {
		return;
	}

	removerArquivoSeExistirMapa($raizProjeto, $caminhoRelativo);

	$arquivo = basename($caminhoRelativo);
	$nomeSemExtensao = pathinfo($arquivo, PATHINFO_FILENAME);
	$diretorioRelativo = dirname($caminhoRelativo);
	if ($diretorioRelativo === '.' || $diretorioRelativo === '') {
		return;
	}

	$nomeBase = preg_replace('/_(thumb|thumbnail|pequena|media|grande)$/i', '', $nomeSemExtensao);
	$sufixos = ['thumb', 'thumbnail', 'pequena', 'media', 'grande'];
	$extensoes = ['png', 'jpg', 'jpeg', 'webp'];

	foreach ($sufixos as $sufixo) {
		foreach ($extensoes as $extensao) {
			removerArquivoSeExistirMapa($raizProjeto, $diretorioRelativo . '/' . $nomeBase . '_' . $sufixo . '.' . $extensao);
		}
	}
}

function removerCampoImagensMapa(string $raizProjeto, ?string $valorCampo): void {
	$valorCampo = trim((string) $valorCampo);
	if ($valorCampo === '') {
		return;
	}

	$imagensDecodificadas = json_decode($valorCampo, true);
	if (is_array($imagensDecodificadas)) {
		foreach ($imagensDecodificadas as $imagemPath) {
			if (is_string($imagemPath) && trim($imagemPath) !== '') {
				removerDerivadosImagemMapa($raizProjeto, $imagemPath);
			}
		}
		return;
	}

	removerDerivadosImagemMapa($raizProjeto, $valorCampo);
}

if ($usuario_id <= 0) {
	echo '<div class="alert alert-danger">Usuário não autenticado.</div>';
	exit;
}

if ($id <= 0) {
	echo '<div class="alert alert-danger">Mapa inválido para exclusão.</div>';
	exit;
}

$stmt = $pdo->prepare("SELECT m.id, m.produto_id, m.imagem_capa, m.imagens
	FROM mapas m
	WHERE m.id = ? AND m.usuario_id = ?
	LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$mapa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mapa) {
	echo '<div class="alert alert-danger">Mapa não encontrado!</div>';
	exit;
}

try {
	$pdo->beginTransaction();

	$produtoId = (int) ($mapa['produto_id'] ?? 0);

	$stmtDeleteMapa = $pdo->prepare("DELETE FROM mapas WHERE id = ? AND usuario_id = ?");
	$stmtDeleteMapa->execute([$id, $usuario_id]);

	if ($stmtDeleteMapa->rowCount() < 1) {
		throw new RuntimeException('Mapa não encontrado ou sem permissão para excluir.');
	}

	if ($produtoId > 0) {
		$stmtDeleteProduto = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND usuario_id = ?");
		$stmtDeleteProduto->execute([$produtoId, $usuario_id]);
	}

	$pdo->commit();

	$raizProjeto = realpath(__DIR__ . '/../../');
	if ($raizProjeto !== false) {
		if (!empty($mapa['imagem_capa'])) {
			removerDerivadosImagemMapa($raizProjeto, (string) $mapa['imagem_capa']);
		}
		removerCampoImagensMapa($raizProjeto, (string) ($mapa['imagens'] ?? ''));
	}

	echo '<script>window.location.href="?pagina=mapas";</script>';
	exit;
} catch (Throwable $e) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

