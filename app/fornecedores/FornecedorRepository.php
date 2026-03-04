<?php

namespace App\Fornecedores;

use PDO;

class FornecedorRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorIdEUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fornecedores WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$id, $usuarioId]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fornecedor ?: null;
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fornecedores WHERE usuario_id = ? ORDER BY nome_fantasia ASC');
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function inserir(array $dados): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO fornecedores (
            usuario_id, nome_fantasia, razao_social, cnpj_cpf, categoria_ramo, vendedor,
            whatsapp, telefone_fixo, email_pedidos, site,
            cep, logradouro, numero, complemento, bairro, cidade, estado_uf,
            endereco, prazo_entrega_medio, pedido_minimo,
            condicoes_pagamento, dados_bancarios, chave_pix, qualidade, observacoes_gerais,
            ultima_atualizacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $dados['usuario_id'],
            $dados['nome_fantasia'],
            $dados['razao_social'],
            $dados['cnpj_cpf'],
            $dados['categoria_ramo'],
            $dados['vendedor'],
            $dados['whatsapp'],
            $dados['telefone_fixo'],
            $dados['email_pedidos'],
            $dados['site'],
            $dados['cep'],
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado_uf'],
            $dados['endereco'],
            $dados['prazo_entrega_medio'],
            $dados['pedido_minimo'],
            $dados['condicoes_pagamento'],
            $dados['dados_bancarios'],
            $dados['chave_pix'],
            $dados['qualidade'],
            $dados['observacoes_gerais'],
        ]);
    }

    public function atualizar(int $id, int $usuarioId, array $dados): void
    {
        $stmt = $this->pdo->prepare("UPDATE fornecedores SET
            nome_fantasia = ?,
            razao_social = ?,
            cnpj_cpf = ?,
            categoria_ramo = ?,
            vendedor = ?,
            whatsapp = ?,
            telefone_fixo = ?,
            email_pedidos = ?,
            site = ?,
            cep = ?,
            logradouro = ?,
            numero = ?,
            complemento = ?,
            bairro = ?,
            cidade = ?,
            estado_uf = ?,
            endereco = ?,
            prazo_entrega_medio = ?,
            pedido_minimo = ?,
            condicoes_pagamento = ?,
            dados_bancarios = ?,
            chave_pix = ?,
            qualidade = ?,
            observacoes_gerais = ?,
            ultima_atualizacao = NOW()
        WHERE id = ? AND usuario_id = ?");

        $stmt->execute([
            $dados['nome_fantasia'],
            $dados['razao_social'],
            $dados['cnpj_cpf'],
            $dados['categoria_ramo'],
            $dados['vendedor'],
            $dados['whatsapp'],
            $dados['telefone_fixo'],
            $dados['email_pedidos'],
            $dados['site'],
            $dados['cep'],
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado_uf'],
            $dados['endereco'],
            $dados['prazo_entrega_medio'],
            $dados['pedido_minimo'],
            $dados['condicoes_pagamento'],
            $dados['dados_bancarios'],
            $dados['chave_pix'],
            $dados['qualidade'],
            $dados['observacoes_gerais'],
            $id,
            $usuarioId,
        ]);
    }

    public function excluirPorIdEUsuario(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $usuarioId]);

        return $stmt->rowCount() > 0;
    }
}
