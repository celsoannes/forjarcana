<?php

namespace App\Usuarios;

use PDO;

class UsuarioController
{
    private UsuarioService $usuarioService;
    private UsuarioFotoService $usuarioFotoService;

    public function __construct(PDO $pdo)
    {
        $this->usuarioService = new UsuarioService($pdo);
        $this->usuarioFotoService = new UsuarioFotoService();
    }

    public function buscarPorId(int $usuarioId): ?array
    {
        return $this->usuarioService->buscarPorId($usuarioId);
    }

    public function processarCadastro(array $post, array $files): array
    {
        $erro = $this->usuarioService->validarCadastro($post);
        if ($erro !== null) {
            return ['sucesso' => false, 'erro' => $erro];
        }

        try {
            $usuarioId = $this->usuarioService->criar($post);
            $uuid = $this->usuarioService->buscarUuidPorId($usuarioId);
            if (!$uuid) {
                throw new \RuntimeException('Não foi possível identificar o usuário recém-criado.');
            }

            $fotoNome = '';
            $fotoUpload = $this->usuarioFotoService->processarUploadFoto($files['foto'] ?? [], (string) $uuid, false);
            if ($fotoUpload !== null) {
                $fotoNome = $fotoUpload;
                $this->usuarioService->atualizarFoto((int) $usuarioId, $fotoNome);
            }

            return [
                'sucesso' => true,
                'erro' => null,
                'usuario_id' => (int) $usuarioId,
                'foto_nome' => $fotoNome,
            ];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['sucesso' => false, 'erro' => 'Já existe um usuário com este e-mail ou CPF.'];
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    public function processarEdicao(int $usuarioId, array $post, array $files, string $fotoAtual): array
    {
        $erro = $this->usuarioService->validarEdicao($post);
        if ($erro !== null) {
            return ['sucesso' => false, 'erro' => $erro, 'foto_nome' => $fotoAtual];
        }

        try {
            $this->usuarioService->atualizar($usuarioId, $post);

            $uuid = $this->usuarioService->buscarUuidPorId($usuarioId);
            if (!$uuid) {
                throw new \RuntimeException('Não foi possível identificar o usuário para atualização da foto.');
            }

            $fotoNome = $fotoAtual;
            $fotoUpload = $this->usuarioFotoService->processarUploadFoto($files['foto'] ?? [], (string) $uuid, true);
            if ($fotoUpload !== null) {
                $fotoNome = $fotoUpload;
            }

            if ($fotoNome !== '') {
                $this->usuarioService->atualizarFoto($usuarioId, $fotoNome);
                $this->usuarioFotoService->definirFotoNaSessao($fotoNome);
            }

            return [
                'sucesso' => true,
                'erro' => null,
                'foto_nome' => $fotoNome,
            ];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['sucesso' => false, 'erro' => 'Já existe um usuário com este e-mail ou CPF.', 'foto_nome' => $fotoAtual];
            }

            return ['sucesso' => false, 'erro' => 'Erro ao editar: ' . $e->getMessage(), 'foto_nome' => $fotoAtual];
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage(), 'foto_nome' => $fotoAtual];
        }
    }

    public function excluir(int $usuarioId, string $uploadsBaseDir): bool
    {
        return $this->usuarioService->excluirUsuarioComArquivos($usuarioId, $uploadsBaseDir);
    }

    public function listarTodos(): array
    {
        return $this->usuarioService->listarTodos();
    }
}
