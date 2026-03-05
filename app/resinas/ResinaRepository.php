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
        $stmt = $this->pdo->prepare('INSERT INTO resinas (usuario_id, nome, marca, cor, preco_kilo, link_compra, capa, ultima_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['marca'] ?? ''),
            (string) ($dados['cor'] ?? ''),
            (float) ($dados['preco_kilo'] ?? 0),
            (string) ($dados['link_compra'] ?? ''),
            (string) ($dados['capa'] ?? null),
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
