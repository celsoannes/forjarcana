<?php
require_once __DIR__ . '/../../app/db.php';

$stmt = $pdo->query("SELECT * FROM miniaturas ORDER BY data_cadastro DESC, id DESC");
$miniaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Miniaturas</h3>
    <div class="card-tools">
      <a href="?pagina=miniaturas&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar miniatura
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($miniaturas): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>SKU</th>
            <th>Estúdio</th>
            <th>Temática</th>
            <th>Coleção</th>
            <th>Raça</th>
            <th>Classe</th>
            <th>Material</th>
            <th>Pintada</th>
            <th>Data Cadastro</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($miniaturas as $miniatura): ?>
            <tr>
              <td><?= htmlspecialchars($miniatura['nome'] ?? '-') ?></td>
              <td><?= htmlspecialchars($miniatura['sku']) ?></td>
              <td><?= htmlspecialchars($miniatura['estudio']) ?></td>
              <td><?= htmlspecialchars($miniatura['tematica'] ?: '-') ?></td>
              <td><?= htmlspecialchars($miniatura['colecao'] ?: '-') ?></td>
              <td><?= htmlspecialchars($miniatura['raca'] ?: '-') ?></td>
              <td><?= htmlspecialchars($miniatura['classe'] ?: '-') ?></td>
              <td><?= htmlspecialchars($miniatura['material'] ?: '-') ?></td>
              <td><?= !empty($miniatura['pintada']) ? 'Sim' : 'Não' ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($miniatura['data_cadastro']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=miniaturas&acao=editar&id=<?= $miniatura['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm" href="?pagina=miniaturas&acao=excluir&id=<?= $miniatura['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta miniatura?');">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhuma miniatura cadastrada.</div>
    <?php endif; ?>
  </div>
</div>
