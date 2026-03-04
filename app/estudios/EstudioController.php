<?php

namespace App\Estudios;

use PDO;

class EstudioController
{
    private EstudioService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new EstudioService($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, array $post): array
    {
        return $this->service->processarFluxoAdicao($usuarioId, $post);
    }
}
