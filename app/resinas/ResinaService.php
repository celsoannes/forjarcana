<?php

namespace App\Resinas;

use PDO;

class ResinaService
{
    private ResinaRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ResinaRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'preco_kilo' => trim((string) ($post['preco_kilo'] ?? '')),
            'link_compra' => trim((string) ($post['link_compra'] ?? '')),
            'capa' => isset($post['capa']) ? trim((string) $post['capa']) : null,
        ];
    }

    public function parseDadosAdicao(array $post, ?string $caminhoCapa = null): array
    {
        $precoKiloRaw = str_replace(',', '.', (string) ($post['preco_kilo'] ?? ''));
        $dados = [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'marca' => trim((string) ($post['marca'] ?? '')),
            'cor' => trim((string) ($post['cor'] ?? '')),
            'preco_kilo' => (float) $precoKiloRaw,
            'link_compra' => trim((string) ($post['link_compra'] ?? '')),
        ];
        if ($caminhoCapa) {
            $dados['capa'] = $caminhoCapa;
        }
        return $dados;
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $marca = trim((string) ($dados['marca'] ?? ''));
        $cor = trim((string) ($dados['cor'] ?? ''));
        $precoKilo = (float) ($dados['preco_kilo'] ?? 0);
        $linkCompra = trim((string) ($dados['link_compra'] ?? ''));

        if ($nome === '' || $marca === '' || $cor === '' || $precoKilo <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }
        if ($linkCompra !== '' && !filter_var($linkCompra, FILTER_VALIDATE_URL)) {
            return 'O link de compra informado não é um URL válido.';
        }
        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro da resina.'];
        }
        try {
            $this->repository->inserirResina([
                'usuario_id' => $usuarioId,
                'nome' => (string) ($dados['nome'] ?? ''),
                'marca' => (string) ($dados['marca'] ?? ''),
                'cor' => (string) ($dados['cor'] ?? ''),
                'preco_kilo' => (float) ($dados['preco_kilo'] ?? 0),
                'link_compra' => (string) ($dados['link_compra'] ?? ''),
                'capa' => isset($dados['capa']) ? $dados['capa'] : null,
            ]);
            return ['sucesso' => true, 'erro' => ''];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, array $post, ?string $caminhoCapa = null): array
    {
        $dados = $this->parseDadosAdicao($post, $caminhoCapa);
        $erro = $this->validarDadosAdicao($dados);
        if ($erro !== '') {
            return [
                'sucesso' => false,
                'erro' => $erro,
            ];
        }
        $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, $dados);
        if (!empty($resultadoCadastro['sucesso'])) {
            return [
                'sucesso' => true,
                'erro' => '',
            ];
        }
        return [
            'sucesso' => false,
            'erro' => trim((string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar.')),
        ];
    }
}
