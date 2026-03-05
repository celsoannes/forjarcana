<?php

namespace App\Filamentos;

use PDO;

class FilamentoController
{
    private FilamentoService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new FilamentoService($pdo);
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
