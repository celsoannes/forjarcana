<?php

namespace App\Produtos;

use PDO;

class ProdutoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT
                p.id,
                mp.id AS mapa_id,
                t.id AS torre_id,
                s.sku AS sku_codigo,
                c.nome AS categoria_nome,
                COALESCE(NULLIF(p.nome, ''), m.nome_original) AS miniatura_nome,
                p.markup,
                COALESCE(i_m.unidades_produzidas, i_t.unidades_produzidas, 0) AS unidades_produzidas,
                cu.custo_total AS custo_total,
                cu.custo_por_unidade AS custo_unidade,
                p.preco_lojista,
                p.preco_consumidor_final,
                p.lucro_lojista,
                p.lucro_consumidor_final,
                p.imagem_capa,
                p.data_cadastro
            FROM produtos p
            LEFT JOIN sku s ON s.produto_id = p.id
            LEFT JOIN categorias c ON c.id = p.categoria
            LEFT JOIN custos cu ON cu.produto_id = p.id
            LEFT JOIN miniaturas m ON m.produto_id = p.id
            LEFT JOIN mapas mp ON mp.produto_id = p.id AND mp.usuario_id = p.usuario_id
            LEFT JOIN torres t ON t.produto_id = p.id
            LEFT JOIN impressoes i_m ON i_m.id = m.id_impressao
            LEFT JOIN impressoes i_t ON i_t.id = t.id_impressao
            WHERE p.usuario_id = ?
            ORDER BY p.id DESC");
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obterCompatibilidadeCamposEdicao(): array
    {
        $temObservacoes = $this->temColuna('produtos', 'observacoes');
        $temMarkupLojista = $this->temColuna('produtos', 'markup_lojista');
        $temMarkupConsumidor = $this->temColuna('produtos', 'markup_consumidor_final');
        $temMarkup = $this->temColuna('produtos', 'markup');

        return [
            'tem_observacoes' => $temObservacoes,
            'tem_markup_lojista' => $temMarkupLojista,
            'tem_markup_consumidor_final' => $temMarkupConsumidor,
            'tem_markup' => $temMarkup,
        ];
    }

    public function buscarParaEdicao(int $id, int $usuarioId, array $compatibilidade): ?array
    {
        $selectObservacoes = !empty($compatibilidade['tem_observacoes']) ? 'p.observacoes' : 'NULL AS observacoes';
        $selectMarkupLojista = !empty($compatibilidade['tem_markup_lojista'])
            ? 'p.markup_lojista'
            : (!empty($compatibilidade['tem_markup']) ? 'p.markup AS markup_lojista' : '0 AS markup_lojista');
        $selectMarkupConsumidor = !empty($compatibilidade['tem_markup_consumidor_final'])
            ? 'p.markup_consumidor_final'
            : (!empty($compatibilidade['tem_markup']) ? 'p.markup AS markup_consumidor_final' : '0 AS markup_consumidor_final');

        $stmt = $this->pdo->prepare("SELECT
            p.id,
            p.descricao,
            {$selectObservacoes},
            {$selectMarkupLojista},
            {$selectMarkupConsumidor},
            p.preco_lojista,
            p.preco_consumidor_final,
            p.imagem_capa,
            c.custo_total,
            c.custo_por_unidade,
            s.sku AS sku_codigo,
            cg.nome AS categoria_nome,
            m.nome_original AS miniatura_nome
        FROM produtos p
        LEFT JOIN sku s ON s.produto_id = p.id
        LEFT JOIN categorias cg ON cg.id = p.categoria
        LEFT JOIN custos c ON c.produto_id = p.id
        LEFT JOIN miniaturas m ON m.produto_id = p.id
        WHERE p.id = ? AND p.usuario_id = ?
        LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }

    public function atualizarOuInserirCustos(int $produtoId, float $custoTotal, float $custoPorUnidade): void
    {
        $stmt = $this->pdo->prepare('UPDATE custos SET custo_total = ?, custo_por_unidade = ? WHERE produto_id = ?');
        $stmt->execute([$custoTotal, $custoPorUnidade, $produtoId]);

        if ($stmt->rowCount() < 1) {
            $stmtInsert = $this->pdo->prepare('INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)');
            $stmtInsert->execute([$produtoId, $custoTotal, $custoPorUnidade]);
        }
    }

    public function atualizarProdutoEdicao(int $produtoId, int $usuarioId, array $dados, array $compatibilidade): void
    {
        $setProduto = ['descricao = ?'];
        $valoresProduto = [$dados['descricao'] !== '' ? $dados['descricao'] : null];

        if (!empty($compatibilidade['tem_observacoes'])) {
            $setProduto[] = 'observacoes = ?';
            $valoresProduto[] = $dados['observacoes'] !== '' ? $dados['observacoes'] : null;
        }

        if (!empty($compatibilidade['tem_markup_lojista'])) {
            $setProduto[] = 'markup_lojista = ?';
            $valoresProduto[] = $dados['markup_lojista'];
        }

        if (!empty($compatibilidade['tem_markup_consumidor_final'])) {
            $setProduto[] = 'markup_consumidor_final = ?';
            $valoresProduto[] = $dados['markup_consumidor_final'];
        }

        if (!empty($compatibilidade['tem_markup']) && empty($compatibilidade['tem_markup_lojista']) && empty($compatibilidade['tem_markup_consumidor_final'])) {
            $setProduto[] = 'markup = ?';
            $valoresProduto[] = $dados['markup_consumidor_final'];
        }

        $setProduto[] = 'preco_lojista = ?';
        $valoresProduto[] = $dados['preco_lojista'];
        $setProduto[] = 'preco_consumidor_final = ?';
        $valoresProduto[] = $dados['preco_consumidor_final'];
        $valoresProduto[] = $produtoId;
        $valoresProduto[] = $usuarioId;

        $stmt = $this->pdo->prepare('UPDATE produtos SET ' . implode(', ', $setProduto) . ' WHERE id = ? AND usuario_id = ?');
        $stmt->execute($valoresProduto);
    }

    public function buscarResumoParaExclusao(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT p.id, p.imagem_capa, p.imagens, s.sku AS sku_codigo
            FROM produtos p
            LEFT JOIN sku s ON s.produto_id = p.id
            WHERE p.id = ? AND p.usuario_id = ?
            LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }

    public function buscarImagensTorres(int $produtoId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT capa, imagens FROM torres WHERE produto_id = ? AND usuario_id = ?');
        $stmt->execute([$produtoId, $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarImagensMapas(int $produtoId, int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT imagem_capa, imagens FROM mapas WHERE produto_id = ? AND usuario_id = ?');
        $stmt->execute([$produtoId, $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function excluirDependenciasPorProduto(int $produtoId, int $usuarioId, string $skuCodigo): void
    {
        if ($skuCodigo !== '') {
            $stmtMiniaturas = $this->pdo->prepare('DELETE FROM miniaturas WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)');
            $stmtMiniaturas->execute([$produtoId, $usuarioId, $skuCodigo]);

            $stmtTorres = $this->pdo->prepare('DELETE FROM torres WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)');
            $stmtTorres->execute([$produtoId, $usuarioId, $skuCodigo]);

            $stmtMapas = $this->pdo->prepare('DELETE FROM mapas WHERE produto_id = ? OR (usuario_id = ? AND id_sku = ?)');
            $stmtMapas->execute([$produtoId, $usuarioId, $skuCodigo]);
            return;
        }

        $stmtMiniaturas = $this->pdo->prepare('DELETE FROM miniaturas WHERE produto_id = ?');
        $stmtMiniaturas->execute([$produtoId]);

        $stmtTorres = $this->pdo->prepare('DELETE FROM torres WHERE produto_id = ?');
        $stmtTorres->execute([$produtoId]);

        $stmtMapas = $this->pdo->prepare('DELETE FROM mapas WHERE produto_id = ?');
        $stmtMapas->execute([$produtoId]);
    }

    public function excluirProduto(int $produtoId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$produtoId, $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    private function temColuna(string $tabela, string $coluna): bool
    {
        static $cache = [];
        $chave = $tabela . ':' . $coluna;

        if (array_key_exists($chave, $cache)) {
            return $cache[$chave];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
            $cache[$chave] = false;
            return false;
        }

        $sql = "SHOW COLUMNS FROM `{$tabela}` LIKE " . $this->pdo->quote($coluna);
        $stmt = $this->pdo->query($sql);
        $cache[$chave] = (bool) ($stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false);

        return $cache[$chave];
    }
}
