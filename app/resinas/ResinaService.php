<?php

namespace App\Resinas;

use PDO;

class ResinaService
{
    private ResinaRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ResinaRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'preco_litro' => trim((string) ($post['preco_litro'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        $precoLitroRaw = str_replace(',', '.', (string) ($post['preco_litro'] ?? ''));

        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'preco_litro' => (float) $precoLitroRaw,
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $marca = trim((string) ($dados['marca'] ?? ''));
        $cor = trim((string) ($dados['cor'] ?? ''));
        $precoLitro = (float) ($dados['preco_litro'] ?? 0);

        if ($nome === '' || $marca === '' || $cor === '' || $precoLitro <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro da resina.'];
        }

        try {
            $this->repository->inserirResina([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'marca' => (string) ($dados['marca'] ?? ''),
                'cor' => (string) ($dados['cor'] ?? ''),
                'preco_litro' => (float) ($dados['preco_litro'] ?? 0),
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
