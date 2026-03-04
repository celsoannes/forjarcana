<?php

namespace App\Resinas;

use PDO;

class ResinaRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirResina(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO resinas (usuario_id, nome, marca, cor, preco_litro, ultima_atualizacao) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['marca'] ?? ''),
            (string) ($dados['cor'] ?? ''),
            (float) ($dados['preco_litro'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
