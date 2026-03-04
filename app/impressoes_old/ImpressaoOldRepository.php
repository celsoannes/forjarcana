<?php

namespace App\ImpressoesOld;

use PDO;

class ImpressaoOldRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarImpressorasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM impressoras WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarResinaPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM resinas WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$id, $usuarioId]);
        $resina = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resina ?: null;
    }

    public function buscarFilamentoPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM filamento WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$id, $usuarioId]);
        $filamento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $filamento ?: null;
    }

    public function listarResinasPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM resinas WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarFilamentosPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM filamento WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarEstudiosPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarColecoesPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome FROM colecoes WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function inserirImpressaoFilamento(array $dados): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO impressoes (nome, nome_original, arquivo_impressao, impressora_id, filamento_id, tempo_impressao, imagem_capa, unidades_produzidas, markup, taxa_falha, estudio_id, colecao_id, usuario_id, peso_material) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $dados['nome'],
            $dados['nome_original'],
            $dados['arquivo_impressao'],
            $dados['impressora_id'],
            $dados['material_id'],
            $dados['tempo_impressao'],
            $dados['imagem_capa'],
            $dados['unidades_produzidas'],
            $dados['markup'],
            $dados['taxa_falha'],
            $dados['estudio_id'],
            $dados['colecao_id'],
            $dados['usuario_id'],
            $dados['peso_material'],
        ]);
    }

    public function inserirImpressaoResina(array $dados): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO impressoes (nome, nome_original, arquivo_impressao, impressora_id, resina_id, tempo_impressao, imagem_capa, unidades_produzidas, markup, taxa_falha, estudio_id, colecao_id, usuario_id, peso_material) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $dados['nome'],
            $dados['nome_original'],
            $dados['arquivo_impressao'],
            $dados['impressora_id'],
            $dados['material_id'],
            $dados['tempo_impressao'],
            $dados['imagem_capa'],
            $dados['unidades_produzidas'],
            $dados['markup'],
            $dados['taxa_falha'],
            $dados['estudio_id'],
            $dados['colecao_id'],
            $dados['usuario_id'],
            $dados['peso_material'],
        ]);
    }
}
