<?php

namespace App\Componentes;

use PDO;

class ComponenteController
{
    private ComponenteService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ComponenteService($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files): array
    {
        return $this->service->processarFluxoAdicao($usuarioId, $usuarioUuid, $post, $files);
    }
}
