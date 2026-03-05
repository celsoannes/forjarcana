<?php

namespace App\Torres;

use PDO;

class TorreRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT
            t.id,
            t.nome_original,
            p.id AS produto_id,
            p.nome,
            p.imagem_capa,
            p.preco_lojista,
            p.preco_consumidor_final,
            p.data_cadastro
        FROM torres t
        INNER JOIN produtos p ON p.id = t.produto_id
        WHERE t.usuario_id = ?
        ORDER BY t.id DESC");
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarUuidUsuario(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT uuid FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $uuid = $stmt->fetchColumn();

        return $uuid !== false ? (string) $uuid : null;
    }

    public function buscarImpressora(int $impressoraId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, marca, modelo, tipo, potencia, fator_uso, custo_hora FROM impressoras WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$impressoraId, $usuarioId]);
        $impressora = $stmt->fetch(PDO::FETCH_ASSOC);

        return $impressora ?: null;
    }

    public function buscarResina(int $resinaId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, marca, cor, preco_litro FROM resinas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$resinaId, $usuarioId]);
        $resina = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resina ?: null;
    }

    public function buscarFilamento(int $filamentoId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, marca, cor, tipo, preco_kilo FROM filamento WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$filamentoId, $usuarioId]);
        $filamento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $filamento ?: null;
    }

    public function listarEstudiosPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE usuario_id = ? ORDER BY nome');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarColecoesPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT c.id, c.nome, e.nome AS estudio_nome, e.id AS estudio_id FROM colecoes c INNER JOIN estudios e ON e.id = c.estudio_id WHERE c.usuario_id = ? ORDER BY e.nome, c.nome');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarTematicas(): array
    {
        $stmt = $this->pdo->query('SELECT id, nome FROM tematicas ORDER BY nome');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function listarOutrasCaracteristicasMiniaturas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT outras_caracteristicas FROM miniaturas WHERE usuario_id = ? AND outras_caracteristicas IS NOT NULL AND outras_caracteristicas <> ''");
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function buscarValorKwhPorUsuario(int $usuarioId): float
    {
        $stmt = $this->pdo->prepare('SELECT valor_kwh FROM energia WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $linha ? (float) ($linha['valor_kwh'] ?? 1.0) : 1.0;
    }

    public function buscarPrecoLitroAlcoolPorUsuario(int $usuarioId): float
    {
        $stmt = $this->pdo->prepare('SELECT preco_litro FROM alcool WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $linha ? (float) ($linha['preco_litro'] ?? 0.0) : 0.0;
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
            t.id,
            t.produto_id,
            t.nome_original,
            p.nome,
            p.descricao,
            {$selectObservacoes},
            {$selectMarkupLojista},
            {$selectMarkupConsumidor},
            p.preco_lojista,
            p.preco_consumidor_final
        FROM torres t
        INNER JOIN produtos p ON p.id = t.produto_id
        WHERE t.id = ? AND t.usuario_id = ?
        LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $torre = $stmt->fetch(PDO::FETCH_ASSOC);

        return $torre ?: null;
    }

    public function buscarParaExclusao(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT t.id, p.id AS produto_id, p.imagem_capa, p.imagens
            FROM torres t
            INNER JOIN produtos p ON p.id = t.produto_id
            WHERE t.id = ? AND t.usuario_id = ?
            LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $torre = $stmt->fetch(PDO::FETCH_ASSOC);

        return $torre ?: null;
    }

    public function buscarParaVisualizacao(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT
            t.id,
            t.produto_id,
            t.id_impressao,
            t.nome_original,
            t.tematica,
            t.capa,
            t.imagens,
            t.outras_caracteristicas,
            p.nome AS produto_nome,
            p.descricao AS produto_descricao,
            p.preco_lojista,
            p.preco_consumidor_final,
            p.lucro_lojista,
            p.lucro_consumidor_final,
            p.markup,
            c.custo_total,
            c.custo_por_unidade,
            s.sku AS sku_codigo,
            i.tempo_impressao,
            i.unidades_produzidas,
            i.markup AS markup_impressao,
            i.taxa_falha,
            COALESCE(i.valor_energia, en.valor_kwh, 0) AS valor_energia,
            i.peso_material,
            i.custo_material,
            i.custo_lavagem_alcool,
            i.custo_energia,
            i.depreciacao,
            i.custo_total_impressao,
            i.custo_por_unidade AS custo_por_unidade_impressao,
            i.lucro_total_impressao,
            i.lucro_por_unidade,
            i.porcentagem_lucro,
            i.preco_venda_sugerido,
            i.preco_venda_sugerido_unidade,
            i.observacoes,
            i.impressora_id,
            i.filamento_id,
            i.resina_id,
            imp.marca AS impressora_marca,
            imp.modelo AS impressora_modelo,
            imp.tipo AS impressora_tipo,
            imp.potencia AS impressora_potencia,
            imp.fator_uso AS impressora_fator_uso,
            imp.custo_hora AS impressora_custo_hora,
            fil.nome AS filamento_nome,
            fil.marca AS filamento_marca,
            fil.cor AS filamento_cor,
            fil.tipo AS filamento_tipo,
            fil.preco_kilo AS filamento_preco_kilo,
            res.nome AS resina_nome,
            res.marca AS resina_marca,
            res.cor AS resina_cor,
            res.preco_litro AS resina_preco_litro,
            e.nome AS estudio_nome,
            co.nome AS colecao_nome,
            tm.nome AS tematica_nome
        FROM torres t
        INNER JOIN produtos p ON p.id = t.produto_id
        LEFT JOIN custos c ON c.produto_id = t.produto_id
        LEFT JOIN sku s ON s.produto_id = t.produto_id AND s.usuario_id = t.usuario_id
        LEFT JOIN impressoes i ON i.id = t.id_impressao
        LEFT JOIN energia en ON en.usuario_id = t.usuario_id
        LEFT JOIN impressoras imp ON imp.id = i.impressora_id
        LEFT JOIN filamento fil ON fil.id = i.filamento_id
        LEFT JOIN resinas res ON res.id = i.resina_id
        LEFT JOIN estudios e ON e.id = t.id_estudio
        LEFT JOIN colecoes co ON co.id = t.id_colecao
        LEFT JOIN tematicas tm ON tm.id = t.id_tematica
        WHERE t.id = ? AND t.usuario_id = ?
        LIMIT 1");
        $stmt->execute([$id, $usuarioId]);
        $torre = $stmt->fetch(PDO::FETCH_ASSOC);

        return $torre ?: null;
    }

    public function atualizarNomeOriginal(int $id, int $usuarioId, ?string $nomeOriginal): void
    {
        $stmt = $this->pdo->prepare('UPDATE torres SET nome_original = ? WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$nomeOriginal, $id, $usuarioId]);
    }

    public function atualizarMetadadosDaTorre(
        int $id,
        int $usuarioId,
        ?int $estudioId,
        ?int $colecaoId,
        ?int $tematicaId,
        ?string $tematicaNome,
        ?string $outrasCaracteristicas
    ): void {
        $stmt = $this->pdo->prepare('UPDATE torres SET id_estudio = ?, id_colecao = ?, id_tematica = ?, tematica = ?, outras_caracteristicas = ? WHERE id = ? AND usuario_id = ?');
        $stmt->execute([
            $estudioId,
            $colecaoId,
            $tematicaId,
            $tematicaNome,
            $outrasCaracteristicas,
            $id,
            $usuarioId,
        ]);
    }

    public function atualizarSkuDaTorre(int $id, int $usuarioId, string $sku): void
    {
        $stmt = $this->pdo->prepare('UPDATE torres SET id_sku = ? WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$sku, $id, $usuarioId]);
    }

    public function atualizarOuInserirSkuDoProduto(int $produtoId, int $usuarioId, string $sku): void
    {
        $stmtUpdate = $this->pdo->prepare('UPDATE sku SET sku = ? WHERE produto_id = ? AND usuario_id = ?');
        $stmtUpdate->execute([$sku, $produtoId, $usuarioId]);

        if ($stmtUpdate->rowCount() > 0) {
            return;
        }

        $stmtInsert = $this->pdo->prepare('INSERT INTO sku (produto_id, sku, usuario_id) VALUES (?, ?, ?)');
        $stmtInsert->execute([$produtoId, $sku, $usuarioId]);
    }

    public function atualizarProdutoDaTorre(int $produtoId, int $usuarioId, array $dados, array $compatibilidade): void
    {
        $temLucroLojista = $this->temColuna('produtos', 'lucro_lojista');
        $temLucroConsumidorFinal = $this->temColuna('produtos', 'lucro_consumidor_final');

        $setProduto = ['nome = ?', 'descricao = ?'];
        $valoresProduto = [
            $dados['nome'],
            $dados['descricao'] !== '' ? $dados['descricao'] : null,
        ];

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

        if ($temLucroLojista) {
            $setProduto[] = 'lucro_lojista = ?';
            $valoresProduto[] = (float) ($dados['lucro_lojista'] ?? 0);
        }

        if ($temLucroConsumidorFinal) {
            $setProduto[] = 'lucro_consumidor_final = ?';
            $valoresProduto[] = (float) ($dados['lucro_consumidor_final'] ?? 0);
        }

        $valoresProduto[] = $produtoId;
        $valoresProduto[] = $usuarioId;

        $stmt = $this->pdo->prepare('UPDATE produtos SET ' . implode(', ', $setProduto) . ' WHERE id = ? AND usuario_id = ?');
        $stmt->execute($valoresProduto);
    }

    public function atualizarMidiaDaTorre(int $id, int $usuarioId, ?string $capa, ?string $imagensJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE torres SET capa = ?, imagens = ? WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$capa, $imagensJson, $id, $usuarioId]);
    }

    public function atualizarMidiaDoProduto(int $produtoId, int $usuarioId, ?string $capa, ?string $imagensJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE produtos SET imagem_capa = ?, imagens = ? WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$capa, $imagensJson, $produtoId, $usuarioId]);
    }

    public function atualizarCustosDoProduto(int $produtoId, float $custoTotal, float $custoPorUnidade): void
    {
        $stmt = $this->pdo->prepare('UPDATE custos SET custo_total = ?, custo_por_unidade = ? WHERE produto_id = ?');
        $stmt->execute([$custoTotal, $custoPorUnidade, $produtoId]);
    }

    public function atualizarImpressaoDaTorre(int $impressaoId, int $usuarioId, array $dados): void
    {
        $stmt = $this->pdo->prepare('UPDATE impressoes SET
            tempo_impressao = ?,
            unidades_produzidas = ?,
            markup = ?,
            taxa_falha = ?,
            valor_energia = ?,
            peso_material = ?,
            custo_material = ?,
            custo_lavagem_alcool = ?,
            custo_energia = ?,
            depreciacao = ?,
            custo_total_impressao = ?,
            custo_por_unidade = ?,
            lucro_total_impressao = ?,
            lucro_por_unidade = ?,
            porcentagem_lucro = ?,
            preco_venda_sugerido = ?,
            preco_venda_sugerido_unidade = ?,
            observacoes = ?
            WHERE id = ? AND usuario_id = ?');

        $stmt->execute([
            $dados['tempo_impressao'],
            $dados['unidades_produzidas'],
            $dados['markup'],
            $dados['taxa_falha'],
            $dados['valor_energia'],
            $dados['peso_material'],
            $dados['custo_material'],
            $dados['custo_lavagem_alcool'],
            $dados['custo_energia'],
            $dados['depreciacao'],
            $dados['custo_total_impressao'],
            $dados['custo_por_unidade'],
            $dados['lucro_total_impressao'],
            $dados['lucro_por_unidade'],
            $dados['porcentagem_lucro'],
            $dados['preco_venda_sugerido'],
            $dados['preco_venda_sugerido_unidade'],
            $dados['observacoes'],
            $impressaoId,
            $usuarioId,
        ]);
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
