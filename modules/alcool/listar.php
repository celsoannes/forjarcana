<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Busca os dados de álcool do usuário autenticado
$stmt = $pdo->prepare("SELECT * FROM alcool WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$alcool = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="card">
  <?php if ($alcool): ?>
    <div class="card-body table-responsive p-0">
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Marca</th>
            <th>Preço por Litro (R$)</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($alcool['nome']) ?></td>
            <td><?= htmlspecialchars($alcool['marca']) ?></td>
            <td><?= number_format($alcool['preco_litro'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($alcool['ultima_atualizacao']) ?></td>
            <td class="text-right">
              <a class="btn btn-info btn-sm" href="?pagina=alcool&acao=editar&id=<?= $alcool['id'] ?>">
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
      Nenhum dado de álcool cadastrado.
    </div>
  <?php endif; ?>
  <?php if (!$alcool): ?>
    <div class="card-footer clearfix">
      <a href="?pagina=alcool&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar álcool
      </a>
    </div>
  <?php endif; ?>
  <!-- /.card-footer-->
</div>
<!