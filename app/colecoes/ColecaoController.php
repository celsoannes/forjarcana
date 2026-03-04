<?php

namespace App\Colecoes;

use PDO;

class ColecaoController
{
    private ColecaoService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ColecaoService($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId): array
    {
        return $this->service->carregarContextoAdicao($usuarioId);
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
