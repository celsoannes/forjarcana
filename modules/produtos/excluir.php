<?php
session_start();
require_once __DIR__ . '/../../app/db.php';

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

function caminhoRelativoSeguro(string $caminho): ?string {
    $caminho = trim(str_replace('\\', '/', $caminho));
    if ($caminho === '') {
        return null;
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

function removerArquivoSeExistir(string $raizProjeto, string $caminhoRelativo): void {
    $arquivo = rtrim($raizProjeto, '/').'/'.$caminhoRelativo;
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

function removerDerivadosImagem(string $raizProjeto, string $caminhoOriginal): void {
    $caminhoRelativo = caminhoRelativoSeguro($caminhoOriginal);
    if ($caminhoRelativo === null) {
        return;
    }

    removerArquivoSeExistir($raizProjeto, $caminhoRelativo);

    $arquivo = basename($caminhoRelativo);
    $diretorioRelativo = dirname($caminhoRelativo);
    $diretorioAbsoluto = rtrim($raizProjeto, '/').'/'.$diretorioRelativo;

    if (!is_dir($diretorioAbsoluto)) {
        return;
    }

    $nomeSemExtensao = pathinfo($arquivo, PATHINFO_FILENAME);
    $nomeBase = preg_replace('/_(thumb|thumbnail|pequena|media|grande)$/i', '', $nomeSemExtensao);
    $sufixos = ['thumb', 'thumbnail', 'pequena', 'media', 'grande'];
    $extensoes = ['png', 'jpg', 'jpeg', 'webp'];
    $extensaoOriginal = strtolower((string) pathinfo($arquivo, PATHINFO_EXTENSION));

    if ($extensaoOriginal !== '' && !in_array($extensaoOriginal, $extensoes, true)) {
        $extensoes[] = $extensaoOriginal;
    }

    foreach ($sufixos as $sufixo) {
        foreach ($extensoes as $extensao) {
            $caminhoDerivado = $diretorioRelativo.'/'.$nomeBase.'_'.$sufixo.'.'.$extensao;
            removerArquivoSeExistir($raizProjeto, $caminhoDerivado);
        }
    }
}

function removerCampoImagens(string $raizProjeto, ?string $valorCampo): void {
    $valorCampo = trim((string) $valorCampo);
    if ($valorCampo === '') {
        return;
    }

    $imagensDecodificadas = json_decode($valorCampo, true);
    if (is_array($imagensDecodificadas)) {
        foreach ($imagensDecodificadas as $imagemPath) {
            if (is_string($imagemPath) && trim($imagemPath) !== '') {
                removerDerivadosImagem($raizProjeto, $imagemPath);
            }
        }
        return;
    }

    removerDerivadosImagem($raizProjeto, $valorCampo);
}

if ($usuario_id <= 0) {
    echo '<div class="alert alert-danger">Usuário não autenticado.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT p.id, p.imagem_capa, p.imagens, s.sku AS sku_codigo
    FROM produtos p
    LEFT JOIN sku s ON s.produto_id = p.id
    WHERE p.id = ? AND p.usuario_id = ?
    LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtTorres = $pdo->prepare("SELECT capa, imagens FROM torres WHERE produto_id = ? AND usuario_id = ?");
$stmtTorres->execute([$id, $usuario_id]);
$torresImagens = $stmtTorres->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmtMapas = $pdo->prepare("SELECT imagem_capa, imagens FROM mapas WHERE produto_id = ? AND usuario_id = ?");
$stmtMapas->execute([$id, $usuario_id]);
$mapasImagens = $stmtMapas->fetchAll(PDO::FETCH_ASSOC) ?: [];

if (!$produto) {
    echo '<div class="alert alert-danger">Produto não encontrado!</div>';
    exit;
}

try {
    $pdo->beginTransaction();

    $skuCodigo = (string) ($produto['sku_codigo'] ?? '');

    if ($skuCodigo !== '') {
        $stmtMiniaturas = $pdo->prepare("DELETE FROM miniaturas WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)");
        $stmtMiniaturas->execute([$id, $usuario_id, $skuCodigo]);

        $stmtTorresDelete = $pdo->prepare("DELETE FROM torres WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)");
        $stmtTorresDelete->execute([$id, $usuario_id, $skuCodigo]);

        $stmtMapasDelete = $pdo->prepare("DELETE FROM mapas WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)");
        $stmtMapasDelete->execute([$id, $usuario_id, $skuCodigo]);
    } else {
        $stmtMiniaturas = $pdo->prepare("DELETE FROM miniaturas WHERE produto_id = ?");
        $stmtMiniaturas->execute([$id]);

        $stmtTorresDelete = $pdo->prepare("DELETE FROM torres WHERE produto_id = ?");
        $stmtTorresDelete->execute([$id]);

        $stmtMapasDelete = $pdo->prepare("DELETE FROM mapas WHERE produto_id = ?");
        $stmtMapasDelete->execute([$id]);
    }

    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('Produto não encontrado ou sem permissão para excluir.');
    }

    $pdo->commit();

    $raizProjeto = realpath(__DIR__ . '/../../');
    if ($raizProjeto !== false) {
        if (!empty($produto['imagem_capa'])) {
            removerDerivadosImagem($raizProjeto, (string) $produto['imagem_capa']);
        }

        removerCampoImagens($raizProjeto, (string) ($produto['imagens'] ?? ''));

        foreach ($torresImagens as $torreImagem) {
            if (!empty($torreImagem['capa'])) {
                removerDerivadosImagem($raizProjeto, (string) $torreImagem['capa']);
            }
            removerCampoImagens($raizProjeto, (string) ($torreImagem['imagens'] ?? ''));
        }

        foreach ($mapasImagens as $mapaImagem) {
            if (!empty($mapaImagem['imagem_capa'])) {
                removerDerivadosImagem($raizProjeto, (string) $mapaImagem['imagem_capa']);
            }
            removerCampoImagens($raizProjeto, (string) ($mapaImagem['imagens'] ?? ''));
        }
    }

    echo '<script>window.location.href="?pagina=produtos";</script>';
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
