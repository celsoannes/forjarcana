<?php

namespace App\Torres;

use PDO;

class TorreService
{
    private TorreRepository $repository;
    private TorreImagemService $imagemService;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new TorreRepository($pdo);
        $this->imagemService = new TorreImagemService();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->repository->listarPorUsuario($usuarioId);
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
            'outras_caracteristicas_disponiveis' => [],
        ];

        if ($resultado['usuario_uuid'] === '' && $usuarioId > 0) {
            try {
                $resultado['usuario_uuid'] = (string) ($this->repository->buscarUuidUsuario($usuarioId) ?? '');
            } catch (\Throwable $e) {
                $resultado['usuario_uuid'] = '';
            }
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

        try {
            $resultado['estudios_disponiveis'] = $this->repository->listarEstudiosPorUsuario($usuarioId);
        } catch (\Throwable $e) {
            $resultado['estudios_disponiveis'] = [];
        }

        try {
            $resultado['colecoes_disponiveis'] = $this->repository->listarColecoesPorUsuario($usuarioId);
        } catch (\Throwable $e) {
            $resultado['colecoes_disponiveis'] = [];
        }

        try {
            $resultado['tematicas_disponiveis'] = $this->repository->listarTematicas();
        } catch (\Throwable $e) {
            $resultado['tematicas_disponiveis'] = [];
        }

        try {
            $linhas = $this->repository->listarOutrasCaracteristicasMiniaturas($usuarioId);
            $itensUnicos = [];
            $controleUnicos = [];

            foreach ($linhas as $linhaOutrasCaracteristicas) {
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
            $resultado['outras_caracteristicas_disponiveis'] = [];
        }

        return $resultado;
    }

    public function parseDadosAdicao(array $post): array
    {
        $colecao = trim((string) ($post['colecao'] ?? ''));
        $colecoesSelecionadas = $this->normalizarListaTags($colecao);

        $tempoDias = (int) ($post['tempo_dias'] ?? 0);
        $tempoHoras = (int) ($post['tempo_horas'] ?? 0);
        $tempoMinutos = (int) ($post['tempo_minutos'] ?? 0);
        $tempoTotalMin = ($tempoDias * 24 * 60) + ($tempoHoras * 60) + $tempoMinutos;

        $fotoAtual = null;
        $fotoExistente = trim((string) ($post['foto_existente'] ?? ''));
        if ($fotoExistente !== '') {
            $fotoAtual = $fotoExistente;
        }

        $imagensAtuais = [];
        $imagensExistentesRaw = trim((string) ($post['imagens_existentes'] ?? ''));
        if ($imagensExistentesRaw !== '') {
            $imagensExistentes = json_decode($imagensExistentesRaw, true);
            if (is_array($imagensExistentes)) {
                foreach ($imagensExistentes as $imagemExistente) {
                    if (is_string($imagemExistente) && trim($imagemExistente) !== '') {
                        $imagensAtuais[] = trim($imagemExistente);
                    }
                }
            }
        }

        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'nome_original' => trim((string) ($post['nome_original'] ?? '')),
            'estudio' => trim((string) ($post['estudio'] ?? '')),
            'colecao' => $colecao,
            'colecoesSelecionadas' => $colecoesSelecionadas,
            'tematica' => trim((string) ($post['tematica'] ?? '')),
            'outras_caracteristicas' => trim((string) ($post['outras_caracteristicas'] ?? '')),
            'descricao_produto' => trim((string) ($post['descricao_produto'] ?? '')),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
            'markup_lojista' => (float) str_replace(',', '.', trim((string) ($post['markup_lojista'] ?? '2'))),
            'markup_consumidor_final' => (float) str_replace(',', '.', trim((string) ($post['markup_consumidor_final'] ?? '5'))),
            'gramas' => (float) str_replace(',', '.', trim((string) ($post['gramas'] ?? '0'))),
            'tempo_dias' => $tempoDias,
            'tempo_horas' => $tempoHoras,
            'tempo_minutos' => $tempoMinutos,
            'tempo_total_min' => $tempoTotalMin,
            'unidades_produzidas' => (int) ($post['unidades_produzidas'] ?? 0),
            'taxa_falha' => (float) str_replace(',', '.', trim((string) ($post['taxa_falha'] ?? '10'))),
            'foto' => $fotoAtual,
            'imagens' => $imagensAtuais,
        ];
    }

    public function validarDadosAdicao(array $dados, $selecaoConfirmacao): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $gramas = (float) ($dados['gramas'] ?? 0);
        $tempoTotalMin = (int) ($dados['tempo_total_min'] ?? 0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $taxaFalha = (float) ($dados['taxa_falha'] ?? 0);

        if ($nome === '') {
            return 'Preencha o nome da torre.';
        }

        if (!$selecaoConfirmacao) {
            return 'Selecione impressora e material antes de adicionar a torre de dados.';
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

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files, $selecaoConfirmacao): array
    {
        $dados = $this->parseDadosAdicao($post);
        $erro = $this->validarDadosAdicao($dados, $selecaoConfirmacao);

        $foto = $dados['foto'] ?? null;
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];

        $custosCalculados = [
            'valor_kwh' => 1.0,
            'custo_material' => 0.0,
            'custo_lavagem_alcool' => 0.0,
            'custo_energia' => 0.0,
            'custo_depreciacao' => 0.0,
            'custo_total_impressao' => 0.0,
        ];

        if ($erro === '') {
            $custosCalculados = $this->calcularCustosImpressao(
                $usuarioId,
                is_array($selecaoConfirmacao) ? $selecaoConfirmacao : [],
                (float) ($dados['gramas'] ?? 0),
                (int) ($dados['tempo_total_min'] ?? 0),
                (float) ($dados['taxa_falha'] ?? 0)
            );
        }

        if ($erro === '') {
            $resultadoUpload = $this->processarUploadsAdicao($files, $usuarioUuid, $foto, $imagens);
            $erroUpload = (string) ($resultadoUpload['erro'] ?? '');
            if ($erroUpload !== '') {
                $erro = $erroUpload;
            }

            $foto = $resultadoUpload['foto'] ?? $foto;
            $imagens = is_array($resultadoUpload['imagens'] ?? null) ? $resultadoUpload['imagens'] : $imagens;
        }

        if ($erro === '') {
            $resultadoCriacao = $this->processarCriacao(array_merge($dados, [
                'usuario_id' => $usuarioId,
                'valor_kwh' => (float) ($custosCalculados['valor_kwh'] ?? 1.0),
                'custo_material' => (float) ($custosCalculados['custo_material'] ?? 0.0),
                'custo_lavagem_alcool' => (float) ($custosCalculados['custo_lavagem_alcool'] ?? 0.0),
                'custo_energia' => (float) ($custosCalculados['custo_energia'] ?? 0.0),
                'custo_depreciacao' => (float) ($custosCalculados['custo_depreciacao'] ?? 0.0),
                'custo_total_impressao' => (float) ($custosCalculados['custo_total_impressao'] ?? 0.0),
                'foto' => $foto,
                'imagens' => $imagens,
                'selecao_confirmacao' => is_array($selecaoConfirmacao) ? $selecaoConfirmacao : [],
            ]));

            if (!empty($resultadoCriacao['sucesso'])) {
                return [
                    'sucesso' => true,
                    'erro' => '',
                    'torre_id' => (int) ($resultadoCriacao['torre_id'] ?? 0),
                    'foto' => $foto,
                    'imagens' => $imagens,
                ];
            }

            $erro = (string) ($resultadoCriacao['erro'] ?? 'Erro ao cadastrar torre.');
        }

        return [
            'sucesso' => false,
            'erro' => $erro,
            'torre_id' => 0,
            'foto' => $foto,
            'imagens' => $imagens,
        ];
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'nome_original' => trim((string) ($post['nome_original'] ?? '')),
            'estudio' => trim((string) ($post['estudio'] ?? '')),
            'colecao' => trim((string) ($post['colecao'] ?? '')),
            'tematica' => trim((string) ($post['tematica'] ?? '')),
            'outras_caracteristicas' => trim((string) ($post['outras_caracteristicas'] ?? '')),
            'descricao_produto' => trim((string) ($post['descricao_produto'] ?? '')),
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

    public function calcularCustosImpressao(int $usuarioId, array $selecaoConfirmacao, float $gramas, int $tempoTotalMin, float $taxaFalha): array
    {
        $tempoTotalHoras = $tempoTotalMin / 60;
        $potencia = isset($selecaoConfirmacao['impressora']['potencia']) ? (float) $selecaoConfirmacao['impressora']['potencia'] : 0.0;
        $fatorUso = isset($selecaoConfirmacao['impressora']['fator_uso']) ? (float) $selecaoConfirmacao['impressora']['fator_uso'] : 1.0;
        $custoHora = isset($selecaoConfirmacao['impressora']['custo_hora']) ? (float) $selecaoConfirmacao['impressora']['custo_hora'] : 0.0;

        $valorKwh = $this->repository->buscarValorKwhPorUsuario($usuarioId);
        $custoEnergia = round((($potencia * $tempoTotalHoras * $fatorUso * $valorKwh) / 1000), 2);
        $custoDepreciacao = round((($custoHora / 60) * $tempoTotalMin), 2);

        $custoMaterial = 0.00;
        $custoLavagemAlcool = 0.00;
        $custoTotalImpressao = 0.00;

        if (($selecaoConfirmacao['material_tipo'] ?? '') === 'Filamento') {
            $precoKilo = isset($selecaoConfirmacao['material']['preco_kilo']) ? (float) $selecaoConfirmacao['material']['preco_kilo'] : 0.0;
            $custoMaterial = round((($gramas / 1000) * $precoKilo), 2);
            $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao;
            $custoTaxaFalha = round(($baseCusto * ($taxaFalha / 100)), 2);
            $custoTotalImpressao = round($baseCusto + $custoTaxaFalha, 2);
        } else {
            $precoLitro = isset($selecaoConfirmacao['material']['preco_litro']) ? (float) $selecaoConfirmacao['material']['preco_litro'] : 0.0;
            $custoMaterial = round((($gramas / 1000) * $precoLitro), 2);

            $precoLitroAlcool = $this->repository->buscarPrecoLitroAlcoolPorUsuario($usuarioId);
            $custoLavagemAlcool = round((($precoLitroAlcool / 1000) * $gramas), 2);

            $baseCusto = $custoMaterial + $custoEnergia + $custoDepreciacao + $custoLavagemAlcool;
            $custoTaxaFalha = round(($baseCusto * ($taxaFalha / 100)), 2);
            $custoTotalImpressao = round($baseCusto + $custoTaxaFalha, 2);
        }

        return [
            'valor_kwh' => $valorKwh,
            'custo_material' => $custoMaterial,
            'custo_lavagem_alcool' => $custoLavagemAlcool,
            'custo_energia' => $custoEnergia,
            'custo_depreciacao' => $custoDepreciacao,
            'custo_total_impressao' => $custoTotalImpressao,
        ];
    }

    public function processarUploadsAdicao(array $files, string $usuarioUuid, ?string $fotoAtual, array $imagensAtuais): array
    {
        return $this->imagemService->processarUploadsAdicao($files, $usuarioUuid, $fotoAtual, $imagensAtuais);
    }

    private function normalizarListaTags(string $valor): array
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

    public function buscarParaEdicao(int $id, int $usuarioId): ?array
    {
        $compatibilidade = $this->repository->obterCompatibilidadeCamposEdicao();
        $torre = $this->repository->buscarParaEdicao($id, $usuarioId, $compatibilidade);
        if (!$torre) {
            return null;
        }

        $torre['__compatibilidade'] = $compatibilidade;
        return $torre;
    }

    public function buscarParaVisualizacao(int $id, int $usuarioId): ?array
    {
        return $this->repository->buscarParaVisualizacao($id, $usuarioId);
    }

    public function processarCriacao(array $dados): array
    {
        $usuarioId = (int) ($dados['usuario_id'] ?? 0);
        $nome = trim((string) ($dados['nome'] ?? ''));
        $nomeOriginal = trim((string) ($dados['nome_original'] ?? ''));
        $estudio = trim((string) ($dados['estudio'] ?? ''));
        $colecoesSelecionadas = is_array($dados['colecoesSelecionadas'] ?? null) ? $dados['colecoesSelecionadas'] : [];
        $tematica = trim((string) ($dados['tematica'] ?? ''));
        $outrasCaracteristicas = trim((string) ($dados['outras_caracteristicas'] ?? ''));
        $descricaoProduto = trim((string) ($dados['descricao_produto'] ?? ''));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $markupLojista = (float) ($dados['markup_lojista'] ?? 0);
        $markupConsumidorFinal = (float) ($dados['markup_consumidor_final'] ?? 0);
        $taxaFalha = (float) ($dados['taxa_falha'] ?? 0);
        $gramas = (float) ($dados['gramas'] ?? 0);
        $tempoTotalMin = (int) ($dados['tempo_total_min'] ?? 0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $valorKwh = (float) ($dados['valor_kwh'] ?? 1.0);
        $custoMaterial = (float) ($dados['custo_material'] ?? 0);
        $custoLavagemAlcool = (float) ($dados['custo_lavagem_alcool'] ?? 0);
        $custoEnergia = (float) ($dados['custo_energia'] ?? 0);
        $custoDepreciacao = (float) ($dados['custo_depreciacao'] ?? 0);
        $custoTotalImpressao = (float) ($dados['custo_total_impressao'] ?? 0);
        $foto = $dados['foto'] ?? null;
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];
        $selecaoConfirmacao = is_array($dados['selecao_confirmacao'] ?? null) ? $dados['selecao_confirmacao'] : [];

        try {
            $this->pdo->beginTransaction();

            $stmtCategoria = $this->pdo->prepare('SELECT id FROM categorias WHERE nome = ? LIMIT 1');
            $stmtCategoria->execute(['Torre de Dados']);
            $categoriaId = (int) ($stmtCategoria->fetchColumn() ?: 0);

            if ($categoriaId === 0) {
                $stmtInsertCategoria = $this->pdo->prepare('INSERT INTO categorias (nome) VALUES (?)');
                $stmtInsertCategoria->execute(['Torre de Dados']);
                $categoriaId = (int) $this->pdo->lastInsertId();
            }

            $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;

            $custoPorUnidade = $unidadesProduzidas > 0 ? round($custoTotalImpressao / $unidadesProduzidas, 2) : 0.00;
            $precoVendaSugerido = round($custoTotalImpressao * $markupConsumidorFinal, 2);
            $precoVendaSugeridoUnidade = $unidadesProduzidas > 0 ? round($precoVendaSugerido / $unidadesProduzidas, 2) : 0.00;
            $lucroTotal = round($precoVendaSugerido - $custoTotalImpressao, 2);
            $lucroPorUnidade = $unidadesProduzidas > 0 ? round($lucroTotal / $unidadesProduzidas, 2) : 0.00;

            $precoLojista = round($custoPorUnidade * $markupLojista, 2);
            $precoConsumidorFinal = $precoVendaSugeridoUnidade;
            $lucroLojista = round($precoLojista - $custoPorUnidade, 2);
            $lucroConsumidorFinal = round($precoConsumidorFinal - $custoPorUnidade, 2);

            $stmtColunasProdutos = $this->pdo->query('SHOW COLUMNS FROM produtos');
            $colunasProdutosRaw = $stmtColunasProdutos ? $stmtColunasProdutos->fetchAll(PDO::FETCH_ASSOC) : [];
            $colunasProdutosMap = [];
            foreach ($colunasProdutosRaw as $colunaProduto) {
                $campoProduto = (string) ($colunaProduto['Field'] ?? '');
                if ($campoProduto !== '') {
                    $colunasProdutosMap[$campoProduto] = true;
                }
            }

            $colunasProdutoInsert = ['usuario_id', 'nome', 'categoria', 'imagem_capa', 'imagens', 'descricao'];
            $valoresProdutoInsert = [
                $usuarioId,
                $nome,
                $categoriaId,
                $foto,
                $imagensJson,
                $descricaoProduto !== '' ? $descricaoProduto : null,
            ];

            if (isset($colunasProdutosMap['observacoes'])) {
                $colunasProdutoInsert[] = 'observacoes';
                $valoresProdutoInsert[] = $observacoes !== '' ? $observacoes : null;
            }

            if (isset($colunasProdutosMap['markup_lojista'])) {
                $colunasProdutoInsert[] = 'markup_lojista';
                $valoresProdutoInsert[] = $markupLojista;
            }
            if (isset($colunasProdutosMap['markup_consumidor_final'])) {
                $colunasProdutoInsert[] = 'markup_consumidor_final';
                $valoresProdutoInsert[] = $markupConsumidorFinal;
            }
            if (isset($colunasProdutosMap['markup']) && !isset($colunasProdutosMap['markup_lojista'])) {
                $colunasProdutoInsert[] = 'markup';
                $valoresProdutoInsert[] = $markupConsumidorFinal;
            }

            if (isset($colunasProdutosMap['lucro_lojista'])) {
                $colunasProdutoInsert[] = 'lucro_lojista';
                $valoresProdutoInsert[] = $lucroLojista;
            }

            if (isset($colunasProdutosMap['lucro_consumidor_final'])) {
                $colunasProdutoInsert[] = 'lucro_consumidor_final';
                $valoresProdutoInsert[] = $lucroConsumidorFinal;
            }

            $colunasProdutoInsert[] = 'preco_lojista';
            $valoresProdutoInsert[] = $precoLojista;
            $colunasProdutoInsert[] = 'preco_consumidor_final';
            $valoresProdutoInsert[] = $precoConsumidorFinal;

            $placeholdersProduto = implode(', ', array_fill(0, count($colunasProdutoInsert), '?'));
            $sqlProduto = 'INSERT INTO produtos (' . implode(', ', $colunasProdutoInsert) . ') VALUES (' . $placeholdersProduto . ')';
            $stmtProduto = $this->pdo->prepare($sqlProduto);
            $stmtProduto->execute($valoresProdutoInsert);
            $produtoId = (int) $this->pdo->lastInsertId();

            $estudioResolvido = $this->resolverEstudio($usuarioId, $estudio);
            $colecaoPrincipal = (string) ($colecoesSelecionadas[0] ?? '');
            $colecaoResolvida = $this->resolverColecao($usuarioId, (int) ($estudioResolvido['id'] ?? 0), $colecaoPrincipal);
            $tematicaResolvida = $this->resolverTematica($tematica);

            $estudioIdTorre = (int) ($estudioResolvido['id'] ?? ($colecaoResolvida['estudio_id'] ?? 0));
            $colecaoIdTorre = (int) ($colecaoResolvida['id'] ?? 0);
            $tematicaIdTorre = (int) ($tematicaResolvida['id'] ?? 0);
            $tematicaNomeTorre = (string) ($tematicaResolvida['nome'] ?? $tematica);
            $estudioNomeParaSku = (string) ($estudioResolvido['nome'] ?? $estudio);
            $skuCodigoTorre = $this->gerarSkuTorre($estudioNomeParaSku);

            $stmtInsertSku = $this->pdo->prepare('INSERT INTO sku (produto_id, sku, usuario_id) VALUES (?, ?, ?)');
            $stmtInsertSku->execute([$produtoId, $skuCodigoTorre, $usuarioId]);

            $markupImpressao = max(1, (int) round($markupConsumidorFinal));
            $taxaFalhaImpressao = max(1, (int) round($taxaFalha));
            $pesoMaterial = max(1, (int) round($gramas));
            $porcentagemLucro = $custoTotalImpressao > 0 ? (int) round(($lucroTotal / $custoTotalImpressao) * 100) : 0;
            $materialTipo = (string) ($selecaoConfirmacao['material_tipo'] ?? '');
            $filamentoIdImpressao = $materialTipo === 'Filamento' ? (int) ($selecaoConfirmacao['material']['id'] ?? 0) : null;
            $resinaIdImpressao = $materialTipo === 'Resina' ? (int) ($selecaoConfirmacao['material']['id'] ?? 0) : null;

            $stmtInsertCusto = $this->pdo->prepare('INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)');
            $stmtInsertCusto->execute([$produtoId, $custoTotalImpressao, $custoPorUnidade]);

            $stmtImpressao = $this->pdo->prepare("INSERT INTO impressoes
              (impressora_id, tempo_impressao, unidades_produzidas, markup, taxa_falha, valor_energia, peso_material, custo_material, custo_lavagem_alcool, custo_energia, depreciacao, custo_total_impressao, custo_por_unidade, lucro_total_impressao, lucro_por_unidade, porcentagem_lucro, preco_venda_sugerido, preco_venda_sugerido_unidade, observacoes, usuario_id, filamento_id, resina_id)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtImpressao->execute([
                (int) ($selecaoConfirmacao['impressora']['id'] ?? 0),
                $tempoTotalMin,
                $unidadesProduzidas,
                $markupImpressao,
                $taxaFalhaImpressao,
                $valorKwh,
                $pesoMaterial,
                round($custoMaterial, 2),
                round($custoLavagemAlcool, 2),
                round($custoEnergia, 2),
                round($custoDepreciacao, 2),
                $custoTotalImpressao,
                $custoPorUnidade,
                $lucroTotal,
                $lucroPorUnidade,
                $porcentagemLucro,
                $precoVendaSugerido,
                $precoVendaSugeridoUnidade,
                $observacoes !== '' ? $observacoes : null,
                $usuarioId,
                $filamentoIdImpressao,
                $resinaIdImpressao,
            ]);
            $impressaoId = (int) $this->pdo->lastInsertId();

            $stmtTorre = $this->pdo->prepare('INSERT INTO torres (id_sku, produto_id, usuario_id, id_impressao, nome_original, id_estudio, id_colecao, id_tematica, tematica, capa, imagens, outras_caracteristicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmtTorre->execute([
                $skuCodigoTorre,
                $produtoId,
                $usuarioId,
                $impressaoId > 0 ? $impressaoId : null,
                $nomeOriginal !== '' ? $nomeOriginal : null,
                $estudioIdTorre > 0 ? $estudioIdTorre : null,
                $colecaoIdTorre > 0 ? $colecaoIdTorre : null,
                $tematicaIdTorre > 0 ? $tematicaIdTorre : null,
                $tematicaNomeTorre !== '' ? $tematicaNomeTorre : null,
                $foto,
                $imagensJson,
                $outrasCaracteristicas !== '' ? $outrasCaracteristicas : null,
            ]);
            $torreId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return ['sucesso' => true, 'erro' => null, 'torre_id' => $torreId];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar torre: ' . $e->getMessage()];
        }
    }

    public function editar(int $id, int $usuarioId, array $dados, array $compatibilidade, int $produtoId): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $nomeOriginal = trim((string) ($dados['nome_original'] ?? ''));
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $markupLojista = (float) str_replace(',', '.', trim((string) ($dados['markup_lojista'] ?? '0')));
        $markupConsumidorFinal = (float) str_replace(',', '.', trim((string) ($dados['markup_consumidor_final'] ?? '0')));
        $precoLojista = (float) str_replace(',', '.', trim((string) ($dados['preco_lojista'] ?? '0')));
        $precoConsumidorFinal = (float) str_replace(',', '.', trim((string) ($dados['preco_consumidor_final'] ?? '0')));

        if ($nome === '') {
            return ['sucesso' => false, 'erro' => 'Preencha o nome da torre.'];
        }

        if ($markupLojista < 0 || $markupConsumidorFinal < 0 || $precoLojista < 0 || $precoConsumidorFinal < 0) {
            return ['sucesso' => false, 'erro' => 'Informe valores numéricos válidos (maiores ou iguais a zero).'];
        }

        try {
            $this->pdo->beginTransaction();

            $this->repository->atualizarNomeOriginal($id, $usuarioId, $nomeOriginal !== '' ? $nomeOriginal : null);
            $this->repository->atualizarProdutoDaTorre(
                $produtoId,
                $usuarioId,
                [
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'observacoes' => $observacoes,
                    'markup_lojista' => $markupLojista,
                    'markup_consumidor_final' => $markupConsumidorFinal,
                    'preco_lojista' => $precoLojista,
                    'preco_consumidor_final' => $precoConsumidorFinal,
                ],
                $compatibilidade
            );

            $this->pdo->commit();

            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao editar torre: ' . $e->getMessage()];
        }
    }

    public function excluir(int $id, int $usuarioId, string $raizProjeto): array
    {
        $torre = $this->repository->buscarParaExclusao($id, $usuarioId);
        if (!$torre) {
            return ['sucesso' => false, 'erro' => 'Torre não encontrada!'];
        }

        try {
            $this->pdo->beginTransaction();

            $excluiu = $this->repository->excluirProduto((int) $torre['produto_id'], $usuarioId);
            if (!$excluiu) {
                throw new \RuntimeException('Torre não encontrada ou sem permissão para excluir.');
            }

            $this->pdo->commit();

            $this->removerArquivosRelacionados($raizProjeto, $torre);

            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }

    private function caminhoRelativoSeguro(string $caminho): ?string
    {
        $caminho = trim(str_replace('\\', '/', $caminho));
        if ($caminho === '') {
            return null;
        }

        $caminho = ltrim($caminho, '/');
        if (strpos($caminho, '..') !== false) {
            return null;
        }

        if (strpos($caminho, 'uploads/') !== 0) {
            return null;
        }

        return $caminho;
    }

    private function removerArquivoSeExistir(string $raizProjeto, string $caminhoRelativo): void
    {
        $arquivo = rtrim($raizProjeto, '/') . '/' . $caminhoRelativo;
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }

    private function removerDerivadosImagem(string $raizProjeto, string $caminhoOriginal): void
    {
        $caminhoRelativo = $this->caminhoRelativoSeguro($caminhoOriginal);
        if ($caminhoRelativo === null) {
            return;
        }

        $this->removerArquivoSeExistir($raizProjeto, $caminhoRelativo);

        $arquivo = basename($caminhoRelativo);
        $nomeSemExtensao = pathinfo($arquivo, PATHINFO_FILENAME);
        $diretorioRelativo = dirname($caminhoRelativo);
        if ($diretorioRelativo === '.' || $diretorioRelativo === '') {
            return;
        }
        $nomeBase = preg_replace('/_(thumb|thumbnail|pequena|media|grande)$/i', '', $nomeSemExtensao);
        $sufixos = ['thumb', 'thumbnail', 'pequena', 'media', 'grande'];
        $extensoes = ['png', 'jpg', 'jpeg', 'webp'];

        foreach ($sufixos as $sufixo) {
            foreach ($extensoes as $extensao) {
                $this->removerArquivoSeExistir($raizProjeto, $diretorioRelativo . '/' . $nomeBase . '_' . $sufixo . '.' . $extensao);
            }
        }
    }

    private function removerArquivosRelacionados(string $raizProjeto, array $torre): void
    {
        if (!empty($torre['imagem_capa'])) {
            $this->removerDerivadosImagem($raizProjeto, (string) $torre['imagem_capa']);
        }

        $imagensCampo = (string) ($torre['imagens'] ?? '');
        if ($imagensCampo === '') {
            return;
        }

        $imagensDecodificadas = json_decode($imagensCampo, true);
        if (is_array($imagensDecodificadas)) {
            foreach ($imagensDecodificadas as $imagemPath) {
                if (is_string($imagemPath) && $imagemPath !== '') {
                    $this->removerDerivadosImagem($raizProjeto, $imagemPath);
                }
            }
            return;
        }

        $this->removerDerivadosImagem($raizProjeto, $imagensCampo);
    }

    private function resolverEstudio(int $usuarioId, string $entrada): ?array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            return null;
        }

        $stmtEstudio = $this->pdo->prepare('SELECT id, nome FROM estudios WHERE usuario_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1');
        $stmtEstudio->execute([$usuarioId, $entrada]);
        $estudio = $stmtEstudio->fetch(PDO::FETCH_ASSOC);

        if ($estudio) {
            return $estudio;
        }

        $stmtNovoEstudio = $this->pdo->prepare('INSERT INTO estudios (nome, site, usuario_id) VALUES (?, ?, ?)');
        $stmtNovoEstudio->execute([$entrada, 'https://pendente.local', $usuarioId]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'nome' => $entrada,
        ];
    }

    private function resolverColecao(int $usuarioId, int $estudioId, string $entrada): ?array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            return null;
        }

        if ($estudioId > 0) {
            $stmtColecao = $this->pdo->prepare("SELECT c.id, c.nome, c.estudio_id
              FROM colecoes c
              WHERE c.usuario_id = ?
                AND c.estudio_id = ?
                AND LOWER(c.nome) = LOWER(?)
              LIMIT 1");
            $stmtColecao->execute([$usuarioId, $estudioId, $entrada]);
            $colecao = $stmtColecao->fetch(PDO::FETCH_ASSOC);

            if ($colecao) {
                return $colecao;
            }

            $stmtNovaColecao = $this->pdo->prepare('INSERT INTO colecoes (estudio_id, nome, usuario_id) VALUES (?, ?, ?)');
            $stmtNovaColecao->execute([$estudioId, $entrada, $usuarioId]);

            return [
                'id' => (int) $this->pdo->lastInsertId(),
                'nome' => $entrada,
                'estudio_id' => $estudioId,
            ];
        }

        $stmtColecaoSemEstudio = $this->pdo->prepare("SELECT c.id, c.nome, c.estudio_id
            FROM colecoes c
            WHERE c.usuario_id = ?
              AND LOWER(c.nome) = LOWER(?)
            ORDER BY c.id DESC
            LIMIT 1");
        $stmtColecaoSemEstudio->execute([$usuarioId, $entrada]);
        $colecaoSemEstudio = $stmtColecaoSemEstudio->fetch(PDO::FETCH_ASSOC);

        return $colecaoSemEstudio ?: null;
    }

    private function resolverTematica(string $entrada): ?array
    {
        $entrada = trim($entrada);
        if ($entrada === '') {
            return null;
        }

        $stmtTematica = $this->pdo->prepare('SELECT id, nome FROM tematicas WHERE LOWER(nome) = LOWER(?) LIMIT 1');
        $stmtTematica->execute([$entrada]);
        $tematica = $stmtTematica->fetch(PDO::FETCH_ASSOC);

        if ($tematica) {
            return $tematica;
        }

        $stmtNovaTematica = $this->pdo->prepare('INSERT INTO tematicas (nome) VALUES (?)');
        $stmtNovaTematica->execute([$entrada]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'nome' => $entrada,
        ];
    }

    private function gerarSiglaEstudio(string $estudioNome): string
    {
        $estudioNome = trim($estudioNome);
        if ($estudioNome === '') {
            return 'XXX';
        }

        $partesOriginais = preg_split('/\s+/', $estudioNome) ?: [];
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

    private function gerarSkuTorre(string $estudioNome): string
    {
        $prefixo = 'TOR-' . $this->gerarSiglaEstudio($estudioNome);

        do {
            $numero = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sku = $prefixo . '-' . $numero;
            $stmtSku = $this->pdo->prepare('SELECT COUNT(*) FROM sku WHERE sku = ?');
            $stmtSku->execute([$sku]);
            $existe = (int) $stmtSku->fetchColumn() > 0;
        } while ($existe);

        return $sku;
    }
}
