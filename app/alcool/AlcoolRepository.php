<?php

namespace App\Alcool;

use PDO;

class AlcoolRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirAlcool(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO alcool (usuario_id, nome, marca, preco_litro) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['marca'] ?? ''),
            (float) ($dados['preco_litro'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
