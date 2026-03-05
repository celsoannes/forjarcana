<?php

namespace App\Impressoras3d;

use PDO;

class Impressora3dRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function inserirImpressora(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO impressoras (usuario_id, marca, modelo, tipo, preco_aquisicao, potencia, depreciacao, tempo_vida_util, capa, ultima_atualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            (int) ($dados['usuario_id'] ?? 0),
            (string) ($dados['marca'] ?? ''),
            (string) ($dados['modelo'] ?? ''),
            (string) ($dados['tipo'] ?? ''),
            (float) ($dados['preco_aquisicao'] ?? 0),
            (int) ($dados['potencia'] ?? 0),
            (int) ($dados['depreciacao'] ?? 0),
            (int) ($dados['tempo_vida_util'] ?? 0),
            isset($dados['capa']) ? $dados['capa'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
