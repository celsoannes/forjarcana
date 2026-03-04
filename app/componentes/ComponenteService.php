<?php

namespace App\Componentes;

use PDO;

class ComponenteService
{
    private ComponenteRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ComponenteRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'nome_material' => trim((string) ($post['nome_material'] ?? '')),
            'tipo_material' => trim((string) ($post['tipo_material'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'unidade_medida' => trim((string) ($post['unidade_medida'] ?? '')),
            'valor_unitario' => trim((string) ($post['valor_unitario'] ?? '')),
            'fornecedor' => trim((string) ($post['fornecedor'] ?? '')),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        $valorUnitarioRaw = str_replace(',', '.', (string) ($post['valor_unitario'] ?? ''));

        return [
            'nome_material' => trim((string) ($post['nome_material'] ?? '')),
            'tipo_material' => trim((string) ($post['tipo_material'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'unidade_medida' => trim((string) ($post['unidade_medida'] ?? '')),
            'valor_unitario' => (float) $valorUnitarioRaw,
            'fornecedor' => trim((string) ($post['fornecedor'] ?? '')),
            'observacoes' => trim((string) ($post['observacoes'] ?? '')),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $nomeMaterial = trim((string) ($dados['nome_material'] ?? ''));
        $tipoMaterial = trim((string) ($dados['tipo_material'] ?? ''));
        $unidadeMedida = trim((string) ($dados['unidade_medida'] ?? ''));
        $valorUnitario = (float) ($dados['valor_unitario'] ?? 0);

        if ($nomeMaterial === '' || $tipoMaterial === '' || $unidadeMedida === '' || $valorUnitario <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarUploadAdicao(string $usuarioUuid, array $files): array
    {
        $resultado = [
            'erro' => '',
            'imagem' => '',
        ];

        if (!isset($files['imagem']) || ($files['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $resultado;
        }

        if (($files['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $resultado['erro'] = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
            return $resultado;
        }

        $imagem = uploadImagem($files['imagem'], $usuarioUuid, 'usuarios', null, 'componente', false);
        if (!$imagem) {
            $resultado['erro'] = 'Formato de imagem não suportado. Use apenas PNG, JPG, WEBP ou GIF.';
            return $resultado;
        }

        $resultado['imagem'] = (string) $imagem;
        return $resultado;
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro do componente.'];
        }

        try {
            $this->repository->inserirComponente([
                'usuario_id' => $usuarioId,
                'nome_material' => (string) ($dados['nome_material'] ?? ''),
                'tipo_material' => (string) ($dados['tipo_material'] ?? ''),
                'descricao' => (string) ($dados['descricao'] ?? ''),
                'unidade_medida' => (string) ($dados['unidade_medida'] ?? ''),
                'valor_unitario' => (float) ($dados['valor_unitario'] ?? 0),
                'fornecedor' => (string) ($dados['fornecedor'] ?? ''),
                'observacoes' => (string) ($dados['observacoes'] ?? ''),
                'imagem' => (string) ($dados['imagem'] ?? ''),
            ]);

            return ['sucesso' => true, 'erro' => ''];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, string $usuarioUuid, array $post, array $files): array
    {
        $dados = $this->parseDadosAdicao($post);
        $erro = $this->validarDadosAdicao($dados);
        $imagem = '';

        if ($erro === '') {
            $resultadoUpload = $this->processarUploadAdicao($usuarioUuid, $files);
            $erroUpload = trim((string) ($resultadoUpload['erro'] ?? ''));
            if ($erroUpload !== '') {
                $erro = $erroUpload;
            }
            $imagem = (string) ($resultadoUpload['imagem'] ?? '');
        }

        if ($erro === '') {
            $resultadoCadastro = $this->processarCadastroAdicao($usuarioId, array_merge($dados, [
                'imagem' => $imagem,
            ]));

            if (!empty($resultadoCadastro['sucesso'])) {
                return [
                    'sucesso' => true,
                    'erro' => '',
                    'imagem' => $imagem,
                ];
            }

            $erro = trim((string) ($resultadoCadastro['erro'] ?? 'Erro ao cadastrar.'));
        }

        return [
            'sucesso' => false,
            'erro' => $erro,
            'imagem' => $imagem,
        ];
    }
}
