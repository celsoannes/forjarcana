<?php

namespace App\Miniaturas;

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
                if (($impressoraSelecionada['tipo'] ?? '') === 'Resina' && $resinaId > 0) {
                    $materialSelecionado = $this->repository->buscarResina($resinaId, $usuarioId);
                    if ($materialSelecionado) {
                        $resultado['selecao_confirmacao'] = [
                            'impressora' => $impressoraSelecionada,
                            'material_tipo' => 'Resina',
                            'material' => $materialSelecionado,
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
        $estudioIdPost = (int) ($dados['estudio_id_post'] ?? 0);
        $estudio = trim((string) ($dados['estudio'] ?? ''));
        $colecoesSelecionadas = is_array($dados['colecoesSelecionadas'] ?? null) ? $dados['colecoesSelecionadas'] : [];
        $tematicaIdPost = (int) ($dados['tematica_id_post'] ?? 0);
        $tematica = trim((string) ($dados['tematica'] ?? ''));
        $classesSelecionadas = is_array($dados['classesSelecionadas'] ?? null) ? $dados['classesSelecionadas'] : [];
        $raca = trim((string) ($dados['raca'] ?? ''));

        $custoTotalImpressao = (float) ($dados['custo_total_impressao'] ?? 0);
        $markupLojista = (float) ($dados['markup_lojista_valor'] ?? 2.0);
        $markupConsumidorFinal = (float) ($dados['markup_consumidor_final_valor'] ?? 5.0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];

        try {
            $this->repository->iniciarTransacao();

            if ($estudioIdPost > 0) {
                $estudioEscolhido = $this->buscarEstudioPorId($usuarioId, $estudioIdPost);
                if (!$estudioEscolhido) {
                    throw new \RuntimeException('O estúdio selecionado não é válido para este usuário.');
                }
            } else {
                $estudioEscolhido = $this->resolverEstudio($usuarioId, $estudio);
            }

            $colecoesResolvidas = [];
            foreach ($colecoesSelecionadas as $colecaoItem) {
                $colecoesResolvidas[] = $this->resolverColecao($usuarioId, (int) ($estudioEscolhido['id'] ?? 0), (string) $colecaoItem);
            }

            if ($tematicaIdPost > 0) {
                $tematicaEscolhida = $this->buscarTematicaPorId($tematicaIdPost);
                if (!$tematicaEscolhida) {
                    throw new \RuntimeException('A temática selecionada não é válida.');
                }
            } else {
                $tematicaEscolhida = $this->resolverTematica($tematica);
            }

            $estudioIdEscolhido = (int) ($estudioEscolhido['id'] ?? 0);
            $colecaoIdsEscolhidas = array_values(array_unique(array_map(static function ($colecaoItem): int {
                return (int) ($colecaoItem['id'] ?? 0);
            }, $colecoesResolvidas)));
            $colecaoIdsEscolhidas = array_values(array_filter($colecaoIdsEscolhidas, static function (int $id): bool {
                return $id > 0;
            }));
            $colecaoIdEscolhida = (int) ($colecaoIdsEscolhidas[0] ?? 0);

            if ($colecaoIdEscolhida <= 0) {
                throw new \RuntimeException('Não foi possível identificar uma coleção válida para este cadastro.');
            }

            if ($estudioIdEscolhido <= 0) {
                throw new \RuntimeException('Não foi possível identificar um estúdio válido para este cadastro.');
            }

            $categoriaId = $this->repository->buscarCategoriaIdPorNome('Miniaturas');
            if ($categoriaId === 0) {
                $categoriaId = $this->repository->inserirCategoria('Miniaturas');
            }

            $classeParaSku = (string) ($classesSelecionadas[0] ?? '');
            $skuCodigo = $this->gerarSkuAutomatico((string) ($estudioEscolhido['nome'] ?? ''), $raca, $classeParaSku);

            $precoLojista = $custoTotalImpressao * $markupLojista;
            $precoConsumidorFinal = $custoTotalImpressao * $markupConsumidorFinal;
            $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;

            $produtoId = $this->repository->inserirProduto([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'categoria' => $categoriaId,
                'imagem_capa' => $this->toNullableString($dados['foto'] ?? null),
                'imagens' => $imagensJson,
                'descricao' => $this->toNullableString($dados['descricao_produto'] ?? null),
                'observacoes' => $this->toNullableString($dados['observacoes'] ?? null),
                'markup_lojista' => $markupLojista,
                'markup_consumidor_final' => $markupConsumidorFinal,
                'preco_lojista' => $precoLojista,
                'preco_consumidor_final' => $precoConsumidorFinal,
            ]);

            $this->repository->inserirSku($produtoId, $skuCodigo, $usuarioId);

            $custoPorUnidade = $unidadesProduzidas > 0 ? round($custoTotalImpressao / $unidadesProduzidas, 2) : 0.00;
            $this->repository->inserirCusto($produtoId, $custoTotalImpressao, $custoPorUnidade);

            $miniaturaId = $this->repository->inserirMiniatura([
                'id_sku' => $skuCodigo,
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'nome_original' => $this->toNullableString($dados['nome_original'] ?? null),
                'id_estudio' => $estudioIdEscolhido,
                'id_colecao' => $colecaoIdEscolhida,
                'id_tematica' => (int) ($tematicaEscolhida['id'] ?? 0),
                'tematica' => (string) ($tematicaEscolhida['nome'] ?? ''),
                'raca' => $this->toNullableString($dados['raca'] ?? null),
                'classe' => $this->toNullableString($dados['classe'] ?? null),
                'genero' => $this->toNullableString($dados['genero'] ?? null),
                'criatura' => $this->toNullableString($dados['criatura'] ?? null),
                'papel' => $this->toNullableString($dados['papel'] ?? null),
                'tamanho' => $this->toNullableString($dados['tamanho'] ?? null),
                'base' => $this->toNullableString($dados['base'] ?? null),
                'pintada' => ($dados['pintada'] ?? '') !== '' ? (int) $dados['pintada'] : null,
                'arma_principal' => $this->toNullableString($dados['arma_principal'] ?? null),
                'arma_secundaria' => $this->toNullableString($dados['arma_secundaria'] ?? null),
                'armadura' => $this->toNullableString($dados['armadura'] ?? null),
                'outras_caracteristicas' => $this->toNullableString($dados['outras_caracteristicas'] ?? null),
            ]);

            $this->vincularMiniaturaColecoes($miniaturaId, $usuarioId, $colecaoIdsEscolhidas);
            $this->repository->confirmarTransacao();

            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            if ($this->repository->emTransacao()) {
                $this->repository->desfazerTransacao();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar miniatura/produto: ' . $e->getMessage()];
        }
    }

    public function calcularCustoImpressaoAdicao(int $usuarioId, array $selecaoConfirmacao, float $gramas, int $tempoTotalMin, float $taxaFalha): float
    {
        if ($usuarioId <= 0 || $gramas <= 0 || $tempoTotalMin <= 0 || $taxaFalha <= 0) {
            return 0.0;
        }

        $tempoTotalHoras = $tempoTotalMin / 60;
        $potencia = isset($selecaoConfirmacao['impressora']['potencia']) ? (float) $selecaoConfirmacao['impressora']['potencia'] : 0.0;
        $fatorUso = isset($selecaoConfirmacao['impressora']['fator_uso']) ? (float) $selecaoConfirmacao['impressora']['fator_uso'] : 1.0;
        $custoHora = isset($selecaoConfirmacao['impressora']['custo_hora']) ? (float) $selecaoConfirmacao['impressora']['custo_hora'] : 0.0;

        $valorKwh = $this->repository->buscarValorKwhPorUsuario($usuarioId);
        if ($valorKwh === null || $valorKwh <= 0) {
            $valorKwh = 1.0;
        }

        $custoEnergia = ($potencia * $tempoTotalHoras * $fatorUso * $valorKwh) / 1000;
        $custoDepreciacao = ($custoHora / 60) * $tempoTotalMin;

        if (($selecaoConfirmacao['material_tipo'] ?? '') === 'Filamento') {
            $precoKilo = isset($selecaoConfirmacao['material']['preco_kilo']) ? (float) $selecaoConfirmacao['material']['preco_kilo'] : 0.0;
            $custoMaterial = ($gramas / 1000) * $precoKilo;
            $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao;
            return round($baseCusto + (($baseCusto * 0.7) / $taxaFalha), 2);
        }

        $precoLitro = isset($selecaoConfirmacao['material']['preco_litro']) ? (float) $selecaoConfirmacao['material']['preco_litro'] : 0.0;
        $custoMaterial = ($gramas / 1000) * $precoLitro;

        $precoLitroAlcool = $this->repository->buscarPrecoLitroAlcoolPorUsuario($usuarioId);
        if ($precoLitroAlcool === null || $precoLitroAlcool < 0) {
            $precoLitroAlcool = 0.0;
        }
        $custoLavagemAlcool = ($precoLitroAlcool / 1000) * $gramas;

        $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao + $custoLavagemAlcool;
        return round($baseCusto + (($baseCusto * 0.7) / $taxaFalha), 2);
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
        $chaves = [];

        foreach ($itens as $item) {
            if ($item === '') {
                continue;
            }

            $chave = function_exists('mb_strtolower') ? mb_strtolower($item, 'UTF-8') : strtolower($item);
            if (isset($chaves[$chave])) {
                continue;
            }

            $chaves[$chave] = true;
            $resultado[] = $item;
        }

        return $resultado;
    }

    public function processarUploadsAdicao(string $usuarioUuid, string $fotoExistente, string $imagensExistentesRaw, array $files): array
    {
        $resultado = [
            'erro' => '',
            'foto' => $fotoExistente !== '' ? $fotoExistente : null,
            'imagens' => [],
            'avisos_upload' => [],
        ];

        if ($imagensExistentesRaw !== '') {
            $imagensExistentes = json_decode($imagensExistentesRaw, true);
            if (is_array($imagensExistentes)) {
                foreach ($imagensExistentes as $imagemExistente) {
                    if (is_string($imagemExistente) && trim($imagemExistente) !== '') {
                        $resultado['imagens'][] = trim($imagemExistente);
                    }
                }
            }
        }

        if ($usuarioUuid === '') {
            $resultado['erro'] = 'Não foi possível identificar o UUID do usuário para upload das imagens.';
            return $resultado;
        }

        $tamanhosUpload = [
            'thumbnail' => [150, 150, 'crop'],
            'pequena' => [300, 300, 'proporcional'],
            'media' => [300, 300, 'proporcional'],
            'grande' => [1024, 1024, 'proporcional'],
        ];

        if (isset($files['foto']) && ($files['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $fotoUpload = uploadImagem($files['foto'], $usuarioUuid, 'usuarios', $tamanhosUpload, 'miniatura_CAPA', false);
            if ($fotoUpload === false) {
                $resultado['erro'] = 'Erro ao enviar a imagem de capa. Verifique formato e tamanho do arquivo.';
                return $resultado;
            }

            $resultado['foto'] = $fotoUpload;
        }

        if (isset($files['fotos']) && isset($files['fotos']['name']) && is_array($files['fotos']['name'])) {
            $totalArquivos = count($files['fotos']['name']);
            for ($i = 0; $i < $totalArquivos; $i++) {
                $nomeArquivo = trim((string) ($files['fotos']['name'][$i] ?? ''));
                $erroArquivo = $files['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

                if ($nomeArquivo === '' || $erroArquivo === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($erroArquivo !== UPLOAD_ERR_OK) {
                    $resultado['avisos_upload'][] = 'A imagem adicional "' . $nomeArquivo . '" não foi enviada: ' . $this->descreverErroUpload((int) $erroArquivo);
                    continue;
                }

                $arquivoImagem = [
                    'name' => $nomeArquivo,
                    'type' => $files['fotos']['type'][$i] ?? '',
                    'tmp_name' => $files['fotos']['tmp_name'][$i] ?? '',
                    'error' => $erroArquivo,
                    'size' => $files['fotos']['size'][$i] ?? 0,
                ];

                $imagemUpload = uploadImagem($arquivoImagem, $usuarioUuid, 'usuarios', $tamanhosUpload, 'miniatura_IMAGEM', false);
                if ($imagemUpload === false) {
                    $resultado['avisos_upload'][] = 'A imagem adicional "' . $nomeArquivo . '" não pôde ser processada (formato ou conteúdo inválido).';
                    continue;
                }

                $resultado['imagens'][] = $imagemUpload;
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

        $markupLojista = trim((string) ($post['markup_lojista'] ?? '2'));
        $markupConsumidorFinal = trim((string) ($post['markup_consumidor_final'] ?? '5'));

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
            'markup_lojista' => $markupLojista,
            'markup_consumidor_final' => $markupConsumidorFinal,
            'markup_lojista_valor' => is_numeric($markupLojista) ? (float) $markupLojista : 2.0,
            'markup_consumidor_final_valor' => is_numeric($markupConsumidorFinal) ? (float) $markupConsumidorFinal : 5.0,
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
            'markup_consumidor_final' => trim((string) ($post['markup_consumidor_final'] ?? '5')),
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

        if ($erro === '') {
            $resultadoCadastro = $this->processarCadastroAdicao(array_merge($dadosPost, [
                'usuario_id' => $usuarioId,
                'foto' => $foto,
                'imagens' => $imagens,
                'custo_total_impressao' => $custoTotalImpressao,
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
        $sku = trim((string) ($dados['sku'] ?? ''));
        $estudio = trim((string) ($dados['estudio'] ?? ''));

        if ($sku === '' || $estudio === '') {
            return ['sucesso' => false, 'erro' => 'Preencha os campos obrigatórios: SKU e Estúdio.'];
        }

        $payload = [
            'nome' => $this->toNullableString($dados['nome'] ?? null),
            'sku' => $sku,
            'estudio' => $estudio,
            'tematica' => $this->toNullableString($dados['tematica'] ?? null),
            'colecao' => $this->toNullableString($dados['colecao'] ?? null),
            'raca' => $this->toNullableString($dados['raca'] ?? null),
            'classe' => $this->toNullableString($dados['classe'] ?? null),
            'genero' => $this->toNullableString($dados['genero'] ?? null),
            'criatura' => $this->toNullableString($dados['criatura'] ?? null),
            'papel' => $this->toNullableString($dados['papel'] ?? null),
            'tamanho' => $this->toNullableString($dados['tamanho'] ?? null),
            'base' => $this->toNullableString($dados['base'] ?? null),
            'material' => $this->toNullableString($dados['material'] ?? null),
            'pintada' => !empty($dados['pintada']) ? 1 : 0,
            'arma_principal' => $this->toNullableString($dados['arma_principal'] ?? null),
            'arma_secundaria' => $this->toNullableString($dados['arma_secundaria'] ?? null),
            'armadura' => $this->toNullableString($dados['armadura'] ?? null),
            'outras_caracteristicas' => $this->toNullableString($dados['outras_caracteristicas'] ?? null),
            'foto' => $this->toNullableString($dados['foto'] ?? null),
        ];

        try {
            $this->repository->atualizar($id, $payload);
            return ['sucesso' => true, 'erro' => null];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['sucesso' => false, 'erro' => 'SKU já cadastrado. Informe um SKU único.'];
            }

            return ['sucesso' => false, 'erro' => 'Erro ao editar miniatura: ' . $e->getMessage()];
        }
    }

    public function excluir(int $id): void
    {
        $this->repository->excluirPorId($id);
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
}
