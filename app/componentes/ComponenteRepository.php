<?php

namespace App\Componentes;

use PDO;

class ComponenteRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirComponente(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO componentes (usuario_id, nome_material, tipo_material, descricao, unidade_medida, valor_unitario, fornecedor, observacoes, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome_material'] ?? ''),
            (string) ($dados['tipo_material'] ?? ''),
            (string) ($dados['descricao'] ?? ''),
            (string) ($dados['unidade_medida'] ?? ''),
            (float) ($dados['valor_unitario'] ?? 0),
            (string) ($dados['fornecedor'] ?? ''),
            (string) ($dados['observacoes'] ?? ''),
            (string) ($dados['imagem'] ?? ''),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
