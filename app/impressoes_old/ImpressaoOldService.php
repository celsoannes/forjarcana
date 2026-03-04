<?php

namespace App\ImpressoesOld;

use PDO;

class ImpressaoOldService
{
    private ImpressaoOldRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ImpressaoOldRepository($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId, int $impressoraId, int $resinaId, int $filamentoId): array
    {
        $impressoras = $this->repository->listarImpressorasPorUsuario($usuarioId);

        $impressoraEscolhida = null;
        foreach ($impressoras as $impressora) {
            if ((int) ($impressora['id'] ?? 0) === $impressoraId) {
                $impressoraEscolhida = $impressora;
                break;
            }
        }

        $material = null;
        $materialTipo = '';
        $materialId = 0;
        $resinas = [];
        $filamentos = [];

        if (is_array($impressoraEscolhida)) {
            $tipoImpressora = (string) ($impressoraEscolhida['tipo'] ?? '');
            if ($tipoImpressora === 'Resina') {
                $resinas = $this->repository->listarResinasPorUsuario($usuarioId);
                if ($resinaId > 0) {
                    $material = $this->repository->buscarResinaPorIdEUsuario($resinaId, $usuarioId);
                    if ($material) {
                        $materialTipo = 'resina';
                        $materialId = $resinaId;
                    }
                }
            } elseif ($tipoImpressora === 'FDM') {
                $filamentos = $this->repository->listarFilamentosPorUsuario($usuarioId);
                if ($filamentoId > 0) {
                    $material = $this->repository->buscarFilamentoPorIdEUsuario($filamentoId, $usuarioId);
                    if ($material) {
                        $materialTipo = 'filamento';
                        $materialId = $filamentoId;
                    }
                }
            }
        }

        $estudios = $this->repository->listarEstudiosPorUsuario($usuarioId);
        $colecoes = $this->repository->listarColecoesPorUsuario($usuarioId);

        return [
            'impressoras' => $impressoras,
            'impressora_escolhida' => $impressoraEscolhida,
            'material' => $material,
            'material_tipo' => $materialTipo,
            'material_id' => $materialId,
            'resinas' => $resinas,
            'filamentos' => $filamentos,
            'estudios' => $estudios,
            'colecoes' => $colecoes,
        ];
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'nome_original' => trim((string) ($post['nome_original'] ?? '')),
            'arquivo_impressao' => trim((string) ($post['arquivo_impressao'] ?? '')),
            'estudio_id' => trim((string) ($post['estudio_id'] ?? '')),
            'colecao_id' => trim((string) ($post['colecao_id'] ?? '')),
            'peso_material' => trim((string) ($post['peso_material'] ?? '')),
            'tempo_dias' => trim((string) ($post['tempo_dias'] ?? '')),
            'tempo_horas' => trim((string) ($post['tempo_horas'] ?? '')),
            'tempo_minutos' => trim((string) ($post['tempo_minutos'] ?? '')),
            'unidades_produzidas' => trim((string) ($post['unidades_produzidas'] ?? '')),
            'taxa_falha' => trim((string) ($post['taxa_falha'] ?? '')),
            'markup' => trim((string) ($post['markup'] ?? '5')),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
        ];
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, array $impressoraEscolhida, ?array $material, string $materialTipo, int $materialId): array
    {
        $nome = trim((string) ($post['nome'] ?? ''));
        $nomeOriginal = trim((string) ($post['nome_original'] ?? ''));
        $arquivoImpressao = trim((string) ($post['arquivo_impressao'] ?? ''));
        $imagemCapa = trim((string) ($post['imagem_capa'] ?? ''));
        $estudioId = (int) ($post['estudio_id'] ?? 0);
        $colecaoId = (int) ($post['colecao_id'] ?? 0);
        $tempoDias = (int) ($post['tempo_dias'] ?? 0);
        $tempoHoras = (int) ($post['tempo_horas'] ?? 0);
        $tempoMinutos = (int) ($post['tempo_minutos'] ?? 0);
        $tempoImpressao = ($tempoDias * 24 * 60) + ($tempoHoras * 60) + $tempoMinutos;
        $unidadesProduzidas = (int) ($post['unidades_produzidas'] ?? 1);
        $markup = (int) ($post['markup'] ?? 5);
        $taxaFalha = (int) ($post['taxa_falha'] ?? 15);
        $pesoMaterial = (int) ($post['peso_material'] ?? 0);

        if ($taxaFalha <= 0) {
            $taxaFalha = 15;
        }

        if (isset($files['imagem_capa']) && ($files['imagem_capa']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $imagemUpload = uploadImagem($files['imagem_capa'], $usuarioUuid, 'usuarios', null, 'impressao', false);
            if (!$imagemUpload) {
                return ['sucesso' => false, 'erro' => 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.'];
            }
            $imagemCapa = (string) $imagemUpload;
        }

        $camposFaltando = [];
        if ($markup <= 0) {
            $camposFaltando[] = 'Markup';
        }
        if ($taxaFalha <= 0) {
            $camposFaltando[] = 'Taxa de Falha';
        }

        if ($nome === '' || $tempoImpressao <= 0 || $unidadesProduzidas <= 0) {
            return ['sucesso' => false, 'erro' => 'Preencha todos os campos obrigatórios.'];
        }

        if (!empty($camposFaltando)) {
            return ['sucesso' => false, 'erro' => 'Preencha os campos obrigatórios: ' . implode(', ', $camposFaltando) . '.'];
        }

        try {
            $dadosInsert = [
                'nome' => $nome,
                'nome_original' => $nomeOriginal,
                'arquivo_impressao' => $arquivoImpressao,
                'impressora_id' => (int) ($impressoraEscolhida['id'] ?? 0),
                'material_id' => $materialId,
                'tempo_impressao' => $tempoImpressao,
                'imagem_capa' => $imagemCapa,
                'unidades_produzidas' => $unidadesProduzidas,
                'markup' => $markup,
                'taxa_falha' => $taxaFalha,
                'estudio_id' => $estudioId > 0 ? $estudioId : null,
                'colecao_id' => $colecaoId > 0 ? $colecaoId : null,
                'usuario_id' => $usuarioId,
                'peso_material' => $pesoMaterial,
            ];

            if ($materialTipo === 'filamento') {
                $this->repository->inserirImpressaoFilamento($dadosInsert);
            } elseif ($materialTipo === 'resina') {
                $this->repository->inserirImpressaoResina($dadosInsert);
            } else {
                return ['sucesso' => false, 'erro' => 'Material inválido para cadastro da impressão.'];
            }

            return ['sucesso' => true, 'erro' => ''];
        } catch (\PDOException $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar impressão: ' . $e->getMessage()];
        }
    }
}
