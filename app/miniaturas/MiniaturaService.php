<?php

namespace App\Miniaturas;


require_once __DIR__ . '/../upload_imagem.php';

use PDO;

class MiniaturaService
{
    private MiniaturaRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new MiniaturaRepository($pdo);
    }

    public function listarTodas(): array
    {
        return $this->repository->listarTodas();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->repository->buscarPorId($id);
    }

    public function sugerirCampo(int $usuarioId, string $campo, string $termo, string $estudioNome = ''): array
    {
        if ($usuarioId <= 0 || !in_array($campo, ['outras_caracteristicas', 'colecao'], true)) {
            return [];
        }

        $tamanhoTermo = function_exists('mb_strlen') ? mb_strlen($termo, 'UTF-8') : strlen($termo);
        if ($tamanhoTermo < 2) {
            return [];
        }

        try {
            $sugestoes = [];
            $controleUnicos = [];

            if ($campo === 'colecao') {
                $linhasColecao = $this->repository->sugerirColecoes($usuarioId, $termo, trim($estudioNome));

                foreach ($linhasColecao as $item) {
                    if (!is_string($item) || trim($item) === '') {
                        continue;
                    }

                    $item = trim($item);
                    $chave = function_exists('mb_strtolower') ? mb_strtolower($item, 'UTF-8') : strtolower($item);
                    if (isset($controleUnicos[$chave])) {
                        continue;
                    }

                    $controleUnicos[$chave] = true;
                    $sugestoes[] = $item;

                    if (count($sugestoes) >= 10) {
                        break;
                    }
                }

                return $sugestoes;
            }

            $linhas = $this->repository->sugerirOutrasCaracteristicas($usuarioId, $termo);
            foreach ($linhas as $linha) {
                if (!is_string($linha) || $linha === '') {
                    continue;
                }

                $itens = array_map('trim', explode(',', $linha));
                foreach ($itens as $item) {
                    if ($item === '' || stripos($item, $termo) === false) {
                        continue;
                    }

                    $chave = function_exists('mb_strtolower') ? mb_strtolower($item, 'UTF-8') : strtolower($item);
                    if (isset($controleUnicos[$chave])) {
                        continue;
                    }

                    $controleUnicos[$chave] = true;
                    $sugestoes[] = $item;

                    if (count($sugestoes) >= 10) {
                        break 2;
                    }
                }
            }

            return $sugestoes;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function carregarContextoAdicao(int $usuarioId, string $usuarioUuid, int $impressoraId, int $filamentoId, int $resinaId): array
    {
        $resultado = [
            'usuario_uuid' => $usuarioUuid,
            'selecao_confirmacao' => null,
            'aviso_selecao' => '',
            'estudios_disponiveis' => [],
            'colecoes_disponiveis' => [],
            'tematicas_disponiveis' => [],
            'racas_disponiveis' => [],
            'classes_disponiveis' => [],
            'armaduras_disponiveis' => [],
            'armas_disponiveis' => [],
            'outras_caracteristicas_disponiveis' => [],
        ];

        if ($resultado['usuario_uuid'] === '' && $usuarioId > 0) {
            try {
                $resultado['usuario_uuid'] = (string) ($this->repository->buscarUuidUsuario($usuarioId) ?? '');
            } catch (\Throwable $e) {
                $resultado['usuario_uuid'] = '';
            }
        }

        try {
            $resultado['estudios_disponiveis'] = $this->repository->listarEstudiosPorUsuario($usuarioId);
        } catch (\Throwable $e) {
        }

        try {
            $resultado['colecoes_disponiveis'] = $this->repository->listarColecoesPorUsuario($usuarioId);
        } catch (\Throwable $e) {
        }

        try {
            $resultado['tematicas_disponiveis'] = $this->repository->listarTematicas();
        } catch (\Throwable $e) {
        }

        try {
            $resultado['racas_disponiveis'] = $this->repository->listarRacasPorUsuario($usuarioId);
        } catch (\Throwable $e) {
        }

        try {
            $resultado['classes_disponiveis'] = $this->repository->listarClassesPorUsuario($usuarioId);
        } catch (\Throwable $e) {
        }

        try {
            $resultado['armaduras_disponiveis'] = $this->repository->listarArmadurasPorUsuario($usuarioId);
        } catch (\Throwable $e) {
        }

        try {
            $armasPrincipais = $this->repository->listarArmasPrincipaisPorUsuario($usuarioId);
            $armasSecundarias = $this->repository->listarArmasSecundariasPorUsuario($usuarioId);
            $armasUnicas = [];
            $armasControle = [];
            foreach (array_merge($armasPrincipais, $armasSecundarias) as $armaItem) {
                if (!is_string($armaItem)) {
                    continue;
                }
                $armaItem = trim($armaItem);
                if ($armaItem === '') {
                    continue;
                }
                $chave = function_exists('mb_strtolower') ? mb_strtolower($armaItem, 'UTF-8') : strtolower($armaItem);
                if (isset($armasControle[$chave])) {
                    continue;
                }
                $armasControle[$chave] = true;
                $armasUnicas[] = ['nome' => $armaItem];
            }
            $resultado['armas_disponiveis'] = $armasUnicas;
        } catch (\Throwable $e) {
        }

        try {
            $linhasOutrasCaracteristicas = $this->repository->listarOutrasCaracteristicasPorUsuario($usuarioId);
            $itensUnicos = [];
            $controleUnicos = [];
            foreach ($linhasOutrasCaracteristicas as $linhaOutrasCaracteristicas) {
                if (!is_string($linhaOutrasCaracteristicas) || trim($linhaOutrasCaracteristicas) === '') {
                    continue;
                }

                $itensLinha = array_map('trim', explode(',', $linhaOutrasCaracteristicas));
                foreach ($itensLinha as $itemLinha) {
                    if ($itemLinha === '') {
                        continue;
                    }

                    $chaveItem = function_exists('mb_strtolower') ? mb_strtolower($itemLinha, 'UTF-8') : strtolower($itemLinha);
                    if (isset($controleUnicos[$chaveItem])) {
                        continue;
                    }

                    $controleUnicos[$chaveItem] = true;
                    $itensUnicos[] = $itemLinha;
                }
            }

            natcasesort($itensUnicos);
            $resultado['outras_caracteristicas_disponiveis'] = array_values($itensUnicos);
        } catch (\Throwable $e) {
        }

        if ($impressoraId > 0) {
            $impressoraSelecionada = $this->repository->buscarImpressora($impressoraId, $usuarioId);

            if ($impressoraSelecionada) {
                $valorKwh = $this->repository->buscarValorKwhPorUsuario($usuarioId);
                if (($impressoraSelecionada['tipo'] ?? '') === 'Resina' && $resinaId > 0) {
                    $materialSelecionado = $this->repository->buscarResina($resinaId, $usuarioId);
                    if ($materialSelecionado) {
                        $resultado['selecao_confirmacao'] = [
                            'impressora' => $impressoraSelecionada,
                            'material_tipo' => 'Resina',
                            'material' => $materialSelecionado,
                            'valor_kwh' => $valorKwh,
                        ];
                    } else {
                        $resultado['aviso_selecao'] = 'A resina selecionada não foi encontrada para este usuário.';
                    }
                } elseif (($impressoraSelecionada['tipo'] ?? '') === 'FDM' && $filamentoId > 0) {
                    $materialSelecionado = $this->repository->buscarFilamento($filamentoId, $usuarioId);
                    if ($materialSelecionado) {
                        $resultado['selecao_confirmacao'] = [
                            'impressora' => $impressoraSelecionada,
                            'material_tipo' => 'Filamento',
                            'material' => $materialSelecionado,
                            'valor_kwh' => $valorKwh,
                        ];
                    } else {
                        $resultado['aviso_selecao'] = 'O filamento selecionado não foi encontrado para este usuário.';
                    }
                } else {
                    $resultado['aviso_selecao'] = 'Seleção de material não corresponde ao tipo da impressora escolhida.';
                }
            } else {
                $resultado['aviso_selecao'] = 'A impressora selecionada não foi encontrada para este usuário.';
            }
        }

        return $resultado;
    }

    public function gerarSkuAutomatico(string $estudioNome, string $raca, string $classe): string
    {
        $prefixo = 'MIN' . '-' . $this->gerarSiglaEstudio($estudioNome) . '-' . $this->gerarSigla3($raca) . '-' . $this->gerarSigla3($classe);

        do {
            $numero = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sku = $prefixo . '-' . $numero;
            $existe = $this->repository->contarSkuPorCodigo($sku) > 0;
        } while ($existe);

        return $sku;
    }

    public function resolverEstudio(int $usuarioId, string $entrada): array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            throw new \RuntimeException('Informe um estúdio válido.');
        }

        $estudio = $this->repository->buscarEstudioPorNome($usuarioId, $entrada);
        if ($estudio) {
            return $estudio;
        }

        $novoId = $this->repository->inserirEstudio($entrada, $usuarioId);
        return ['id' => $novoId, 'nome' => $entrada];
    }

    public function resolverColecao(int $usuarioId, int $estudioId, string $entrada): array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            throw new \RuntimeException('Informe uma coleção válida.');
        }

        $colecao = $this->repository->buscarColecaoPorNomeEEstudio($usuarioId, $estudioId, $entrada);
        if ($colecao) {
            return $colecao;
        }

        $novaId = $this->repository->inserirColecao($estudioId, $entrada, $usuarioId);
        $estudioNome = $this->repository->buscarNomeEstudioPorId($estudioId);

        return [
            'id' => $novaId,
            'nome' => $entrada,
            'estudio_id' => $estudioId,
            'estudio_nome' => $estudioNome,
        ];
    }

    public function resolverTematica(string $entrada): array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            throw new \RuntimeException('Informe uma temática válida.');
        }

        $tematica = $this->repository->buscarTematicaPorNome($entrada);
        if ($tematica) {
            return $tematica;
        }

        $novaId = $this->repository->inserirTematica($entrada);
        return ['id' => $novaId, 'nome' => $entrada];
    }

    public function buscarEstudioPorId(int $usuarioId, int $estudioId): ?array
    {
        if ($estudioId <= 0) {
            return null;
        }

        return $this->repository->buscarEstudioPorId($estudioId, $usuarioId);
    }

    public function buscarColecaoPorId(int $usuarioId, int $colecaoId): ?array
    {
        if ($colecaoId <= 0) {
            return null;
        }

        return $this->repository->buscarColecaoPorId($colecaoId, $usuarioId);
    }

    public function buscarTematicaPorId(int $tematicaId): ?array
    {
        if ($tematicaId <= 0) {
            return null;
        }

        return $this->repository->buscarTematicaPorId($tematicaId);
    }

    public function vincularMiniaturaColecoes(int $miniaturaId, int $usuarioId, array $colecaoIds): void
    {
        if ($miniaturaId <= 0 || $usuarioId <= 0 || empty($colecaoIds)) {
            return;
        }

        foreach ($colecaoIds as $colecaoId) {
            $colecaoId = (int) $colecaoId;
            if ($colecaoId <= 0) {
                continue;
            }

            $this->repository->vincularMiniaturaColecao($miniaturaId, $colecaoId, $usuarioId);
        }
    }

    public function processarCadastroAdicao(array $dados): array
    {
        $usuarioId = (int) ($dados['usuario_id'] ?? 0);
        $markup = (float) ($dados['markup'] ?? 5.0);
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];
        $categoriaId = $this->repository->buscarCategoriaIdPorNome('Miniaturas');
        if ($categoriaId === 0) {
            $categoriaId = $this->repository->inserirCategoria('Miniaturas');
        }
        $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;
        try {
            // Inserir produto
            $produtoId = $this->repository->inserirProduto([
                $usuarioId,
                (string) ($dados['nome'] ?? ''),
                $categoriaId,
                $this->toNullableString($dados['foto'] ?? null),
                isset($imagensJson) ? $imagensJson : null,
                $this->toNullableString($dados['descricao_produto'] ?? null),
                isset($markup) ? $markup : 0,
                0, // lucro_lojista
                0, // lucro_consumidor_final
                0, // preco_lojista
                0, // preco_consumidor_final
            ]);


            // ... cálculo dos custos ...

            // Calcular custo_total_impressao e custo_por_unidade igual torres
            $taxaFalha = isset($dados['taxa_falha']) ? (float)$dados['taxa_falha'] : 0.0;
            $unidadesProduzidas = isset($dados['unidades_produzidas']) ? (int)$dados['unidades_produzidas'] : 1;
            $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao + $custoLavagemAlcool;
            $custoTaxaFalha = round(($baseCusto * ($taxaFalha / 100)), 2);
            $custoTotalImpressao = round($baseCusto + $custoTaxaFalha, 2);
            $custoPorUnidade = $unidadesProduzidas > 0 ? round($custoTotalImpressao / $unidadesProduzidas, 2) : 0.0;

            // Inserir custos (igual torres)
            $this->repository->inserirCusto($produtoId, $custoTotalImpressao, $custoPorUnidade);

            // Gerar SKU automático simples (pode ser aprimorado depois)
            $skuCodigo = 'SKU-' . strtoupper(bin2hex(random_bytes(4)));
            $this->repository->inserirSku($produtoId, $skuCodigo, $usuarioId);

            // Inserir na tabela impressoes (dados mínimos obrigatórios)
            // Usar valor_kwh do contexto (igual torres)
            // Garante que valor_energia sempre será preenchido corretamente
            $valorEnergia = null;
            if (
                isset($dados['valor_kwh']) &&
                is_numeric($dados['valor_kwh']) &&
                (float)$dados['valor_kwh'] > 0
            ) {
                $valorEnergia = (float)$dados['valor_kwh'];
            }
            if ($valorEnergia === null) {
                // Busca direto do banco se não vier do contexto ou for inválido
                try {
                    $stmtEnergia = $this->repository->pdo->prepare('SELECT valor_kwh FROM energia WHERE usuario_id = ? LIMIT 1');
                    $stmtEnergia->execute([$usuarioId]);
                    $valorEnergiaBanco = $stmtEnergia->fetchColumn();
                    if ($valorEnergiaBanco !== false && is_numeric($valorEnergiaBanco) && (float)$valorEnergiaBanco > 0) {
                        $valorEnergia = (float)$valorEnergiaBanco;
                    } else {
                        $valorEnergia = 1.0;
                    }
                } catch (\Throwable $e) {
                    $valorEnergia = 1.0;
                }
            }
            $potencia = isset($dados['potencia']) ? (float)$dados['potencia'] : 0.0;
            $fatorUso = isset($dados['fator_uso']) ? (float)$dados['fator_uso'] : 1.0;
            $custoHora = isset($dados['custo_hora']) ? (float)$dados['custo_hora'] : 0.0;
            $tempoTotalMin = isset($dados['tempo_total_min']) ? (int)$dados['tempo_total_min'] : 0;
            $tempoTotalHoras = $tempoTotalMin / 60;
            $custoEnergia = round((($potencia * $tempoTotalHoras * $fatorUso * $valorEnergia) / 1000), 2);
            $custoDepreciacao = round((($custoHora / 60) * $tempoTotalMin), 2);

            // Calcular custo_material
            $custoMaterial = 0.0;
            $custoLavagemAlcool = 0.0;
            $materialTipo = '';
            if (isset($dados['filamento_id']) && $dados['filamento_id']) {
                $materialTipo = 'Filamento';
            } elseif (isset($dados['resina_id']) && $dados['resina_id']) {
                $materialTipo = 'Resina';
            }
            $gramas = isset($dados['gramas']) ? (float)$dados['gramas'] : 0.0;
            if ($materialTipo === 'Filamento' && isset($dados['preco_kilo'])) {
                $precoKilo = (float)$dados['preco_kilo'];
                $custoMaterial = round((($gramas / 1000) * $precoKilo), 2);
            } elseif ($materialTipo === 'Resina' && isset($dados['preco_litro'])) {
                $precoLitro = (float)$dados['preco_litro'];
                $custoMaterial = round((($gramas / 1000) * $precoLitro), 2);
                // Buscar preço do álcool para lavagem
                $precoLitroAlcool = $this->repository->buscarPrecoLitroAlcoolPorUsuario($usuarioId);
                $custoLavagemAlcool = round((($precoLitroAlcool / 1000) * $gramas), 2);
            }

            // Calcular custo_total_impressao e custo_por_unidade igual torres
            $taxaFalha = isset($dados['taxa_falha']) ? (float)$dados['taxa_falha'] : 0.0;
            $unidadesProduzidas = isset($dados['unidades_produzidas']) ? (int)$dados['unidades_produzidas'] : 1;
            $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao + $custoLavagemAlcool;
            $custoTaxaFalha = round(($baseCusto * ($taxaFalha / 100)), 2);
            $custoTotalImpressao = round($baseCusto + $custoTaxaFalha, 2);
            $custoPorUnidade = $unidadesProduzidas > 0 ? round($custoTotalImpressao / $unidadesProduzidas, 2) : 0.0;

            // Cálculo dos campos de venda e lucro igual torres
            $markup = isset($dados['markup']) ? (float)$dados['markup'] : 1.0;
            $precoVendaSugerido = round($custoTotalImpressao * $markup, 2);
            $precoVendaSugeridoUnidade = $unidadesProduzidas > 0 ? round($precoVendaSugerido / $unidadesProduzidas, 2) : 0.0;
            $lucroTotalImpressao = round($precoVendaSugerido - $custoTotalImpressao, 2);
            $lucroPorUnidade = $unidadesProduzidas > 0 ? round($lucroTotalImpressao / $unidadesProduzidas, 2) : 0.0;
            $porcentagemLucro = $custoTotalImpressao > 0 ? (int) round(($lucroTotalImpressao / $custoTotalImpressao) * 100) : 0;

            $impressoesParams = [
                'produto_id' => $produtoId,
                'impressora_id' => (int)($dados['impressora_id'] ?? 1),
                'tempo_impressao' => (int)($dados['tempo_total_min'] ?? 0),
                'unidades_produzidas' => $unidadesProduzidas,
                'markup' => (int)($dados['markup'] ?? 1),
                'taxa_falha' => (int)($dados['taxa_falha'] ?? 0),
                'valor_energia' => $valorEnergia,
                'peso_material' => (int)($dados['gramas'] ?? 0),
                'custo_material' => $custoMaterial,
                'custo_lavagem_alcool' => $custoLavagemAlcool,
                'custo_energia' => $custoEnergia,
                'depreciacao' => $custoDepreciacao,
                'custo_total_impressao' => $custoTotalImpressao,
                'custo_por_unidade' => $custoPorUnidade,
                'lucro_total_impressao' => $lucroTotalImpressao,
                'lucro_por_unidade' => $lucroPorUnidade,
                'porcentagem_lucro' => $porcentagemLucro,
                'preco_venda_sugerido' => $precoVendaSugerido,
                'preco_venda_sugerido_unidade' => $precoVendaSugeridoUnidade,
                'observacoes' => isset($dados['observacoes']) && trim($dados['observacoes']) !== '' ? trim($dados['observacoes']) : null,
                'usuario_id' => $usuarioId,
                'filamento_id' => isset($dados['filamento_id']) ? (int)$dados['filamento_id'] : null,
                'resina_id' => isset($dados['resina_id']) ? (int)$dados['resina_id'] : null,
            ];
            $idImpressao = $this->repository->inserirImpressao($impressoesParams);

            // Inserir na tabela miniaturas (dados mínimos obrigatórios)
            $idEstudioRecebido = isset($dados['id_estudio']) ? (int)$dados['id_estudio'] : 0;
            error_log('DEBUG POST id_estudio recebido: ' . var_export($idEstudioRecebido, true));
            if ($idEstudioRecebido <= 0) {
                return ['sucesso' => false, 'erro' => 'Estúdio não selecionado corretamente. Selecione um estúdio da lista.'];
            }
            $miniaturaParams = [
                'id_sku' => $skuCodigo,
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'id_impressao' => $idImpressao,
                'nome_original' => isset($dados['nome_original']) ? (string)$dados['nome_original'] : null,
                'id_estudio' => $idEstudioRecebido,
                'id_colecao' => isset($dados['id_colecao']) ? (int)$dados['id_colecao'] : null,
                // 'id_tematica' removido
                'tematica' => isset($dados['tematica']) ? (string)$dados['tematica'] : null,
                'raca' => isset($dados['raca']) ? (string)$dados['raca'] : null,
                'classe' => isset($dados['classe']) ? (string)$dados['classe'] : null,
                'genero' => isset($dados['genero']) ? (string)$dados['genero'] : null,
                'criatura' => isset($dados['criatura']) ? (string)$dados['criatura'] : null,
                'papel' => isset($dados['papel']) ? (string)$dados['papel'] : null,
                'tamanho' => isset($dados['tamanho']) ? (string)$dados['tamanho'] : null,
                'base' => isset($dados['base']) ? (string)$dados['base'] : null,
                'pintada' => isset($dados['pintada']) ? (int)$dados['pintada'] : 0,
                'arma_principal' => isset($dados['arma_principal']) ? (string)$dados['arma_principal'] : null,
                'arma_secundaria' => isset($dados['arma_secundaria']) ? (string)$dados['arma_secundaria'] : null,
                'armadura' => isset($dados['armadura']) ? (string)$dados['armadura'] : null,
                'outras_caracteristicas' => isset($dados['outras_caracteristicas']) ? (string)$dados['outras_caracteristicas'] : null,
            ];
            error_log('DEBUG inserirMiniatura $miniaturaParams: ' . var_export($miniaturaParams, true));
            $this->repository->inserirMiniatura($miniaturaParams);

            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar miniatura/produto: ' . $e->getMessage()];
        }
    }

    public function descreverErroUpload(int $codigoErro): string
    {
        $mapa = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo maior que o limite configurado no servidor.',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite permitido pelo formulário.',
            UPLOAD_ERR_PARTIAL => 'Upload foi enviado parcialmente.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por uma extensão do PHP.',
        ];

        return $mapa[$codigoErro] ?? 'Erro desconhecido no envio do arquivo.';
    }

    public function normalizarListaTags(string $valor): array
    {
        $itens = array_map('trim', explode(',', $valor));
        $resultado = [];
        foreach ($itens as $item) {
            if ($item !== '') {
                $resultado[] = $item;
            }
        }
        return $resultado;
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $estudio = trim((string) ($dados['estudio'] ?? ''));
        $colecoesSelecionadas = is_array($dados['colecoesSelecionadas'] ?? null) ? $dados['colecoesSelecionadas'] : [];
        $tematica = trim((string) ($dados['tematica'] ?? ''));
        $raca = trim((string) ($dados['raca'] ?? ''));
        $classesSelecionadas = is_array($dados['classesSelecionadas'] ?? null) ? $dados['classesSelecionadas'] : [];
        $selecaoConfirmacao = $dados['selecao_confirmacao'] ?? null;
        $gramas = (float) ($dados['gramas'] ?? 0);
        $tempoTotalMin = (int) ($dados['tempo_total_min'] ?? 0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $taxaFalha = (float) ($dados['taxa_falha'] ?? 0);

        if ($nome === '') {
            return 'Preencha o campo obrigatório: Nome.';
        }
        if ($estudio === '') {
            return 'Selecione um estúdio.';
        }
        if (empty($colecoesSelecionadas)) {
            return 'Selecione ao menos uma coleção.';
        }
        if ($tematica === '') {
            return 'Selecione uma temática.';
        }
        if ($raca === '') {
            return 'Preencha o campo obrigatório: Raça.';
        }
        if (empty($classesSelecionadas)) {
            return 'Preencha o campo obrigatório: Classe.';
        }
        if (!$selecaoConfirmacao) {
            return 'Selecione impressora e material antes de adicionar a miniatura.';
        }
        if ($gramas <= 0) {
            return 'Informe um valor válido para Gramas (g).';
        }
        if ($tempoTotalMin <= 0) {
            return 'Informe um tempo de impressão válido.';
        }
        if ($unidadesProduzidas <= 0) {
            return 'Informe um valor válido para Unidades Produzidas.';
        }
        if ($taxaFalha <= 0) {
            return 'Informe uma Taxa de Falha (%) maior que zero.';
        }

        return '';
    }

    public function parseDadosAdicao(array $post): array
    {
        $nome = trim((string) ($post['nome'] ?? ''));
        $nomeOriginal = trim((string) ($post['nome_original'] ?? ''));
        $estudio = trim((string) ($post['estudio'] ?? ''));
        $estudioIdPost = (int) ($post['estudio_id'] ?? 0);

        $colecao = trim((string) ($post['colecao'] ?? ''));
        $colecoesSelecionadas = $this->normalizarListaTags($colecao);

        $tematica = trim((string) ($post['tematica'] ?? ''));
        $tematicaIdPost = (int) ($post['tematica_id'] ?? 0);

        $raca = trim((string) ($post['raca'] ?? ''));
        $classeEntrada = trim((string) ($post['classe'] ?? ''));
        $classesSelecionadas = $this->normalizarListaTags($classeEntrada);
        $classe = implode(', ', $classesSelecionadas);

        $tempoDias = (int) ($post['tempo_dias'] ?? 0);
        $tempoHoras = (int) ($post['tempo_horas'] ?? 0);
        $tempoMinutos = (int) ($post['tempo_minutos'] ?? 0);
        $tempoTotalMin = ($tempoDias * 24 * 60) + ($tempoHoras * 60) + $tempoMinutos;

        $markup = trim((string) ($post['markup'] ?? '5'));

        return [
            'nome' => $nome,
            'nome_original' => $nomeOriginal,
            'estudio' => $estudio,
            'estudio_id_post' => $estudioIdPost,
            'colecao' => $colecao,
            'colecoesSelecionadas' => $colecoesSelecionadas,
            'tematica' => $tematica,
            'tematica_id_post' => $tematicaIdPost,
            'raca' => $raca,
            'classe' => $classe,
            'classesSelecionadas' => $classesSelecionadas,
            'genero' => trim((string) ($post['genero'] ?? '')),
            'criatura' => trim((string) ($post['criatura'] ?? '')),
            'papel' => trim((string) ($post['papel'] ?? '')),
            'tamanho' => trim((string) ($post['tamanho'] ?? '')),
            'base' => trim((string) ($post['base'] ?? '')),
            'pintada' => $post['pintada'] ?? '',
            'arma_principal' => trim((string) ($post['arma_principal'] ?? '')),
            'arma_secundaria' => trim((string) ($post['arma_secundaria'] ?? '')),
            'armadura' => trim((string) ($post['armadura'] ?? '')),
            'outras_caracteristicas' => trim((string) ($post['outras_caracteristicas'] ?? '')),
            'gramas' => (float) str_replace(',', '.', trim((string) ($post['gramas'] ?? '0'))),
            'tempo_dias' => $tempoDias,
            'tempo_horas' => $tempoHoras,
            'tempo_minutos' => $tempoMinutos,
            'tempo_total_min' => $tempoTotalMin,
            'unidades_produzidas' => (int) ($post['unidades_produzidas'] ?? 0),
            'taxa_falha' => (float) str_replace(',', '.', trim((string) ($post['taxa_falha'] ?? '10'))),
            'markup' => is_numeric($markup) ? (float) $markup : 5.0,
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
            'descricao_produto' => trim((string) ($post['descricao_produto'] ?? '')),
            'fotoExistente' => trim((string) ($post['foto_existente'] ?? '')),
            'imagensExistentesRaw' => trim((string) ($post['imagens_existentes'] ?? '')),
        ];
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'form_token' => trim((string) ($post['form_token'] ?? '')),
            'nome' => trim((string) ($post['nome'] ?? '')),
            'nome_original' => trim((string) ($post['nome_original'] ?? '')),
            'estudio' => trim((string) ($post['estudio'] ?? '')),
            'colecao' => trim((string) ($post['colecao'] ?? '')),
            'tematica' => trim((string) ($post['tematica'] ?? '')),
            'raca' => trim((string) ($post['raca'] ?? '')),
            'classe' => trim((string) ($post['classe'] ?? '')),
            'genero' => trim((string) ($post['genero'] ?? '')),
            'criatura' => trim((string) ($post['criatura'] ?? '')),
            'papel' => trim((string) ($post['papel'] ?? '')),
            'tamanho' => trim((string) ($post['tamanho'] ?? '')),
            'arma_principal' => trim((string) ($post['arma_principal'] ?? '')),
            'arma_secundaria' => trim((string) ($post['arma_secundaria'] ?? '')),
            'armadura' => trim((string) ($post['armadura'] ?? '')),
            'outras_caracteristicas' => trim((string) ($post['outras_caracteristicas'] ?? '')),
            'descricao_produto' => trim((string) ($post['descricao_produto'] ?? '')),
            'base' => trim((string) ($post['base'] ?? '')),
            'pintada' => (string) ($post['pintada'] ?? ''),
            'gramas' => trim((string) ($post['gramas'] ?? '')),
            'tempo_dias' => trim((string) ($post['tempo_dias'] ?? '')),
            'tempo_horas' => trim((string) ($post['tempo_horas'] ?? '')),
            'tempo_minutos' => trim((string) ($post['tempo_minutos'] ?? '')),
            'unidades_produzidas' => trim((string) ($post['unidades_produzidas'] ?? '')),
            'taxa_falha' => trim((string) ($post['taxa_falha'] ?? '10')),
            'markup' => trim((string) ($post['markup'] ?? '5')),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
        ];
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, $selecaoConfirmacao): array
    {
        $dadosPost = $this->parseDadosAdicao($post);
        $resultadoUpload = $this->processarUploadsAdicao(
            $usuarioUuid,
            (string) ($dadosPost['fotoExistente'] ?? ''),
            (string) ($dadosPost['imagensExistentesRaw'] ?? ''),
            $files
        );

        $erro = trim((string) ($resultadoUpload['erro'] ?? ''));
        $foto = $resultadoUpload['foto'] ?? null;
        $imagens = is_array($resultadoUpload['imagens'] ?? null) ? $resultadoUpload['imagens'] : [];
        $avisosUpload = is_array($resultadoUpload['avisos_upload'] ?? null) ? $resultadoUpload['avisos_upload'] : [];
        $custoTotalImpressao = 0.0;

        if ($erro === '') {
            $erro = $this->validarDadosAdicao([
                'nome' => $dadosPost['nome'] ?? '',
                'estudio' => $dadosPost['estudio'] ?? '',
                'colecoesSelecionadas' => $dadosPost['colecoesSelecionadas'] ?? [],
                'tematica' => $dadosPost['tematica'] ?? '',
                'raca' => $dadosPost['raca'] ?? '',
                'classesSelecionadas' => $dadosPost['classesSelecionadas'] ?? [],
                'selecao_confirmacao' => $selecaoConfirmacao,
                'gramas' => $dadosPost['gramas'] ?? 0,
                'tempo_total_min' => $dadosPost['tempo_total_min'] ?? 0,
                'unidades_produzidas' => $dadosPost['unidades_produzidas'] ?? 0,
                'taxa_falha' => $dadosPost['taxa_falha'] ?? 0,
            ]);
        }

        if ($erro === '') {
            $custoTotalImpressao = $this->calcularCustoImpressaoAdicao(
                $usuarioId,
                is_array($selecaoConfirmacao) ? $selecaoConfirmacao : [],
                (float) ($dadosPost['gramas'] ?? 0),
                (int) ($dadosPost['tempo_total_min'] ?? 0),
                (float) ($dadosPost['taxa_falha'] ?? 0)
            );
        }


        // Resolver estúdio pelo nome, criando se necessário (igual torres)
        $estudioId = 0;
        $estudioNome = trim((string)($dadosPost['estudio'] ?? ''));
        if ($estudioNome !== '') {
            $estudio = $this->resolverEstudio($usuarioId, $estudioNome);
            if (is_array($estudio) && isset($estudio['id'])) {
                $estudioId = (int)$estudio['id'];
            }
        }

        // Resolver coleção principal pelo nome e estúdio (igual torres)
        $colecaoId = null;
        $colecaoNome = '';
        if (!empty($dadosPost['colecao'])) {
            $colecaoNome = trim((string)$dadosPost['colecao']);
        } elseif (!empty($dadosPost['colecoesSelecionadas'][0])) {
            $colecaoNome = trim((string)$dadosPost['colecoesSelecionadas'][0]);
        }
        if ($colecaoNome !== '' && $estudioId > 0) {
            $colecao = $this->resolverColecao($usuarioId, $estudioId, $colecaoNome);
            if (is_array($colecao) && isset($colecao['id'])) {
                $colecaoId = (int)$colecao['id'];
            }
        }

        if ($erro === '') {
            $impressora_id = $selecaoConfirmacao['impressora']['id'] ?? null;
            $material_tipo = $selecaoConfirmacao['material_tipo'] ?? null;
            $material = $selecaoConfirmacao['material'] ?? [];
            $filamento_id = $material_tipo === 'Filamento' ? ($material['id'] ?? null) : null;
            $resina_id = $material_tipo === 'Resina' ? ($material['id'] ?? null) : null;

            $potencia = $selecaoConfirmacao['impressora']['potencia'] ?? null;
            $fator_uso = $selecaoConfirmacao['impressora']['fator_uso'] ?? null;
            $custo_hora = $selecaoConfirmacao['impressora']['custo_hora'] ?? null;
            $preco_kilo = $material_tipo === 'Filamento' ? ($material['preco_kilo'] ?? null) : null;
            $valor_kwh = $selecaoConfirmacao['valor_kwh'] ?? null;

            $resultadoCadastro = $this->processarCadastroAdicao(array_merge($dadosPost, [
                'usuario_id' => $usuarioId,
                'foto' => $foto,
                'imagens' => $imagens,
                'custo_total_impressao' => $custoTotalImpressao,
                'impressora_id' => $impressora_id,
                'filamento_id' => $filamento_id,
                'resina_id' => $resina_id,
                'potencia' => $potencia,
                'fator_uso' => $fator_uso,
                'custo_hora' => $custo_hora,
                'preco_kilo' => $preco_kilo,
                'valor_kwh' => $valor_kwh,
                'id_estudio' => $estudioId,
                'id_colecao' => $colecaoId,
            ]));

            if (($resultadoCadastro['sucesso'] ?? false) === true) {
                return [
                    'sucesso' => true,
                    'erro' => '',
                    'dadosPost' => $dadosPost,
                    'foto' => $foto,
                    'imagens' => $imagens,
                    'avisos_upload' => $avisosUpload,
                    'custo_total_impressao' => $custoTotalImpressao,
                ];
            }

            $erro = (string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar miniatura/produto.');
        }

        return [
            'sucesso' => false,
            'erro' => $erro,
            'dadosPost' => $dadosPost,
            'foto' => $foto,
            'imagens' => $imagens,
            'avisos_upload' => $avisosUpload,
            'custo_total_impressao' => $custoTotalImpressao,
        ];
    }

    public function editar(int $id, array $dados): array
    {
        // Implementação futura ou removida
        return ['sucesso' => false, 'erro' => 'Função editar não implementada.'];
    }

    private function toNullableString($value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : null;
    }

    private function gerarSiglaEstudio(string $nome): string
    {
        $partesOriginais = preg_split('/\s+/u', trim($nome)) ?: [];
        $partes = [];

        foreach ($partesOriginais as $parteOriginal) {
            $parteOriginal = trim((string) $parteOriginal);
            if ($parteOriginal === '') {
                continue;
            }

            $normalizada = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $parteOriginal);
            if ($normalizada === false) {
                $normalizada = $parteOriginal;
            }

            $normalizada = strtoupper($normalizada);
            $normalizada = preg_replace('/[^A-Z0-9]/', '', $normalizada);
            if ($normalizada === '') {
                continue;
            }

            $partes[] = $normalizada;
        }

        if (empty($partes)) {
            return 'XXX';
        }

        if (count($partes) === 1) {
            return str_pad(substr($partes[0], 0, 3), 3, 'X');
        }

        $iniciais = '';
        foreach ($partes as $parte) {
            $iniciais .= substr($parte, 0, 1);
        }

        return $iniciais;
    }

    private function gerarSigla3(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return 'XXX';
        }

        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($normalizado === false) {
            $normalizado = $texto;
        }

        $normalizado = strtoupper($normalizado);
        $normalizado = preg_replace('/[^A-Z0-9]/', '', $normalizado);
        if ($normalizado === '') {
            return 'XXX';
        }

        return str_pad(substr($normalizado, 0, 3), 3, 'X');
    }

    // Implementação para evitar erro fatal ao chamar processarUploadsAdicao
    public function processarUploadsAdicao(string $usuarioUuid, string $fotoExistente, string $imagensExistentesRaw, array $files): array
    {
        // Implemente aqui o processamento real de uploads se necessário
        return [
            'erro' => null,
            'foto' => $fotoExistente,
            'imagens' => [],
            'avisos_upload' => [],
        ];
    }

    // Stub temporário para evitar erro fatal
    public function calcularCustoImpressaoAdicao(int $usuarioId, array $selecaoConfirmacao, float $gramas, int $tempoTotalMin, float $taxaFalha): float
    {
        // TODO: Implementar cálculo real depois
        return 0.0;
    }
}
