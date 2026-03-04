<?php

namespace App\Usuarios;

require_once __DIR__ . '/../validacoes_documentos.php';

class UsuarioValidator
{
    public function validarCadastro(array $dados): ?string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $sobrenome = trim((string) ($dados['sobrenome'] ?? ''));
        $email = trim((string) ($dados['email'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');
        $confirmarSenha = (string) ($dados['confirmar_senha'] ?? '');
        $cargo = trim((string) ($dados['cargo'] ?? ''));
        $celular = trim((string) ($dados['celular'] ?? ''));
        $cpf = trim((string) ($dados['cpf'] ?? ''));
        $dataExpiracao = trim((string) ($dados['data_expiracao'] ?? ''));

        if ($nome === '' || $sobrenome === '' || $email === '' || $senha === '' || $confirmarSenha === '' || $cargo === '' || $celular === '' || $cpf === '' || $dataExpiracao === '') {
            return 'Preencha todos os campos obrigatórios.';
        }

        if ($senha !== $confirmarSenha) {
            return 'Senha e confirmação de senha não conferem.';
        }

        if (!\validarEmail($email, false, 150)) {
            return 'Informe um e-mail válido.';
        }

        if (!\validarCpf($cpf)) {
            return 'Informe um CPF válido.';
        }

        if ($this->converterDataExpiracao($dataExpiracao) === null) {
            return 'Data de expiração inválida.';
        }

        return null;
    }

    public function validarEdicao(array $dados): ?string
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $sobrenome = trim((string) ($dados['sobrenome'] ?? ''));
        $email = trim((string) ($dados['email'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');
        $confirmarSenha = (string) ($dados['confirmar_senha'] ?? '');
        $cargo = trim((string) ($dados['cargo'] ?? ''));
        $celular = trim((string) ($dados['celular'] ?? ''));
        $cpf = trim((string) ($dados['cpf'] ?? ''));
        $dataExpiracao = trim((string) ($dados['data_expiracao'] ?? ''));

        if ($nome === '' || $sobrenome === '' || $email === '' || $cargo === '' || $celular === '' || $cpf === '' || $dataExpiracao === '') {
            return 'Preencha todos os campos obrigatórios.';
        }

        if (($senha !== '' || $confirmarSenha !== '') && $senha !== $confirmarSenha) {
            return 'Senha e confirmação de senha não conferem.';
        }

        if (!\validarEmail($email, false, 150)) {
            return 'Informe um e-mail válido.';
        }

        if (!\validarCpf($cpf)) {
            return 'Informe um CPF válido.';
        }

        if ($this->converterDataExpiracao($dataExpiracao) === null) {
            return 'Data de expiração inválida.';
        }

        return null;
    }

    public function converterDataExpiracao(string $data): ?string
    {
        $dataFormatada = \DateTime::createFromFormat('d/m/Y', trim($data));
        if (!$dataFormatada) {
            return null;
        }

        return $dataFormatada->format('Y-m-d');
    }
}
