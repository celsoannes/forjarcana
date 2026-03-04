<?php

namespace App\Filamentos;

use PDO;

class FilamentoService
{
    private FilamentoRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new FilamentoRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'tipo' => trim((string) ($post['tipo'] ?? '')),
            'preco_kilo' => trim((string) ($post['preco_kilo'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        $precoKiloRaw = str_replace(',', '.', (string) ($post['preco_kilo'] ?? ''));

        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'tipo' => trim((string) ($post['tipo'] ?? '')),
            'preco_kilo' => (float) $precoKiloRaw,
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $marca = trim((string) ($dados['marca'] ?? ''));
        $cor = trim((string) ($dados['cor'] ?? ''));
        $tipo = trim((string) ($dados['tipo'] ?? ''));
        $precoKilo = (float) ($dados['preco_kilo'] ?? 0);

        if ($nome === '' || $marca === '' || $cor === '' || $tipo === '' || $precoKilo <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro do filamento.'];
        }

        try {
            $this->repository->inserirFilamento([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'marca' => (string) ($dados['marca'] ?? ''),
                'cor' => (string) ($dados['cor'] ?? ''),
                'tipo' => (string) ($dados['tipo'] ?? ''),
                'preco_kilo' => (float) ($dados['preco_kilo'] ?? 0),
            ]);

            return ['sucesso' => true, 'erro' => ''];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, array $post): array
    {
        $dados = $this->parseDadosAdicao($post);
        $erro = $this->validarDadosAdicao($dados);

        if ($erro !== '') {
            return [
                'sucesso' => false,
                'erro' => $erro,
            ];
        }

        $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, $dados);
        if (!empty($resultadoCadastro['sucesso'])) {
            return [
                'sucesso' => true,
                'erro' => '',
            ];
        }

        return [
            'sucesso' => false,
            'erro' => trim((string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar.')),
        ];
    }
}
