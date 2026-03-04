<?php

namespace App\Usuarios;

use PDO;

class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function inserir(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, sobrenome, email, senha, cargo, celular, cpf, data_expiracao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $dados['nome'],
            $dados['sobrenome'],
            $dados['email'],
            $dados['senha_hash'],
            $dados['cargo'],
            $dados['celular'],
            $dados['cpf'],
            $dados['data_expiracao'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados, ?string $senhaHash = null): void
    {
        if ($senhaHash !== null) {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, senha = ?, cargo = ?, celular = ?, cpf = ?, data_expiracao = ? WHERE id = ?');
            $stmt->execute([
                $dados['nome'],
                $dados['sobrenome'],
                $dados['email'],
                $senhaHash,
                $dados['cargo'],
                $dados['celular'],
                $dados['cpf'],
                $dados['data_expiracao'],
                $id,
            ]);

            return;
        }

        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, cargo = ?, celular = ?, cpf = ?, data_expiracao = ? WHERE id = ?');
        $stmt->execute([
            $dados['nome'],
            $dados['sobrenome'],
            $dados['email'],
            $dados['cargo'],
            $dados['celular'],
            $dados['cpf'],
            $dados['data_expiracao'],
            $id,
        ]);
    }

    public function buscarUuidPorId(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT uuid FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $uuid = $stmt->fetchColumn();

        return $uuid !== false ? (string) $uuid : null;
    }

    public function atualizarFoto(int $id, string $fotoNome): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET foto = ? WHERE id = ?');
        $stmt->execute([$fotoNome, $id]);
    }

    public function listarTodosOrdenadosPorNome(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome, sobrenome, email, cargo, celular, cpf, foto, data_expiracao FROM usuarios ORDER BY nome, sobrenome');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function excluirPorId(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
    }
}
