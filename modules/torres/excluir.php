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
    $arquivo = rtrim($raizProjeto, '/') . '/' . $caminhoRelativo;
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
            removerArquivoSeExistir($raizProjeto, $diretorioRelativo . '/' . $nomeBase . '_' . $sufixo . '.' . $extensao);
        }
    }
}

if ($usuario_id <= 0) {
    echo '<div class="alert alert-danger">Usuário não autenticado.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT t.id, p.id AS produto_id, p.imagem_capa, p.imagens
    FROM torres t
    INNER JOIN produtos p ON p.id = t.produto_id
    WHERE t.id = ? AND t.usuario_id = ?
    LIMIT 1");
$stmt->execute([$id, $usuario_id]);
$torre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$torre) {
    echo '<div class="alert alert-danger">Torre não encontrada!</div>';
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtDelete = $pdo->prepare("DELETE FROM produtos WHERE id = ? AND usuario_id = ?");
    $stmtDelete->execute([(int) $torre['produto_id'], $usuario_id]);

    if ($stmtDelete->rowCount() < 1) {
        throw new RuntimeException('Torre não encontrada ou sem permissão para excluir.');
    }

    $pdo->commit();

    $raizProjeto = realpath(__DIR__ . '/../../');
    if ($raizProjeto !== false) {
        if (!empty($torre['imagem_capa'])) {
            removerDerivadosImagem($raizProjeto, (string) $torre['imagem_capa']);
        }

        $imagensCampo = (string) ($torre['imagens'] ?? '');
        if ($imagensCampo !== '') {
            $imagensDecodificadas = json_decode($imagensCampo, true);
            if (is_array($imagensDecodificadas)) {
                foreach ($imagensDecodificadas as $imagemPath) {
                    if (is_string($imagemPath) && $imagemPath !== '') {
                        removerDerivadosImagem($raizProjeto, $imagemPath);
                    }
                }
            } else {
                removerDerivadosImagem($raizProjeto, $imagensCampo);
            }
        }
    }

    echo '<script>window.location.href="?pagina=torres";</script>';
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<div class="alert alert-danger">Erro ao excluir: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
