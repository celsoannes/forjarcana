<?php

namespace App\Fornecedores;

use PDO;

class FornecedorController
{
    private FornecedorService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new FornecedorService($pdo);
    }

    public function buscarPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        return $this->service->buscarPorIdEUsuario($id, $usuarioId);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->service->listarPorUsuario($usuarioId);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return $this->service->montarEstadoFormularioAdicao($post);
    }

    public function processarCriacao(int $usuarioId, array $dados): array
    {
        $erro = $this->service->validar($dados);
        if ($erro !== null) {
            return ['sucesso' => false, 'erro' => $erro];
        }

        try {
            $this->service->criar($usuarioId, $dados);
            return ['sucesso' => true, 'erro' => null];
        } catch (\PDOException $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarEdicao(int $id, int $usuarioId, array $dados): array
    {
        $erro = $this->service->validar($dados);
        if ($erro !== null) {
            return ['sucesso' => false, 'erro' => $erro];
        }

        try {
            $this->service->editar($id, $usuarioId, $dados);
            return ['sucesso' => true, 'erro' => null];
        } catch (\PDOException $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao editar: ' . $e->getMessage()];
        }
    }

    public function excluir(int $id, int $usuarioId): array
    {
        try {
            $excluiu = $this->service->excluir($id, $usuarioId);
            if (!$excluiu) {
                return ['sucesso' => false, 'erro' => 'Fornecedor não encontrado ou você não tem permissão!'];
            }

            return ['sucesso' => true, 'erro' => null];
        } catch (\PDOException $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }
}
