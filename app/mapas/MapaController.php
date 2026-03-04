<?php

namespace App\Mapas;

use PDO;

class MapaController
{
    private MapaService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new MapaService($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId, string $usuarioUuid): array
    {
        if ($usuarioId <= 0) {
            return [
                'usuario_uuid' => '',
                'fornecedores_disponiveis' => [],
            ];
        }

        return $this->service->carregarContextoAdicao($usuarioId, $usuarioUuid);
    }

    public function sugerirCampo(int $usuarioId, string $campo, string $termo): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        return $this->service->sugerirCampo($usuarioId, $campo, $termo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files): array
    {
        if ($usuarioId <= 0) {
            $dados = $this->service->parseDadosAdicao($post);

            return [
                'sucesso' => false,
                'erro' => 'Usuário inválido para cadastro do mapa.',
                'foto' => $dados['foto'] ?? null,
                'imagens' => is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [],
                'avisos_upload' => [],
            ];
        }

        return $this->service->processarFluxoAdicao($usuarioId, $usuarioUuid, $post, $files);
    }
}
