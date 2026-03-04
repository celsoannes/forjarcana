<?php

namespace App\Mapas;

use PDO;

class MapaRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarUuidUsuario(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT uuid FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $uuid = $stmt->fetchColumn();

        return $uuid !== false ? (string) $uuid : null;
    }

    public function listarFornecedoresPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome_fantasia FROM fornecedores WHERE usuario_id = ? ORDER BY nome_fantasia');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function sugerirFornecedores(int $usuarioId, string $termo): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT nome_fantasia FROM fornecedores WHERE usuario_id = ? AND nome_fantasia IS NOT NULL AND nome_fantasia <> '' AND nome_fantasia LIKE ? ORDER BY nome_fantasia ASC LIMIT 30");
        $stmt->execute([$usuarioId, '%' . $termo . '%']);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function iniciarTransacao(): void
    {
        $this->pdo->beginTransaction();
    }

    public function confirmarTransacao(): void
    {
        $this->pdo->commit();
    }

    public function desfazerTransacao(): void
    {
        $this->pdo->rollBack();
    }

    public function emTransacao(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function buscarFornecedorIdPorNome(int $usuarioId, string $nomeFantasia): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM fornecedores WHERE usuario_id = ? AND LOWER(nome_fantasia) = LOWER(?) LIMIT 1');
        $stmt->execute([$usuarioId, $nomeFantasia]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function tabelaTemColuna(string $tabela, string $coluna): bool
    {
        $stmt = $this->pdo->prepare('SHOW COLUMNS FROM ' . $tabela . ' LIKE ?');
        $stmt->execute([$coluna]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarCategoriaIdPorNome(string $nome): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM categorias WHERE nome = ? LIMIT 1');
        $stmt->execute([$nome]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function inserirCategoria(string $nome): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categorias (nome) VALUES (?)');
        $stmt->execute([$nome]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listarColunasProdutos(): array
    {
        $stmt = $this->pdo->query('SHOW COLUMNS FROM produtos');
        $colunasRaw = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $colunasMap = [];
        foreach ($colunasRaw as $colunaProduto) {
            $campo = (string) ($colunaProduto['Field'] ?? '');
            if ($campo !== '') {
                $colunasMap[$campo] = true;
            }
        }

        return $colunasMap;
    }

    public function inserirProduto(array $colunas, array $valores): int
    {
        $placeholders = implode(', ', array_fill(0, count($colunas), '?'));
        $sql = 'INSERT INTO produtos (' . implode(', ', $colunas) . ') VALUES (' . $placeholders . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);

        return (int) $this->pdo->lastInsertId();
    }

    public function inserirSku(int $produtoId, string $skuCodigo, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO sku (produto_id, sku, usuario_id) VALUES (?, ?, ?)');
        $stmt->execute([$produtoId, $skuCodigo, $usuarioId]);
    }

    public function inserirCusto(int $produtoId, float $custoTotal, float $custoPorUnidade): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)');
        $stmt->execute([$produtoId, $custoTotal, $custoPorUnidade]);
    }

    public function inserirMapa(array $colunas, array $valores): void
    {
        $placeholders = implode(', ', array_fill(0, count($colunas), '?'));
        $sql = 'INSERT INTO mapas (' . implode(', ', $colunas) . ', ultima_atualizacao) VALUES (' . $placeholders . ', NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
    }

    public function contarSkuPorCodigo(string $sku): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sku WHERE sku = ?');
        $stmt->execute([$sku]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
