<?php

namespace App\Estudios;

use PDO;

class EstudioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirEstudio(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO estudios (usuario_id, nome, site, ultima_atualizacao) VALUES (?, ?, ?, NOW())');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['site'] ?? ''),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
