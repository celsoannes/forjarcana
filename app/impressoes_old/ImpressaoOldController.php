<?php

namespace App\ImpressoesOld;

use PDO;

class ImpressaoOldController
{
    private ImpressaoOldService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ImpressaoOldService($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId, int $impressoraId, int $resinaId, int $filamentoId): array
    {
        return $this->service->carregarContextoAdicao($usuarioId, $impressoraId, $resinaId, $filamentoId);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, array $impressoraEscolhida, ?array $material, string $materialTipo, int $materialId): array
    {
        return $this->service->processarFluxoAdicao($usuarioId, $usuarioUuid, $post, $files, $impressoraEscolhida, $material, $materialTipo, $materialId);
    }
}
