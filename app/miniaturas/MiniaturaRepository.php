<?php
namespace App\Miniaturas;

use PDO;

class MiniaturaRepository
{
    public function inserirMiniatura(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO miniaturas (id_sku, produto_id, usuario_id, id_impressao, nome_original, id_estudio, id_colecao, tematica, raca, classe, genero, criatura, papel, tamanho, base, pintada, arma_principal, arma_secundaria, armadura, outras_caracteristicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $params = [
            $dados['id_sku'] ?? null,
            $dados['produto_id'] ?? null,
            $dados['usuario_id'] ?? null,
            array_key_exists('id_impressao', $dados) ? $dados['id_impressao'] : null,
            $dados['nome_original'] ?? null,
            $dados['id_estudio'] ?? null,
            array_key_exists('id_colecao', $dados) ? $dados['id_colecao'] : null,
            array_key_exists('tematica', $dados) ? $dados['tematica'] : null,
            array_key_exists('raca', $dados) ? $dados['raca'] : null,
            array_key_exists('classe', $dados) ? $dados['classe'] : null,
            array_key_exists('genero', $dados) ? $dados['genero'] : null,
            array_key_exists('criatura', $dados) ? $dados['criatura'] : null,
            array_key_exists('papel', $dados) ? $dados['papel'] : null,
            array_key_exists('tamanho', $dados) ? $dados['tamanho'] : null,
            array_key_exists('base', $dados) ? $dados['base'] : null,
            array_key_exists('pintada', $dados) ? $dados['pintada'] : null,
            array_key_exists('arma_principal', $dados) ? $dados['arma_principal'] : null,
            array_key_exists('arma_secundaria', $dados) ? $dados['arma_secundaria'] : null,
            array_key_exists('armadura', $dados) ? $dados['armadura'] : null,
            array_key_exists('outras_caracteristicas', $dados) ? $dados['outras_caracteristicas'] : null,
        ];
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function inserirImpressao(array $dados): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO impressoes (
            produto_id, impressora_id, tempo_impressao, unidades_produzidas, markup, taxa_falha, valor_energia, peso_material, custo_material, custo_lavagem_alcool, custo_energia, depreciacao, custo_total_impressao, custo_por_unidade, lucro_total_impressao, lucro_por_unidade, porcentagem_lucro, preco_venda_sugerido, preco_venda_sugerido_unidade, observacoes, usuario_id, filamento_id, resina_id
        ) VALUES (
            :produto_id, :impressora_id, :tempo_impressao, :unidades_produzidas, :markup, :taxa_falha, :valor_energia, :peso_material, :custo_material, :custo_lavagem_alcool, :custo_energia, :depreciacao, :custo_total_impressao, :custo_por_unidade, :lucro_total_impressao, :lucro_por_unidade, :porcentagem_lucro, :preco_venda_sugerido, :preco_venda_sugerido_unidade, :observacoes, :usuario_id, :filamento_id, :resina_id
        )');
        $stmt->execute($dados);
        return (int) $this->pdo->lastInsertId();
    }

    public function inserirCusto(int $produtoId, float $custoTotal, float $custoPorUnidade): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)');
        $stmt->execute([$produtoId, $custoTotal, $custoPorUnidade]);
    }
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodas(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM miniaturas ORDER BY data_cadastro DESC, id DESC');
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM miniaturas WHERE id = ?');
        $stmt->execute([$id]);
        $miniatura = $stmt->fetch(PDO::FETCH_ASSOC);

        return $miniatura ?: null;
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->pdo->prepare('UPDATE miniaturas SET nome = ?, sku = ?, estudio = ?, tematica = ?, colecao = ?, raca = ?, classe = ?, genero = ?, criatura = ?, papel = ?, tamanho = ?, base = ?, material = ?, pintada = ?, arma_principal = ?, arma_secundaria = ?, armadura = ?, outras_caracteristicas = ?, foto = ? WHERE id = ?');
        $stmt->execute([
            $dados['nome'],
            $dados['sku'],
            $dados['estudio'],
            $dados['tematica'],
            $dados['colecao'],
            $dados['raca'],
            $dados['classe'],
            $dados['genero'],
            $dados['criatura'],
            $dados['papel'],
            $dados['tamanho'],
            $dados['base'],
            $dados['material'],
            $dados['pintada'],
            $dados['arma_principal'],
            $dados['arma_secundaria'],
            $dados['armadura'],
            $dados['outras_caracteristicas'],
            $dados['foto'],
            $id,
        ]);
    }

    public function excluirPorId(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM miniaturas WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function buscarUuidUsuario(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT uuid FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$usuarioId]);
        $uuid = $stmt->fetchColumn();

        return $uuid !== false ? (string) $uuid : null;
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

    public function listarRacasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT raca AS nome FROM miniaturas WHERE usuario_id = ? AND raca IS NOT NULL AND raca <> '' ORDER BY raca");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarClassesPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT classe AS nome FROM miniaturas WHERE usuario_id = ? AND classe IS NOT NULL AND classe <> '' ORDER BY classe");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarArmadurasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT armadura AS nome FROM miniaturas WHERE usuario_id = ? AND armadura IS NOT NULL AND armadura <> '' ORDER BY armadura");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarArmasPrincipaisPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT arma_principal AS nome FROM miniaturas WHERE usuario_id = ? AND arma_principal IS NOT NULL AND arma_principal <> '' ORDER BY arma_principal");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function listarArmasSecundariasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT arma_secundaria AS nome FROM miniaturas WHERE usuario_id = ? AND arma_secundaria IS NOT NULL AND arma_secundaria <> '' ORDER BY arma_secundaria");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function listarOutrasCaracteristicasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT outras_caracteristicas FROM miniaturas WHERE usuario_id = ? AND outras_caracteristicas IS NOT NULL AND outras_caracteristicas <> ''");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function buscarImpressora(int $impressoraId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, marca, modelo, tipo, potencia, fator_uso, custo_hora FROM impressoras WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$impressoraId, $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function buscarResina(int $resinaId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, marca, cor, preco_litro FROM resinas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$resinaId, $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function buscarFilamento(int $filamentoId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, marca, cor, tipo, preco_kilo FROM filamento WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$filamentoId, $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function sugerirColecoes(int $usuarioId, string $termo, string $estudioNome = ''): array
    {
        if ($estudioNome !== '') {
            $stmt = $this->pdo->prepare("SELECT DISTINCT c.nome
              FROM colecoes c
              INNER JOIN estudios e ON e.id = c.estudio_id
              WHERE c.usuario_id = ?
                AND c.nome LIKE ?
                AND LOWER(e.nome) = LOWER(?)
              ORDER BY c.nome ASC
              LIMIT 50");
            $stmt->execute([$usuarioId, '%' . $termo . '%', $estudioNome]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $stmt = $this->pdo->prepare("SELECT DISTINCT nome
          FROM colecoes
          WHERE usuario_id = ?
            AND nome LIKE ?
          ORDER BY nome ASC
          LIMIT 50");
        $stmt->execute([$usuarioId, '%' . $termo . '%']);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function sugerirOutrasCaracteristicas(int $usuarioId, string $termo): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT outras_caracteristicas FROM miniaturas WHERE usuario_id = ? AND outras_caracteristicas IS NOT NULL AND outras_caracteristicas <> '' AND outras_caracteristicas LIKE ? ORDER BY outras_caracteristicas ASC LIMIT 80");
        $stmt->execute([$usuarioId, '%' . $termo . '%']);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function contarSkuPorCodigo(string $sku): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sku WHERE sku = ?');
        $stmt->execute([$sku]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function buscarEstudioPorNome(int $usuarioId, string $nome): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE usuario_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1');
        $stmt->execute([$usuarioId, $nome]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function inserirEstudio(string $nome, int $usuarioId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO estudios (nome, site, usuario_id) VALUES (?, ?, ?)');
        $stmt->execute([$nome, 'https://pendente.local', $usuarioId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarColecaoPorNomeEEstudio(int $usuarioId, int $estudioId, string $nome): ?array
    {
        $stmt = $this->pdo->prepare("SELECT c.id, c.nome, c.estudio_id, e.nome AS estudio_nome
            FROM colecoes c
            INNER JOIN estudios e ON e.id = c.estudio_id
            WHERE c.usuario_id = ?
              AND c.estudio_id = ?
              AND LOWER(c.nome) = LOWER(?)
            LIMIT 1");
        $stmt->execute([$usuarioId, $estudioId, $nome]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function inserirColecao(int $estudioId, string $nome, int $usuarioId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO colecoes (estudio_id, nome, usuario_id) VALUES (?, ?, ?)');
        $stmt->execute([$estudioId, $nome, $usuarioId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarNomeEstudioPorId(int $estudioId): string
    {
        $stmt = $this->pdo->prepare('SELECT nome FROM estudios WHERE id = ? LIMIT 1');
        $stmt->execute([$estudioId]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    public function buscarTematicaPorNome(string $nome): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM tematicas WHERE LOWER(nome) = LOWER(?) LIMIT 1');
        $stmt->execute([$nome]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function inserirTematica(string $nome): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO tematicas (nome) VALUES (?)');
        $stmt->execute([$nome]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarEstudioPorId(int $estudioId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$estudioId, $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function buscarColecaoPorId(int $colecaoId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT c.id, c.nome, c.estudio_id, e.nome AS estudio_nome
            FROM colecoes c
            INNER JOIN estudios e ON e.id = c.estudio_id
            WHERE c.id = ?
              AND c.usuario_id = ?
            LIMIT 1");
        $stmt->execute([$colecaoId, $usuarioId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function buscarTematicaPorId(int $tematicaId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM tematicas WHERE id = ? LIMIT 1');
        $stmt->execute([$tematicaId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    public function vincularMiniaturaColecao(int $miniaturaId, int $colecaoId, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO miniaturas_colecoes (miniatura_id, colecao_id, usuario_id) VALUES (?, ?, ?)');
        $stmt->execute([$miniaturaId, $colecaoId, $usuarioId]);
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

    public function inserirProduto(array $dados): int
    {
        $dadosIndexado = array_values($dados);
        $dadosString = array_map(fn($v) => is_null($v) ? null : (string)$v, $dadosIndexado);
        $sql = 'INSERT INTO produtos (usuario_id, nome, categoria, imagem_capa, imagens, descricao, markup, lucro_lojista, lucro_consumidor_final, preco_lojista, preco_consumidor_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        error_log('DEBUG inserirProduto (Repository) SQL: ' . $sql);
        error_log('DEBUG inserirProduto (Repository) count: ' . count($dadosString) . ' | conteudo: ' . var_export($dadosString, true));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($dadosString);

        return (int) $this->pdo->lastInsertId();
    }

    public function inserirSku(int $produtoId, string $skuCodigo, int $usuarioId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO sku (produto_id, sku, usuario_id) VALUES (?, ?, ?)');
        $stmt->execute([$produtoId, $skuCodigo, $usuarioId]);
    }


    public function buscarValorKwhPorUsuario(int $usuarioId): ?float
    {
        $stmt = $this->pdo->prepare('SELECT valor_kwh FROM energia WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $valor = $stmt->fetchColumn();

        if ($valor === false) {
            return null;
        }

        return (float) $valor;
    }

    public function buscarPrecoLitroAlcoolPorUsuario(int $usuarioId): ?float
    {
        $stmt = $this->pdo->prepare('SELECT preco_litro FROM alcool WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        $valor = $stmt->fetchColumn();

        if ($valor === false) {
            return null;
        }

        return (float) $valor;
    }
}
