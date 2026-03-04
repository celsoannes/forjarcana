<?php

namespace App\Produtos;

use PDO;

class ProdutoService
{
    private ProdutoRepository $repository;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new ProdutoRepository($pdo);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->repository->listarPorUsuario($usuarioId);
    }

    public function buscarParaEdicao(int $id, int $usuarioId): ?array
    {
        $compatibilidade = $this->repository->obterCompatibilidadeCamposEdicao();
        $produto = $this->repository->buscarParaEdicao($id, $usuarioId, $compatibilidade);
        if (!$produto) {
            return null;
        }

        $produto['__compatibilidade'] = $compatibilidade;
        return $produto;
    }

    public function editar(int $id, int $usuarioId, array $dados, array $compatibilidade): array
    {
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $custoTotal = (float) str_replace(',', '.', trim((string) ($dados['custo_total'] ?? '0')));
        $custoPorUnidade = (float) str_replace(',', '.', trim((string) ($dados['custo_por_unidade'] ?? '0')));
        $markupLojista = (float) str_replace(',', '.', trim((string) ($dados['markup_lojista'] ?? '0')));
        $markupConsumidorFinal = (float) str_replace(',', '.', trim((string) ($dados['markup_consumidor_final'] ?? '0')));
        $precoLojista = (float) str_replace(',', '.', trim((string) ($dados['preco_lojista'] ?? '0')));
        $precoConsumidorFinal = (float) str_replace(',', '.', trim((string) ($dados['preco_consumidor_final'] ?? '0')));

        if ($custoTotal < 0 || $custoPorUnidade < 0 || $markupLojista < 0 || $markupConsumidorFinal < 0 || $precoLojista < 0 || $precoConsumidorFinal < 0) {
            return ['sucesso' => false, 'erro' => 'Informe valores numéricos válidos (maiores ou iguais a zero).'];
        }

        try {
            $this->pdo->beginTransaction();

            $this->repository->atualizarOuInserirCustos($id, $custoTotal, $custoPorUnidade);

            $this->repository->atualizarProdutoEdicao(
                $id,
                $usuarioId,
                [
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

            return ['sucesso' => false, 'erro' => 'Erro ao editar produto: ' . $e->getMessage()];
        }
    }

    public function excluirComDependencias(int $id, int $usuarioId, string $raizProjeto): array
    {
        $produto = $this->repository->buscarResumoParaExclusao($id, $usuarioId);
        if (!$produto) {
            return ['sucesso' => false, 'erro' => 'Produto não encontrado!'];
        }

        $torresImagens = $this->repository->buscarImagensTorres($id, $usuarioId);
        $mapasImagens = $this->repository->buscarImagensMapas($id, $usuarioId);

        try {
            $this->pdo->beginTransaction();

            $skuCodigo = (string) ($produto['sku_codigo'] ?? '');
            $this->repository->excluirDependenciasPorProduto($id, $usuarioId, $skuCodigo);

            $excluiu = $this->repository->excluirProduto($id, $usuarioId);
            if (!$excluiu) {
                throw new \RuntimeException('Produto não encontrado ou sem permissão para excluir.');
            }

            $this->pdo->commit();

            $this->removerArquivosRelacionados($raizProjeto, $produto, $torresImagens, $mapasImagens);

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
        $diretorioRelativo = dirname($caminhoRelativo);
        $diretorioAbsoluto = rtrim($raizProjeto, '/') . '/' . $diretorioRelativo;

        if (!is_dir($diretorioAbsoluto)) {
            return;
        }

        $nomeSemExtensao = pathinfo($arquivo, PATHINFO_FILENAME);
        $nomeBase = preg_replace('/_(thumb|thumbnail|pequena|media|grande)$/i', '', $nomeSemExtensao);
        $sufixos = ['thumb', 'thumbnail', 'pequena', 'media', 'grande'];
        $extensoes = ['png', 'jpg', 'jpeg', 'webp'];
        $extensaoOriginal = strtolower((string) pathinfo($arquivo, PATHINFO_EXTENSION));

        if ($extensaoOriginal !== '' && !in_array($extensaoOriginal, $extensoes, true)) {
            $extensoes[] = $extensaoOriginal;
        }

        foreach ($sufixos as $sufixo) {
            foreach ($extensoes as $extensao) {
                $caminhoDerivado = $diretorioRelativo . '/' . $nomeBase . '_' . $sufixo . '.' . $extensao;
                $this->removerArquivoSeExistir($raizProjeto, $caminhoDerivado);
            }
        }
    }

    private function removerCampoImagens(string $raizProjeto, ?string $valorCampo): void
    {
        $valorCampo = trim((string) $valorCampo);
        if ($valorCampo === '') {
            return;
        }

        $imagensDecodificadas = json_decode($valorCampo, true);
        if (is_array($imagensDecodificadas)) {
            foreach ($imagensDecodificadas as $imagemPath) {
                if (is_string($imagemPath) && trim($imagemPath) !== '') {
                    $this->removerDerivadosImagem($raizProjeto, $imagemPath);
                }
            }
            return;
        }

        $this->removerDerivadosImagem($raizProjeto, $valorCampo);
    }

    private function removerArquivosRelacionados(string $raizProjeto, array $produto, array $torresImagens, array $mapasImagens): void
    {
        if (!empty($produto['imagem_capa'])) {
            $this->removerDerivadosImagem($raizProjeto, (string) $produto['imagem_capa']);
        }

        $this->removerCampoImagens($raizProjeto, (string) ($produto['imagens'] ?? ''));

        foreach ($torresImagens as $torreImagem) {
            if (!empty($torreImagem['capa'])) {
                $this->removerDerivadosImagem($raizProjeto, (string) $torreImagem['capa']);
            }
            $this->removerCampoImagens($raizProjeto, (string) ($torreImagem['imagens'] ?? ''));
        }

        foreach ($mapasImagens as $mapaImagem) {
            if (!empty($mapaImagem['imagem_capa'])) {
                $this->removerDerivadosImagem($raizProjeto, (string) $mapaImagem['imagem_capa']);
            }
            $this->removerCampoImagens($raizProjeto, (string) ($mapaImagem['imagens'] ?? ''));
        }
    }
}
