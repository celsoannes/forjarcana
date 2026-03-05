<?php

namespace App\Resinas;

use PDO;

class ResinaController
{
    private ResinaService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ResinaService($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, array $post, ?string $caminhoCapa = null): array
    {
        return $this->service->processarFluxoAdicao($usuarioId, $post, $caminhoCapa);
    }
}
