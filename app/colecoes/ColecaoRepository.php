<?php

namespace App\Colecoes;

use PDO;

class ColecaoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarEstudiosPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE usuario_id = ? ORDER BY nome');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarEstudioPorNome(int $usuarioId, string $nome): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE nome = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$nome, $usuarioId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function inserirEstudio(int $usuarioId, string $nome): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO estudios (usuario_id, nome, site, ultima_atualizacao) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$usuarioId, $nome, '']);

        return (int) $this->pdo->lastInsertId();
    }

    public function inserirColecao(int $usuarioId, int $estudioId, string $nome): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO colecoes (usuario_id, estudio_id, nome, ultima_atualizacao) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$usuarioId, $estudioId, $nome]);

        return (int) $this->pdo->lastInsertId();
    }

    public function iniciarTransacao(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function confirmarTransacao(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function desfazerTransacao(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function emTransacao(): bool
    {
        return $this->pdo->inTransaction();
    }
}
