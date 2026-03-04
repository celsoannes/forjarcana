<?php

namespace App\Produtos;

use PDO;

class ProdutoController
{
    private ProdutoService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ProdutoService($pdo);
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

    public function excluir(int $id, int $usuarioId, string $raizProjeto): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.'];
        }

        if ($id <= 0) {
            return ['sucesso' => false, 'erro' => 'Produto inválido.'];
        }

        return $this->service->excluirComDependencias($id, $usuarioId, $raizProjeto);
    }

    public function buscarParaEdicao(int $id, int $usuarioId): ?array
    {
        if ($usuarioId <= 0 || $id <= 0) {
            return null;
        }

        return $this->service->buscarParaEdicao($id, $usuarioId);
    }

    public function processarEdicao(int $id, int $usuarioId, array $dados, array $compatibilidade): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário não autenticado.'];
        }

        if ($id <= 0) {
            return ['sucesso' => false, 'erro' => 'Produto inválido.'];
        }

        return $this->service->editar($id, $usuarioId, $dados, $compatibilidade);
    }
}
