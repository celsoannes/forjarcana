<?php

namespace App\Filamentos;

use PDO;

class FilamentoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserirFilamento(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO filamento (usuario_id, nome, marca, cor, tipo, preco_kilo, link_compra, capa, ultima_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['marca'] ?? ''),
            (string) ($dados['cor'] ?? ''),
            (string) ($dados['tipo'] ?? ''),
            (float) ($dados['preco_kilo'] ?? 0),
            (string) ($dados['link_compra'] ?? ''),
            !empty($dados['capa']) ? (string) $dados['capa'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
