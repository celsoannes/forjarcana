<?php

namespace App\Colecoes;

use PDO;

class ColecaoService
{
    private ColecaoRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ColecaoRepository($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [
                'estudios' => [],
            ];
        }

        try {
            return [
                'estudios' => $this->repository->listarEstudiosPorUsuario($usuarioId),
            ];
        } catch (\Throwable $e) {
            return [
                'estudios' => [],
            ];
        }
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'estudio_nome' => trim((string) ($post['estudio_nome'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        return $this->montarEstadoFormularioAdicao($post);
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $estudioNome = trim((string) ($dados['estudio_nome'] ?? ''));

        if ($nome === '' || $estudioNome === '') {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $estudioNome = trim((string) ($dados['estudio_nome'] ?? ''));

        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro da coleção.'];
        }

        try {
            $this->repository->iniciarTransacao();

            $estudio = $this->repository->buscarEstudioPorNome($usuarioId, $estudioNome);
            $estudioId = (int) ($estudio['id'] ?? 0);

            if ($estudioId <= 0) {
                $estudioId = $this->repository->inserirEstudio($usuarioId, $estudioNome);
            }

            if ($estudioId <= 0) {
                throw new \RuntimeException('Não foi possível identificar o estúdio.');
            }

            $this->repository->inserirColecao($usuarioId, $estudioId, $nome);
            $this->repository->confirmarTransacao();

            return ['sucesso' => true, 'erro' => ''];
        } catch (\Throwable $e) {
            if ($this->repository->emTransacao()) {
                $this->repository->desfazerTransacao();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar coleção: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, array $post): array
    {
        $dados = $this->parseDadosAdicao($post);

        if ($usuarioId <= 0) {
            return [
                'sucesso' => false,
                'erro' => 'Usuário inválido para cadastro da coleção.',
                'dados' => $dados,
            ];
        }

        $erro = $this->validarDadosAdicao($dados);
        if ($erro !== '') {
            return [
                'sucesso' => false,
                'erro' => $erro,
                'dados' => $dados,
            ];
        }

        $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, $dados);
        if (!empty($resultadoCadastro['sucesso'])) {
            return [
                'sucesso' => true,
                'erro' => '',
                'dados' => $dados,
            ];
        }

        return [
            'sucesso' => false,
            'erro' => trim((string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar coleção.')),
            'dados' => $dados,
        ];
    }
}
