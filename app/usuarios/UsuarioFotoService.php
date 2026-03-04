<?php

namespace App\Usuarios;

require_once __DIR__ . '/../upload_imagem.php';

class UsuarioFotoService
{
    private string $basePath;

    public function __construct(string $basePath = __DIR__ . '/../../')
    {
        $this->basePath = rtrim($basePath, '/\\') . '/';
    }

    public function processarUploadFoto(array $arquivo, string $uuid, bool $apagarAntigas = false): ?string
    {
        $codigoErro = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($codigoErro !== UPLOAD_ERR_OK) {
            return null;
        }

        $arquivo['name'] = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string) ($arquivo['name'] ?? 'arquivo'));

        $fotoNome = \uploadImagem($arquivo, $uuid, 'usuarios', null, 'foto', $apagarAntigas);
        if (!$fotoNome) {
            throw new \RuntimeException('Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.');
        }

        return $fotoNome;
    }

    public function definirFotoNaSessao(string $fotoNome): void
    {
        $thumb = preg_replace('/_media\.(png|webp)$/i', '_thumbnail.$1', $fotoNome);
        if (is_string($thumb) && file_exists($this->basePath . $thumb)) {
            $_SESSION['usuario_foto'] = $thumb;
            return;
        }

        $thumbLegado = preg_replace('/_media\.(png|webp)$/i', '_thumb.$1', $fotoNome);
        if (is_string($thumbLegado) && file_exists($this->basePath . $thumbLegado)) {
            $_SESSION['usuario_foto'] = $thumbLegado;
            return;
        }

        $_SESSION['usuario_foto'] = $fotoNome;
    }
}
