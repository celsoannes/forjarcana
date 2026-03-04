<?php

namespace App\Energia;

use PDO;

class EnergiaRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirEnergia(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO energia (usuario_id, prestadora, valor_ultima_conta, energia_eletrica) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['prestadora'] ?? ''),
            (float) ($dados['valor_ultima_conta'] ?? 0),
            (float) ($dados['energia_eletrica'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
