<?php

namespace App\Usuarios;

class UsuarioFotoResolver
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\') . '/';
    }

    public function resolverParaLista(string $foto): string
    {
        $foto = trim($foto);
        if ($foto === '') {
            return '';
        }

        $thumbNovo = preg_replace('/_media\.(png|webp)$/i', '_thumbnail.$1', $foto);
        if (is_string($thumbNovo) && file_exists($this->basePath . $thumbNovo)) {
            return $thumbNovo;
        }

        $thumbLegado = preg_replace('/_media\.(png|webp)$/i', '_thumb.$1', $foto);
        if (is_string($thumbLegado) && file_exists($this->basePath . $thumbLegado)) {
            return $thumbLegado;
        }

        if (file_exists($this->basePath . $foto)) {
            return $foto;
        }

        return '';
    }
}
