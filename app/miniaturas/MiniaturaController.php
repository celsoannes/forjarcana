<?php

namespace App\Miniaturas;

use PDO;

class MiniaturaController
{
    private MiniaturaService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new MiniaturaService($pdo);
    }

    public function listarTodas(): array
    {
        return $this->service->listarTodas();
    }

    public function sugerirCampo(int $usuarioId, string $campo, string $termo, string $estudioNome = ''): array
    {
        return $this->service->sugerirCampo($usuarioId, $campo, $termo, $estudioNome);
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
                'racas_disponiveis' => [],
                'classes_disponiveis' => [],
                'armaduras_disponiveis' => [],
                'armas_disponiveis' => [],
                'outras_caracteristicas_disponiveis' => [],
            ];
        }

        return $this->service->carregarContextoAdicao($usuarioId, $usuarioUuid, $impressoraId, $filamentoId, $resinaId);
    }

    public function buscarPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->service->buscarPorId($id);
    }

    public function gerarSkuAutomatico(string $estudioNome, string $raca, string $classe): string
    {
        return $this->service->gerarSkuAutomatico($estudioNome, $raca, $classe);
    }

    public function resolverEstudio(int $usuarioId, string $entrada): array
    {
        if ($usuarioId <= 0) {
            throw new \RuntimeException('Usuário inválido para resolver estúdio.');
        }

        return $this->service->resolverEstudio($usuarioId, $entrada);
    }

    public function resolverColecao(int $usuarioId, int $estudioId, string $entrada): array
    {
        if ($usuarioId <= 0) {
            throw new \RuntimeException('Usuário inválido para resolver coleção.');
        }

        return $this->service->resolverColecao($usuarioId, $estudioId, $entrada);
    }

    public function resolverTematica(string $entrada): array
    {
        return $this->service->resolverTematica($entrada);
    }

    public function buscarEstudioPorId(int $usuarioId, int $estudioId): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }

        return $this->service->buscarEstudioPorId($usuarioId, $estudioId);
    }

    public function buscarColecaoPorId(int $usuarioId, int $colecaoId): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }

        return $this->service->buscarColecaoPorId($usuarioId, $colecaoId);
    }

    public function buscarTematicaPorId(int $tematicaId): ?array
    {
        return $this->service->buscarTematicaPorId($tematicaId);
    }

    public function vincularMiniaturaColecoes(int $miniaturaId, int $usuarioId, array $colecaoIds): void
    {
        if ($usuarioId <= 0) {
            return;
        }

        $this->service->vincularMiniaturaColecoes($miniaturaId, $usuarioId, $colecaoIds);
    }

    public function processarCadastroAdicao(array $dados): array
    {
        $usuarioId = (int) ($dados['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro da miniatura.'];
        }

        return $this->service->processarCadastroAdicao($dados);
    }

    public function calcularCustoImpressaoAdicao(int $usuarioId, array $selecaoConfirmacao, float $gramas, int $tempoTotalMin, float $taxaFalha): float
    {
        if ($usuarioId <= 0) {
            return 0.0;
        }

        return $this->service->calcularCustoImpressaoAdicao($usuarioId, $selecaoConfirmacao, $gramas, $tempoTotalMin, $taxaFalha);
    }

    public function descreverErroUpload(int $codigoErro): string
    {
        return $this->service->descreverErroUpload($codigoErro);
    }

    public function normalizarListaTags(string $valor): array
    {
        return $this->service->normalizarListaTags($valor);
    }

    public function processarUploadsAdicao(string $usuarioUuid, string $fotoExistente, string $imagensExistentesRaw, array $files): array
    {
        return $this->service->processarUploadsAdicao($usuarioUuid, $fotoExistente, $imagensExistentesRaw, $files);
    }

    public function validarDadosAdicao(array $dados): string
    {
        return $this->service->validarDadosAdicao($dados);
    }

    public function parseDadosAdicao(array $post): array
    {
        return $this->service->parseDadosAdicao($post);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, $selecaoConfirmacao): array
    {
        if ($usuarioId <= 0) {
            return [
                'sucesso' => false,
                'erro' => 'Usuário inválido para cadastro da miniatura.',
                'dadosPost' => $this->service->parseDadosAdicao($post),
                'foto' => null,
                'imagens' => [],
                'avisos_upload' => [],
                'custo_total_impressao' => 0.0,
            ];
        }

        return $this->service->processarFluxoAdicao($usuarioId, $usuarioUuid, $post, $files, $selecaoConfirmacao);
    }

    public function processarEdicao(int $id, array $dados): array
    {
        if ($id <= 0) {
            return ['sucesso' => false, 'erro' => 'Miniatura inválida.'];
        }

        return $this->service->editar($id, $dados);
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $this->service->excluir($id);
    }
}
