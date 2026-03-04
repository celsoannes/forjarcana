<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Torres\TorreController;

$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$torres = [];
$erro_consulta = null;

$torreController = new TorreController($pdo);

if ($usuario_id <= 0) {
    $erro_consulta = 'Não foi possível identificar o usuário logado para listar as torres.';
} else {
    try {
        $torres = $torreController->listarPorUsuario($usuario_id);
    } catch (Throwable $e) {
        $erro_consulta = 'Não foi possível carregar as torres no momento.';
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Torres de Dados</h3>
        <div class="card-tools">
            <a href="?pagina=torres&acao=adicionar" class="btn btn-primary float-right">
                <i class="fas fa-plus"></i> Adicionar torre
            </a>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <?php if ($erro_consulta): ?>
            <div class="alert alert-warning m-3 mb-0"><?= htmlspecialchars($erro_consulta) ?></div>
        <?php endif; ?>

        <?php if ($torres): ?>
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Capa</th>
                        <th>Nome</th>
                        <th>Nome Original</th>
                        <th>Preço Lojista</th>
                        <th>Preço Final</th>
                        <th>Cadastro</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($torres as $torre): ?>
                        <tr>
                            <td>
                                <?php if (!empty($torre['imagem_capa'])): ?>
                                    <img src="<?= htmlspecialchars((string) $torre['imagem_capa']) ?>" alt="Capa" style="width:48px; height:48px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;">
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($torre['nome'] ?? 'Torre sem nome')) ?></td>
                            <td><?= htmlspecialchars((string) ($torre['nome_original'] ?? '-')) ?></td>
                            <td>R$ <?= number_format((float) ($torre['preco_lojista'] ?? 0), 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float) ($torre['preco_consumidor_final'] ?? 0), 2, ',', '.') ?></td>
                            <td>
                                <?php if (!empty($torre['data_cadastro'])): ?>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $torre['data_cadastro']))) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a class="btn btn-info btn-sm" href="?pagina=torres&acao=editar&id=<?= (int) $torre['id'] ?>">
                                    <i class="fas fa-pencil-alt"></i> Editar
                                </a>
                                <a class="btn btn-danger btn-sm" href="?pagina=torres&acao=excluir&id=<?= (int) $torre['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta torre?');">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center p-4">Nenhuma torre cadastrada.</div>
        <?php endif; ?>
    </div>
</div>
