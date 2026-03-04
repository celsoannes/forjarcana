<?php

namespace App\Impressoras3d;

use PDO;

class Impressora3dController
{
    private Impressora3dService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new Impressora3dService($pdo);
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
