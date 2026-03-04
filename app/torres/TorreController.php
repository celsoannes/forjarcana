<?php

namespace App\Torres;

use PDO;

class TorreController
{
    private TorreService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new TorreService($pdo);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        try {
            return $this->service->listarPorUsuario($usuarioId);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function carregarContextoAdicao(int $usuarioId, string $usuarioUuid, int $impressoraId, int $filamentoId, int $resinaId): array
    {
        if ($usuarioId <= 0) {
            return [
                'usuario_uuid' => '',
                'selecao_confirmacao' => null,
                'aviso_selecao' => '',
                'estudios_disponiveis' => [],
                'colecoes_disponiveis' => [],
                'tematicas_disponiveis' => [],
                'outras_caracteristicas_disponiveis' => [],
            ];
        }

        return $this->service->carregarContextoAdicao($usuarioId, $usuarioUuid, $impressoraId, $filamentoId, $resinaId);
    }

    public function processarCriacao(array $dados): array
    {
        $usuarioId = (int) ($dados['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.'];
        }

        return $this->service->processarCriacao($dados);
    }

    public function calcularCustosImpressao(int $usuarioId, array $selecaoConfirmacao, float $gramas, int $tempoTotalMin, float $taxaFalha): array
    {
        if ($usuarioId <= 0) {
            return [
                'valor_kwh' => 1.0,
                'custo_material' => 0.0,
                'custo_lavagem_alcool' => 0.0,
                'custo_energia' => 0.0,
                'custo_depreciacao' => 0.0,
                'custo_total_impressao' => 0.0,
            ];
        }

        return $this->service->calcularCustosImpressao($usuarioId, $selecaoConfirmacao, $gramas, $tempoTotalMin, $taxaFalha);
    }

    public function processarUploadsAdicao(array $files, string $usuarioUuid, ?string $fotoAtual, array $imagensAtuais): array
    {
        if ($usuarioUuid === '') {
            return [
                'erro' => 'Não foi possível identificar o UUID do usuário para upload das imagens.',
                'foto' => $fotoAtual,
                'imagens' => $imagensAtuais,
            ];
        }

        return $this->service->processarUploadsAdicao($files, $usuarioUuid, $fotoAtual, $imagensAtuais);
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, $selecaoConfirmacao): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.', 'torre_id' => 0, 'foto' => null, 'imagens' => []];
        }

        return $this->service->processarFluxoAdicao($usuarioId, $usuarioUuid, $post, $files, $selecaoConfirmacao);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function buscarParaEdicao(int $id, int $usuarioId): ?array
    {
        if ($usuarioId <= 0 || $id <= 0) {
            return null;
        }

        return $this->service->buscarParaEdicao($id, $usuarioId);
    }

    public function buscarParaVisualizacao(int $id, int $usuarioId): ?array
    {
        if ($usuarioId <= 0 || $id <= 0) {
            return null;
        }

        return $this->service->buscarParaVisualizacao($id, $usuarioId);
    }

    public function processarEdicao(int $id, int $usuarioId, int $produtoId, array $dados, array $compatibilidade): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.'];
        }

        if ($id <= 0 || $produtoId <= 0) {
            return ['sucesso' => false, 'erro' => 'Torre inválida.'];
        }

        return $this->service->editar($id, $usuarioId, $dados, $compatibilidade, $produtoId);
    }

    public function excluir(int $id, int $usuarioId, string $raizProjeto): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.'];
        }

        if ($id <= 0) {
            return ['sucesso' => false, 'erro' => 'Torre inválida.'];
        }

        return $this->service->excluir($id, $usuarioId, $raizProjeto);
    }
}
