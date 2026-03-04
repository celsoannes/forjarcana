<?php

namespace App\Energia;

use PDO;

class EnergiaService
{
    private EnergiaRepository $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new EnergiaRepository($pdo);
    }

    public function montarEstadoFormularioAdicao(array $post): array
    {
        return [
            'prestadora' => trim((string) ($post['prestadora'] ?? '')),
            'valor_ultima_conta' => trim((string) ($post['valor_ultima_conta'] ?? '')),
            'energia_eletrica' => trim((string) ($post['energia_eletrica'] ?? '')),
        ];
    }

    public function parseDadosAdicao(array $post): array
    {
        return [
            'prestadora' => trim((string) ($post['prestadora'] ?? '')),
            'valor_ultima_conta' => (float) str_replace(',', '.', (string) ($post['valor_ultima_conta'] ?? '0')),
            'energia_eletrica' => (float) str_replace(',', '.', (string) ($post['energia_eletrica'] ?? '0')),
        ];
    }

    public function validarDadosAdicao(array $dados): string
    {
        $prestadora = trim((string) ($dados['prestadora'] ?? ''));
        $valorUltimaConta = (float) ($dados['valor_ultima_conta'] ?? 0);
        $energiaEletrica = (float) ($dados['energia_eletrica'] ?? 0);

        if ($prestadora === '' || $valorUltimaConta <= 0 || $energiaEletrica <= 0) {
            return 'Preencha todos os campos obrigatórios.';
        }

        return '';
    }

    public function processarCadastroAdicao(int $usuarioId, array $dados): array
    {
        if ($usuarioId <= 0) {
            return ['sucesso' => false, 'erro' => 'Usuário inválido para cadastro de energia.'];
        }

        try {
            $this->repository->inserirEnergia([
                'usuario_id' => $usuarioId,
                'prestadora' => (string) ($dados['prestadora'] ?? ''),
                'valor_ultima_conta' => (float) ($dados['valor_ultima_conta'] ?? 0),
                'energia_eletrica' => (float) ($dados['energia_eletrica'] ?? 0),
            ]);

            return ['sucesso' => true, 'erro' => ''];
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return ['sucesso' => false, 'erro' => 'Já existe um registro de energia para este usuário.'];
            }

            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    public function processarFluxoAdicao(int $usuarioId, array $post): array
    {
        $dados = $this->parseDadosAdicao($post);
        $erro = $this->validarDadosAdicao($dados);

        if ($erro !== '') {
            return [
                'sucesso' => false,
                'erro' => $erro,
            ];
        }

        return $this->processarCadastroAdicao($usuarioId, $dados);
    }
}
