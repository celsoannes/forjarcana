<?php

namespace App\Alcool;

use PDO;

class AlcoolService
{
    private AlcoolRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new AlcoolRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'preco_litro' => trim((string) ($post['preco_litro'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'preco_litro' => (float) str_replace(',', '.', (string) ($post['preco_litro'] ?? '0')),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $marca = trim((string) ($dados['marca'] ?? ''));
        $precoLitro = (float) ($dados['preco_litro'] ?? 0);

        if ($nome === '' || $marca === '' || $precoLitro <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro de álcool.'];
        }

        try {
            $this->repository->inserirAlcool([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'marca' => (string) ($dados['marca'] ?? ''),
                'preco_litro' => (float) ($dados['preco_litro'] ?? 0),
            ]);

            return ['sucesso' => true, 'erro' => ''];
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return ['sucesso' => false, 'erro' => 'Já existe um registro de álcool para este usuário.'];
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
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

        return $this->processarCadastroAdicao($usuarioId, $dados);
    }
}
