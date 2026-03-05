<?php

namespace App\Impressoras3d;

use PDO;

class Impressora3dService
{
    private Impressora3dRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new Impressora3dRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'marca' => trim((string) ($post['marca'] ?? '')),
            'modelo' => trim((string) ($post['modelo'] ?? '')),
            'tipo' => trim((string) ($post['tipo'] ?? '')),
            'preco_aquisicao' => trim((string) ($post['preco_aquisicao'] ?? '')),
            'potencia' => trim((string) ($post['potencia'] ?? '')),
            'depreciacao' => trim((string) ($post['depreciacao'] ?? '')),
            'tempo_vida_util' => trim((string) ($post['tempo_vida_util'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        return [
            'marca' => trim((string) ($post['marca'] ?? '')),
            'modelo' => trim((string) ($post['modelo'] ?? '')),
            'tipo' => trim((string) ($post['tipo'] ?? '')),
            'preco_aquisicao' => (float) ($post['preco_aquisicao'] ?? 0),
            'potencia' => (int) ($post['potencia'] ?? 0),
            'depreciacao' => (int) ($post['depreciacao'] ?? 0),
            'tempo_vida_util' => (int) ($post['tempo_vida_util'] ?? 0),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $marca = trim((string) ($dados['marca'] ?? ''));
        $modelo = trim((string) ($dados['modelo'] ?? ''));
        $tipo = trim((string) ($dados['tipo'] ?? ''));
        $precoAquisicao = (float) ($dados['preco_aquisicao'] ?? 0);
        $potencia = (int) ($dados['potencia'] ?? 0);
        $depreciacao = (int) ($dados['depreciacao'] ?? 0);
        $tempoVidaUtil = (int) ($dados['tempo_vida_util'] ?? 0);

        if ($marca === '' || $modelo === '' || $tipo === '' || $precoAquisicao <= 0 || $potencia <= 0 || $depreciacao <= 0 || $tempoVidaUtil <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados, ?string $capa = null): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro da impressora.'];
        }

        try {
            $this->repository->inserirImpressora([
                'usuario_id' => $usuarioId,
                'marca' => (string) ($dados['marca'] ?? ''),
                'modelo' => (string) ($dados['modelo'] ?? ''),
                'tipo' => (string) ($dados['tipo'] ?? ''),
                'preco_aquisicao' => (float) ($dados['preco_aquisicao'] ?? 0),
                'potencia' => (int) ($dados['potencia'] ?? 0),
                'depreciacao' => (int) ($dados['depreciacao'] ?? 0),
                'tempo_vida_util' => (int) ($dados['tempo_vida_util'] ?? 0),
                'capa' => $capa,
            ]);

            return ['sucesso' => true, 'erro' => ''];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, array $post, array $files = []): array
    {
        $dados = $this->parseDadosAdicao($post);
        $erro = $this->validarDadosAdicao($dados);

        if ($erro !== '') {
            return [
                'sucesso' => false,
                'erro' => $erro,
            ];
        }

        // Buscar o uuid do usuário para usar na pasta

        $usuarioUuid = null;
        try {
            require_once __DIR__ . '/../usuarios/UsuarioRepository.php';
            $pdo = $this->repository->getPdo();
            $usuarioRepo = new \App\Usuarios\UsuarioRepository($pdo);
            $usuarioUuid = $usuarioRepo->buscarUuidPorId($usuarioId);
            if (!$usuarioUuid) {
                error_log('[IMPRESSORA3D] UUID do usuário não encontrado para ID: ' . $usuarioId);
            }
        } catch (\Throwable $e) {
            error_log('[IMPRESSORA3D] Erro ao buscar UUID do usuário: ' . $e->getMessage());
            $usuarioUuid = null;
        }

        $caminhoCapa = null;
        if ($usuarioUuid && isset($files['foto']) && $files['foto']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../upload_imagem.php';
            $tamanhosUpload = [
                'thumbnail' => [150, 150, 'crop'],
                'pequena' => [300, 300, 'proporcional'],
                'media' => [300, 300, 'proporcional'],
                'grande' => [1024, 1024, 'proporcional'],
            ];
            // Corrigir pasta base para 'usuarios' (igual torres)
            $caminho = uploadImagem($files['foto'], $usuarioUuid, 'usuarios', $tamanhosUpload, 'impressora', false);
            if ($caminho) {
                $caminhoCapa = $caminho;
                error_log('[IMPRESSORA3D] Upload de capa realizado: ' . $caminhoCapa);
            } else {
                error_log('[IMPRESSORA3D] Falha no uploadImagem para impressora, uuid: ' . $usuarioUuid);
            }
        } else {
            if (!$usuarioUuid) error_log('[IMPRESSORA3D] Não fez upload: uuid do usuário vazio');
            if (!isset($files['foto'])) error_log('[IMPRESSORA3D] Não fez upload: campo foto não enviado');
            if (isset($files['foto']) && $files['foto']['error'] !== UPLOAD_ERR_OK) error_log('[IMPRESSORA3D] Não fez upload: erro no arquivo foto: ' . $files['foto']['error']);
        }

        $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, $dados, $caminhoCapa);
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
