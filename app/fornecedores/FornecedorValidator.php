<?php

namespace App\Fornecedores;

require_once __DIR__ . '/../validacoes_documentos.php';

class FornecedorValidator
{
    public function validar(array $dados): ?string
    {
        $nomeFantasia = trim((string) ($dados['nome_fantasia'] ?? ''));
        $cnpjCpf = trim((string) ($dados['cnpj_cpf'] ?? ''));
        $emailPedidos = trim((string) ($dados['email_pedidos'] ?? ''));
        $qualidade = (int) ($dados['qualidade'] ?? 0);

        if ($nomeFantasia === '') {
            return 'Preencha o nome fantasia do fornecedor.';
        }

        if ($cnpjCpf !== '' && !\validarCpfCnpj($cnpjCpf)) {
            return 'Informe um CPF ou CNPJ válido.';
        }

        if (!\validarEmail($emailPedidos, true, 150)) {
            return 'Informe um e-mail de pedidos válido.';
        }

        if ($qualidade < 0 || $qualidade > 5) {
            return 'A qualidade deve estar entre 0 e 5.';
        }

        return null;
    }
}
