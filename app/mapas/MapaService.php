<?php

namespace App\Mapas;

use PDO;

class MapaService
{
    private MapaRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new MapaRepository($pdo);
    }

    public function carregarContextoAdicao(int $usuarioId, string $usuarioUuid): array
    {
        $resultado = [
            'usuario_uuid' => $usuarioUuid,
            'fornecedores_disponiveis' => [],
        ];

        if ($resultado['usuario_uuid'] === '' && $usuarioId > 0) {
            try {
                $resultado['usuario_uuid'] = (string) ($this->repository->buscarUuidUsuario($usuarioId) ?? '');
            } catch (\Throwable $e) {
                $resultado['usuario_uuid'] = '';
            }
        }

        try {
            $resultado['fornecedores_disponiveis'] = $this->repository->listarFornecedoresPorUsuario($usuarioId);
        } catch (\Throwable $e) {
            $resultado['fornecedores_disponiveis'] = [];
        }

        return $resultado;
    }

    public function sugerirCampo(int $usuarioId, string $campo, string $termo): array
    {
        if ($usuarioId <= 0 || $campo !== 'fornecedor') {
            return [];
        }

        $tamanhoTermo = function_exists('mb_strlen') ? mb_strlen($termo, 'UTF-8') : strlen($termo);
        if ($tamanhoTermo < 2) {
            return [];
        }

        try {
            $fornecedores = $this->repository->sugerirFornecedores($usuarioId, $termo);

            $sugestoes = [];
            $controleUnicos = [];
            foreach ($fornecedores as $fornecedorNome) {
                if (!is_string($fornecedorNome)) {
                    continue;
                }

                $fornecedorNome = trim($fornecedorNome);
                if ($fornecedorNome === '') {
                    continue;
                }

                $chave = function_exists('mb_strtolower') ? mb_strtolower($fornecedorNome, 'UTF-8') : strtolower($fornecedorNome);
                if (isset($controleUnicos[$chave])) {
                    continue;
                }

                $controleUnicos[$chave] = true;
                $sugestoes[] = $fornecedorNome;
            }

            return $sugestoes;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function parseDadosAdicao(array $post): array
    {
        $fotoExistente = trim((string) ($post['foto_existente'] ?? ''));
        $foto = $fotoExistente !== '' ? $fotoExistente : null;

        $imagens = [];
        $imagensExistentesRaw = trim((string) ($post['imagens_existentes'] ?? ''));
        if ($imagensExistentesRaw !== '') {
            $imagensExistentes = json_decode($imagensExistentesRaw, true);
            if (is_array($imagensExistentes)) {
                foreach ($imagensExistentes as $imagemExistente) {
                    if (is_string($imagemExistente) && trim($imagemExistente) !== '') {
                        $imagens[] = trim($imagemExistente);
                    }
                }
            }
        }

        $markup = (float) str_replace(',', '.', trim((string) ($post['markup'] ?? '2')));
        $markupValido = ($markup >= 1 && $markup <= 10 && ((int) round($markup * 2)) === (int) ($markup * 2));

        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'link_compra' => trim((string) ($post['link_compra'] ?? '')),
            'formato_grade' => trim((string) ($post['formato_grade'] ?? '')),
            'largura' => (float) ($post['largura'] ?? 0),
            'comprimento' => (float) ($post['comprimento'] ?? 0),
            'material' => trim((string) ($post['material'] ?? '')),
            'fornecedor' => trim((string) ($post['fornecedor'] ?? '')),
            'custo' => (float) ($post['custo'] ?? 0),
            'unidades_produzidas' => (int) ($post['unidades_produzidas'] ?? 0),
            'markup' => $markup,
            'markup_valido' => $markupValido,
            'foto' => $foto,
            'imagens' => $imagens,
        ];
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'fornecedor' => trim((string) ($post['fornecedor'] ?? '')),
            'material' => trim((string) ($post['material'] ?? '')),
            'formato_grade' => trim((string) ($post['formato_grade'] ?? '')),
            'largura' => trim((string) ($post['largura'] ?? '')),
            'comprimento' => trim((string) ($post['comprimento'] ?? '')),
            'custo' => trim((string) ($post['custo'] ?? '')),
            'unidades_produzidas' => trim((string) ($post['unidades_produzidas'] ?? '1')),
            'markup' => trim((string) ($post['markup'] ?? '2')),
            'link_compra' => trim((string) ($post['link_compra'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $linkCompra = trim((string) ($dados['link_compra'] ?? ''));
        $formatoGrade = trim((string) ($dados['formato_grade'] ?? ''));
        $largura = (float) ($dados['largura'] ?? 0);
        $comprimento = (float) ($dados['comprimento'] ?? 0);
        $material = trim((string) ($dados['material'] ?? ''));
        $custo = (float) ($dados['custo'] ?? 0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $markupValido = !empty($dados['markup_valido']);

        if (!$nome || !$formatoGrade || $largura <= 0 || $comprimento <= 0 || !$material || $custo < 0 || $unidadesProduzidas <= 0 || !$markupValido) {
            return 'Preencha todos os campos obrigatórios corretamente.';
        }

        if ($linkCompra !== '' && !filter_var($linkCompra, FILTER_VALIDATE_URL)) {
            return 'Informe uma URL válida para o link de compra.';
        }

        return '';
    }

    public function processarUploadsAdicao(string $usuarioUuid, array $files, ?string $fotoAtual, array $imagensAtuais): array
    {
        $resultado = [
            'erro' => '',
            'foto' => $fotoAtual,
            'imagens' => $imagensAtuais,
            'avisos_upload' => [],
        ];

        if ($usuarioUuid === '') {
            $resultado['erro'] = 'Não foi possível identificar o UUID do usuário para upload da imagem de capa.';
            return $resultado;
        }

        $tamanhosUpload = [
            'thumbnail' => [150, 150, 'crop'],
            'pequena' => [300, 300, 'proporcional'],
            'media' => [300, 300, 'proporcional'],
            'grande' => [1024, 1024, 'proporcional'],
        ];

        if (isset($files['foto']) && ($files['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $fotoUpload = uploadImagem($files['foto'], $usuarioUuid, 'usuarios', $tamanhosUpload, 'mapa_CAPA', false);
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
                    $resultado['avisos_upload'][] = 'A imagem adicional "' . $nomeArquivo . '" não foi enviada: ' . $this->descreverErroUploadMapa((int) $erroArquivo);
                    continue;
                }

                $arquivoImagem = [
                    'name' => $nomeArquivo,
                    'type' => $files['fotos']['type'][$i] ?? '',
                    'tmp_name' => $files['fotos']['tmp_name'][$i] ?? '',
                    'error' => $erroArquivo,
                    'size' => $files['fotos']['size'][$i] ?? 0,
                ];

                $imagemUpload = uploadImagem($arquivoImagem, $usuarioUuid, 'usuarios', $tamanhosUpload, 'mapa_IMAGEM', false);
                if ($imagemUpload === false) {
                    $resultado['avisos_upload'][] = 'A imagem adicional "' . $nomeArquivo . '" não pôde ser processada (formato ou conteúdo inválido).';
                    continue;
                }

                $resultado['imagens'][] = $imagemUpload;
            }
        }

        return $resultado;
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files): array
    {
        $dados = $this->parseDadosAdicao($post);
        $foto = $dados['foto'] ?? null;
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];

        if ($usuarioId <= 0) {
            return [
                'sucesso' => false,
                'erro' => 'Usuário inválido para cadastro do mapa.',
                'foto' => $foto,
                'imagens' => $imagens,
                'avisos_upload' => [],
            ];
        }

        $erro = '';
        $avisosUpload = [];

        $resultadoUpload = $this->processarUploadsAdicao($usuarioUuid, $files, $foto, $imagens);
        $foto = $resultadoUpload['foto'] ?? $foto;
        $imagens = is_array($resultadoUpload['imagens'] ?? null) ? $resultadoUpload['imagens'] : $imagens;
        $avisosUpload = is_array($resultadoUpload['avisos_upload'] ?? null) ? $resultadoUpload['avisos_upload'] : [];

        $erroUpload = trim((string) ($resultadoUpload['erro'] ?? ''));
        if ($erroUpload !== '') {
            $erro = $erroUpload;
        }

        if ($erro === '') {
            $erro = $this->validarDadosAdicao($dados);
        }

        if ($erro === '') {
            $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, [
                'nome' => (string) ($dados['nome'] ?? ''),
                'descricao' => (string) ($dados['descricao'] ?? ''),
                'link_compra' => (string) ($dados['link_compra'] ?? ''),
                'formato_grade' => (string) ($dados['formato_grade'] ?? ''),
                'largura' => (float) ($dados['largura'] ?? 0),
                'comprimento' => (float) ($dados['comprimento'] ?? 0),
                'material' => (string) ($dados['material'] ?? ''),
                'fornecedor' => (string) ($dados['fornecedor'] ?? ''),
                'custo' => (float) ($dados['custo'] ?? 0),
                'unidades_produzidas' => (int) ($dados['unidades_produzidas'] ?? 0),
                'markup' => (float) ($dados['markup'] ?? 2),
                'foto' => $foto,
                'imagens' => $imagens,
            ]);

            if (!empty($resultadoCadastro['sucesso'])) {
                return [
                    'sucesso' => true,
                    'erro' => '',
                    'foto' => $foto,
                    'imagens' => $imagens,
                    'avisos_upload' => $avisosUpload,
                ];
            }

            $erro = trim((string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar mapa.'));
        }

        return [
            'sucesso' => false,
            'erro' => $erro,
            'foto' => $foto,
            'imagens' => $imagens,
            'avisos_upload' => $avisosUpload,
        ];
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $linkCompra = trim((string) ($dados['link_compra'] ?? ''));
        $formatoGrade = trim((string) ($dados['formato_grade'] ?? ''));
        $largura = (float) ($dados['largura'] ?? 0);
        $comprimento = (float) ($dados['comprimento'] ?? 0);
        $material = trim((string) ($dados['material'] ?? ''));
        $fornecedor = trim((string) ($dados['fornecedor'] ?? ''));
        $custo = (float) ($dados['custo'] ?? 0);
        $unidadesProduzidas = (int) ($dados['unidades_produzidas'] ?? 0);
        $markup = (float) ($dados['markup'] ?? 2);
        $foto = $dados['foto'] ?? null;
        $imagens = is_array($dados['imagens'] ?? null) ? $dados['imagens'] : [];

        $fornecedorId = null;

        try {
            $this->repository->iniciarTransacao();

            if ($fornecedor !== '') {
                $fornecedorId = $this->repository->buscarFornecedorIdPorNome($usuarioId, $fornecedor);
            }

            $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;

            $possuiColunaLinkCompra = $this->repository->tabelaTemColuna('mapas', 'link_compra');
            $possuiColunaFornecedorId = $this->repository->tabelaTemColuna('mapas', 'fornecedor_id');
            $possuiColunaUnidadesProduzidas = $this->repository->tabelaTemColuna('mapas', 'unidades_produzidas');

            if ($possuiColunaFornecedorId && $fornecedor !== '' && !$fornecedorId) {
                throw new \RuntimeException('Fornecedor informado não está cadastrado.');
            }

            $categoriaId = $this->repository->buscarCategoriaIdPorNome('Mapas');
            if ($categoriaId === 0) {
                $categoriaId = $this->repository->inserirCategoria('Mapas');
            }

            $skuCodigo = $this->gerarSkuMapaAutomatico($nome, $formatoGrade, $largura, $comprimento);

            $custoPorUnidade = $unidadesProduzidas > 0 ? round($custo / $unidadesProduzidas, 2) : 0.00;
            $precoConsumidorFinal = round($custoPorUnidade * $markup, 2);
            $lucro = $precoConsumidorFinal - $custoPorUnidade;
            $precoLojista = round(($lucro / 2) + $custoPorUnidade, 2);
            $lucroLojista = round($precoLojista - $custoPorUnidade, 2);
            $lucroConsumidorFinal = round($precoConsumidorFinal - $custoPorUnidade, 2);

            $colunasProdutosMap = $this->repository->listarColunasProdutos();
            $colunasProdutoInsert = ['usuario_id', 'nome', 'categoria', 'imagem_capa', 'imagens', 'descricao'];
            $valoresProdutoInsert = [
                $usuarioId,
                $nome,
                $categoriaId,
                $foto,
                $imagensJson,
                $descricao !== '' ? $descricao : null,
            ];

            if (isset($colunasProdutosMap['observacoes'])) {
                $colunasProdutoInsert[] = 'observacoes';
                $valoresProdutoInsert[] = null;
            }

            if (isset($colunasProdutosMap['markup_lojista'])) {
                $colunasProdutoInsert[] = 'markup_lojista';
                $valoresProdutoInsert[] = $markup;
            }
            if (isset($colunasProdutosMap['markup_consumidor_final'])) {
                $colunasProdutoInsert[] = 'markup_consumidor_final';
                $valoresProdutoInsert[] = $markup;
            }
            if (isset($colunasProdutosMap['markup']) && !isset($colunasProdutosMap['markup_lojista'])) {
                $colunasProdutoInsert[] = 'markup';
                $valoresProdutoInsert[] = $markup;
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

            $produtoId = $this->repository->inserirProduto($colunasProdutoInsert, $valoresProdutoInsert);

            $this->repository->inserirSku($produtoId, $skuCodigo, $usuarioId);
            $this->repository->inserirCusto($produtoId, $custo, $custoPorUnidade);

            $colunasInsert = ['usuario_id', 'nome', 'descricao'];
            $valoresInsert = [$usuarioId, $nome, $descricao];

            $possuiColunaProdutoId = $this->repository->tabelaTemColuna('mapas', 'produto_id');
            $possuiColunaIdSku = $this->repository->tabelaTemColuna('mapas', 'id_sku');
            $possuiColunaCusto = $this->repository->tabelaTemColuna('mapas', 'custo');

            if ($possuiColunaIdSku) {
                $colunasInsert[] = 'id_sku';
                $valoresInsert[] = $skuCodigo;
            }

            if ($possuiColunaProdutoId) {
                $colunasInsert[] = 'produto_id';
                $valoresInsert[] = $produtoId;
            }

            if ($possuiColunaLinkCompra) {
                $colunasInsert[] = 'link_compra';
                $valoresInsert[] = $linkCompra !== '' ? $linkCompra : null;
            }

            $colunasInsert[] = 'imagem_capa';
            $valoresInsert[] = $foto;
            $colunasInsert[] = 'imagens';
            $valoresInsert[] = $imagensJson;
            $colunasInsert[] = 'formato_grade';
            $valoresInsert[] = $formatoGrade;
            $colunasInsert[] = 'largura';
            $valoresInsert[] = $largura;
            $colunasInsert[] = 'comprimento';
            $valoresInsert[] = $comprimento;
            $colunasInsert[] = 'material';
            $valoresInsert[] = $material;

            if ($possuiColunaFornecedorId) {
                $colunasInsert[] = 'fornecedor_id';
                $valoresInsert[] = $fornecedorId;
            } else {
                $colunasInsert[] = 'fornecedor';
                $valoresInsert[] = $fornecedor !== '' ? $fornecedor : null;
            }

            if ($possuiColunaCusto) {
                $colunasInsert[] = 'custo';
                $valoresInsert[] = $custo;
            }

            if ($possuiColunaUnidadesProduzidas) {
                $colunasInsert[] = 'unidades_produzidas';
                $valoresInsert[] = $unidadesProduzidas;
            }

            $this->repository->inserirMapa($colunasInsert, $valoresInsert);

            $this->repository->confirmarTransacao();
            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            if ($this->repository->emTransacao()) {
                $this->repository->desfazerTransacao();
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    private function gerarSkuMapaAutomatico(string $nome, string $formatoGrade, float $largura, float $comprimento): string
    {
        $blocoNome = $this->gerarIniciaisNomeMapa($nome);
        $blocoFormatoGrade = $this->formatarBlocoFormatoGradeSku($formatoGrade);
        $blocoLargura = $this->formatarBlocoDimensaoSku($largura);
        $blocoComprimento = $this->formatarBlocoDimensaoSku($comprimento);
        $prefixo = 'MAP-' . $blocoNome . '-' . $blocoFormatoGrade . '-' . $blocoLargura . '-' . $blocoComprimento;

        do {
            $numero = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sku = $prefixo . '-' . $numero;
            $existe = $this->repository->contarSkuPorCodigo($sku) > 0;
        } while ($existe);

        return $sku;
    }

    private function normalizarTextoSkuMapa(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($normalizado === false) {
            $normalizado = $texto;
        }

        $normalizado = strtoupper($normalizado);
        $normalizado = preg_replace('/[^A-Z0-9\s]/', '', $normalizado);

        return trim((string) $normalizado);
    }

    private function gerarIniciaisNomeMapa(string $nome): string
    {
        $nomeNormalizado = $this->normalizarTextoSkuMapa($nome);
        if ($nomeNormalizado === '') {
            return 'XX';
        }

        $partes = preg_split('/\s+/', $nomeNormalizado) ?: [];
        $iniciais = '';

        foreach ($partes as $parte) {
            $parte = trim((string) $parte);
            if ($parte === '') {
                continue;
            }
            $iniciais .= substr($parte, 0, 1);
        }

        if ($iniciais === '') {
            return 'XX';
        }

        return $iniciais;
    }

    private function formatarBlocoDimensaoSku(float $valor): string
    {
        $texto = number_format($valor, 2, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');
        if ($texto === '') {
            $texto = '0';
        }
        $texto = str_replace('.', 'P', $texto);

        return strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $texto));
    }

    private function formatarBlocoFormatoGradeSku(string $formatoGrade): string
    {
        $formatoGrade = strtoupper(trim($formatoGrade));
        $formatoGrade = preg_replace('/[^A-Z0-9]/', '', $formatoGrade);

        return $formatoGrade !== '' ? $formatoGrade : 'SEMGRD';
    }

    private function descreverErroUploadMapa(int $codigoErro): string
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
}
