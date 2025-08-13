<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca os dados de energia do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM energia WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$energia = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="card">
  <?php if ($energia): ?>
    <div class="card-body table-responsive p-0">
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Prestadora</th>
            <th>Valor Última Conta (R$)</th>
            <th>Energia Elétrica (kWh)</th>
            <th>Valor kWh (R$)</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($energia['prestadora']) ?></td>
            <td><?= number_format($energia['valor_ultima_conta'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($energia['energia_eletrica']) ?></td>
            <td><?= number_format($energia['valor_kwh'], 8, ',', '.') ?></td>
            <td><?= htmlspecialchars($energia['ultima_atualizacao']) ?></td>
            <td class="text-right">
              <a class="btn btn-info btn-sm" href="?pagina=energia&acao=editar&id=<?= $energia['id'] ?>">
                <i class="fas fa-pencil-alt"></i> Editar
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- /.card-body -->
  <?php else: ?>
    <div class="card-body text-center">
      Nenhum dado de energia cadastrado.
    </div>
  <?php endif; ?>
  <?php if (!$energia): ?>
    <div class="card-footer clearfix">
      <a href="?pagina=energia&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar energia
      </a>
    </div>
  <?php endif; ?>
  <!-- /.card-footer-->
</div>
<!-- /.card -->