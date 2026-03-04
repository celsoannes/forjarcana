<?php

namespace App\Usuarios;

use PDO;

class UsuarioService
{
    private UsuarioRepository $repository;
    private UsuarioValidator $validator;

    public function __construct(PDO $pdo, ?UsuarioValidator $validator = null)
    {
        $this->repository = new UsuarioRepository($pdo);
        $this->validator = $validator ?? new UsuarioValidator();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->repository->buscarPorId($id);
    }

    public function validarCadastro(array $dados): ?string
    {
        return $this->validator->validarCadastro($dados);
    }

    public function validarEdicao(array $dados): ?string
    {
        return $this->validator->validarEdicao($dados);
    }

    public function criar(array $dados): int
    {
        $dataExpiracao = $this->validator->converterDataExpiracao((string) ($dados['data_expiracao'] ?? ''));
        if ($dataExpiracao === null) {
            throw new \InvalidArgumentException('Data de expiração inválida.');
        }

        $payload = [
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'sobrenome' => trim((string) ($dados['sobrenome'] ?? '')),
            'email' => trim((string) ($dados['email'] ?? '')),
            'senha_hash' => password_hash((string) ($dados['senha'] ?? ''), PASSWORD_DEFAULT),
            'cargo' => (string) ($dados['cargo'] ?? ''),
            'celular' => trim((string) ($dados['celular'] ?? '')),
            'cpf' => trim((string) ($dados['cpf'] ?? '')),
            'data_expiracao' => $dataExpiracao,
        ];

        return $this->repository->inserir($payload);
    }

    public function atualizar(int $id, array $dados): void
    {
        $dataExpiracao = $this->validator->converterDataExpiracao((string) ($dados['data_expiracao'] ?? ''));
        if ($dataExpiracao === null) {
            throw new \InvalidArgumentException('Data de expiração inválida.');
        }

        $payload = [
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'sobrenome' => trim((string) ($dados['sobrenome'] ?? '')),
            'email' => trim((string) ($dados['email'] ?? '')),
            'cargo' => (string) ($dados['cargo'] ?? ''),
            'celular' => trim((string) ($dados['celular'] ?? '')),
            'cpf' => trim((string) ($dados['cpf'] ?? '')),
            'data_expiracao' => $dataExpiracao,
        ];

        $senha = (string) ($dados['senha'] ?? '');
        $senhaHash = $senha !== '' ? password_hash($senha, PASSWORD_DEFAULT) : null;

        $this->repository->atualizar($id, $payload, $senhaHash);
    }

    public function buscarUuidPorId(int $id): ?string
    {
        return $this->repository->buscarUuidPorId($id);
    }

    public function atualizarFoto(int $id, string $fotoNome): void
    {
        $this->repository->atualizarFoto($id, $fotoNome);
    }

    public function listarTodos(): array
    {
        return $this->repository->listarTodosOrdenadosPorNome();
    }

    public function excluirUsuarioComArquivos(int $id, string $uploadsBaseDir): bool
    {
        $uuid = $this->repository->buscarUuidPorId($id);
        if (!$uuid) {
            return false;
        }

        $this->repository->excluirPorId($id);

        $dir = rtrim($uploadsBaseDir, '/\\') . '/usuarios/' . $uuid;
        if (is_dir($dir)) {
            $this->excluirPastaRecursiva($dir);
        }

        return true;
    }

    private function excluirPastaRecursiva(string $pasta): void
    {
        $arquivos = array_diff(scandir($pasta) ?: [], ['.', '..']);
        foreach ($arquivos as $arquivo) {
            $caminho = $pasta . '/' . $arquivo;
            if (is_dir($caminho)) {
                $this->excluirPastaRecursiva($caminho);
            } else {
                @unlink($caminho);
            }
        }

        @rmdir($pasta);
    }
}
