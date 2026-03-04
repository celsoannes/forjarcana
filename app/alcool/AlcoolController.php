<?php

namespace App\Alcool;

use PDO;

class AlcoolController
{
    private AlcoolService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new AlcoolService($pdo);
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
