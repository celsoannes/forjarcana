<?php

namespace App\Estudios;

use PDO;

class EstudioService
{
    private EstudioRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new EstudioRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'site' => trim((string) ($post['site'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'site' => trim((string) ($post['site'] ?? '')),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($nome === '') {
            return 'Preencha o campo nome obrigatório.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro do estúdio.'];
        }

        try {
            $this->repository->inserirEstudio([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'site' => (string) ($dados['site'] ?? ''),
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

        return $this->processarCadastroAdicao($usuarioId, $dados);
    }
}
