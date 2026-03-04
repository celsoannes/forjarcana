<?php

namespace App\Torres;

require_once __DIR__ . '/../upload_imagem.php';

class TorreImagemService
{
    public function processarUploadsAdicao(array $files, string $usuarioUuid, ?string $fotoAtual, array $imagensAtuais): array
    {
        $foto = $fotoAtual;
        $imagens = $imagensAtuais;
        $erro = '';

        $tamanhosUpload = [
            'thumbnail' => [150, 150, 'crop'],
            'pequena' => [300, 300, 'proporcional'],
            'media' => [300, 300, 'proporcional'],
            'grande' => [1024, 1024, 'proporcional'],
        ];

        if (isset($files['foto']) && (($files['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
            $fotoUpload = \uploadImagem($files['foto'], $usuarioUuid, 'usuarios', $tamanhosUpload, 'torre_CAPA', false);
            if ($fotoUpload === false) {
                $erro = 'Erro ao enviar a imagem de capa.';
            } else {
                $foto = $fotoUpload;
            }
        }

        if ($erro === '' && isset($files['fotos']) && isset($files['fotos']['name']) && is_array($files['fotos']['name'])) {
            $totalArquivos = count($files['fotos']['name']);
            for ($i = 0; $i < $totalArquivos; $i++) {
                $nomeArquivo = trim((string) ($files['fotos']['name'][$i] ?? ''));
                $erroArquivo = $files['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

                if ($nomeArquivo === '' || $erroArquivo === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($erroArquivo !== UPLOAD_ERR_OK) {
                    continue;
                }

                $arquivoImagem = [
                    'name' => $nomeArquivo,
                    'type' => $files['fotos']['type'][$i] ?? '',
                    'tmp_name' => $files['fotos']['tmp_name'][$i] ?? '',
                    'error' => $erroArquivo,
                    'size' => $files['fotos']['size'][$i] ?? 0,
                ];

                $imagemUpload = \uploadImagem($arquivoImagem, $usuarioUuid, 'usuarios', $tamanhosUpload, 'torre_IMAGEM', false);
                if ($imagemUpload !== false) {
                    $imagens[] = $imagemUpload;
                }
            }
        }

        return [
            'erro' => $erro,
            'foto' => $foto,
            'imagens' => $imagens,
        ];
    }
}
