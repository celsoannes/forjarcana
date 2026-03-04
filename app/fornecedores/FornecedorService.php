<?php

namespace App\Fornecedores;

use PDO;

class FornecedorService
{
    private FornecedorRepository $repository;
    private FornecedorValidator $validator;

    public function __construct(PDO $pdo)
    {
        $this->repository = new FornecedorRepository($pdo);
        $this->validator = new FornecedorValidator();
    }

    public function buscarPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        return $this->repository->buscarPorIdEUsuario($id, $usuarioId);
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->repository->listarPorUsuario($usuarioId);
    }

    public function validar(array $dados): ?string
    {
        return $this->validator->validar($dados);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome_fantasia' => trim((string) ($post['nome_fantasia'] ?? '')),
            'razao_social' => trim((string) ($post['razao_social'] ?? '')),
            'cnpj_cpf' => trim((string) ($post['cnpj_cpf'] ?? '')),
            'categoria_ramo' => trim((string) ($post['categoria_ramo'] ?? '')),
            'vendedor' => trim((string) ($post['vendedor'] ?? '')),
            'whatsapp' => trim((string) ($post['whatsapp'] ?? '')),
            'telefone_fixo' => trim((string) ($post['telefone_fixo'] ?? '')),
            'email_pedidos' => trim((string) ($post['email_pedidos'] ?? '')),
            'site' => trim((string) ($post['site'] ?? '')),
            'cep' => trim((string) ($post['cep'] ?? '')),
            'logradouro' => trim((string) ($post['logradouro'] ?? '')),
            'numero' => trim((string) ($post['numero'] ?? '')),
            'complemento' => trim((string) ($post['complemento'] ?? '')),
            'bairro' => trim((string) ($post['bairro'] ?? '')),
            'cidade' => trim((string) ($post['cidade'] ?? '')),
            'estado_uf' => trim((string) ($post['estado_uf'] ?? '')),
            'prazo_entrega_medio' => trim((string) ($post['prazo_entrega_medio'] ?? '')),
            'pedido_minimo' => trim((string) ($post['pedido_minimo'] ?? '')),
            'condicoes_pagamento' => trim((string) ($post['condicoes_pagamento'] ?? '')),
            'dados_bancarios' => trim((string) ($post['dados_bancarios'] ?? '')),
            'chave_pix' => trim((string) ($post['chave_pix'] ?? '')),
            'qualidade' => trim((string) ($post['qualidade'] ?? '0')),
            'observacoes_gerais' => trim((string) ($post['observacoes_gerais'] ?? '')),
        ];
    }

    public function criar(int $usuarioId, array $dados): void
    {
        $payload = $this->normalizarDados($dados);
        $payload['usuario_id'] = $usuarioId;
        $this->repository->inserir($payload);
    }

    public function editar(int $id, int $usuarioId, array $dados): void
    {
        $payload = $this->normalizarDados($dados);
        $this->repository->atualizar($id, $usuarioId, $payload);
    }

    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->repository->excluirPorIdEUsuario($id, $usuarioId);
    }

    private function normalizarDados(array $dados): array
    {
        $logradouro = trim((string) ($dados['logradouro'] ?? ''));
        $numero = trim((string) ($dados['numero'] ?? ''));
        $complemento = trim((string) ($dados['complemento'] ?? ''));
        $bairro = trim((string) ($dados['bairro'] ?? ''));
        $cidade = trim((string) ($dados['cidade'] ?? ''));
        $estadoUf = trim((string) ($dados['estado_uf'] ?? ''));
        $cep = trim((string) ($dados['cep'] ?? ''));

        $enderecoPartes = [];
        if ($logradouro !== '') {
            $enderecoPartes[] = $logradouro . ($numero !== '' ? ', ' . $numero : '');
        }
        if ($complemento !== '') {
            $enderecoPartes[] = 'Compl.: ' . $complemento;
        }
        if ($bairro !== '') {
            $enderecoPartes[] = 'Bairro: ' . $bairro;
        }
        if ($cidade !== '' || $estadoUf !== '') {
            $cidadeUf = trim($cidade . ($estadoUf !== '' ? ' - ' . $estadoUf : ''));
            if ($cidadeUf !== '') {
                $enderecoPartes[] = $cidadeUf;
            }
        }
        if ($cep !== '') {
            $enderecoPartes[] = 'CEP: ' . $cep;
        }

        $qualidade = (int) ($dados['qualidade'] ?? 0);

        return [
            'nome_fantasia' => trim((string) ($dados['nome_fantasia'] ?? '')),
            'razao_social' => trim((string) ($dados['razao_social'] ?? '')),
            'cnpj_cpf' => trim((string) ($dados['cnpj_cpf'] ?? '')),
            'categoria_ramo' => trim((string) ($dados['categoria_ramo'] ?? '')),
            'vendedor' => trim((string) ($dados['vendedor'] ?? '')),
            'whatsapp' => trim((string) ($dados['whatsapp'] ?? '')),
            'telefone_fixo' => trim((string) ($dados['telefone_fixo'] ?? '')),
            'email_pedidos' => trim((string) ($dados['email_pedidos'] ?? '')),
            'site' => trim((string) ($dados['site'] ?? '')),
            'cep' => $cep,
            'logradouro' => $logradouro,
            'numero' => $numero,
            'complemento' => $complemento,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado_uf' => $estadoUf,
            'endereco' => implode(' | ', $enderecoPartes),
            'prazo_entrega_medio' => trim((string) ($dados['prazo_entrega_medio'] ?? '')),
            'pedido_minimo' => trim((string) ($dados['pedido_minimo'] ?? '')),
            'condicoes_pagamento' => trim((string) ($dados['condicoes_pagamento'] ?? '')),
            'dados_bancarios' => trim((string) ($dados['dados_bancarios'] ?? '')),
            'chave_pix' => trim((string) ($dados['chave_pix'] ?? '')),
            'qualidade' => $qualidade > 0 ? $qualidade : null,
            'observacoes_gerais' => trim((string) ($dados['observacoes_gerais'] ?? '')),
        ];
    }
}
