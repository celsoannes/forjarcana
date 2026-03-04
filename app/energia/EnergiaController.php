<?php

namespace App\Energia;

use PDO;

class EnergiaController
{
    private EnergiaService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new EnergiaService($pdo);
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
