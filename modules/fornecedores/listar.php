<?php
require_once __DIR__ . '/../../app/db.php';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE usuario_id = ? ORDER BY nome_fantasia ASC");
$stmt->execute([$usuario_id]);
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Fornecedores</h3>
    <div class="card-tools">
      <a href="?pagina=fornecedores&acao=adicionar" class="btn btn-primary float-right">
        <i class="fas fa-plus"></i> Adicionar fornecedor
      </a>
    </div>
  </div>
  <div class="card-body table-responsive p-0">
    <?php if ($fornecedores): ?>
      <table class="table table-hover text-nowrap">
        <thead>
          <tr>
            <th>Nome Fantasia</th>
            <th>Categoria/Ramo</th>
            <th>Vendedor</th>
            <th>WhatsApp</th>
            <th>E-mail de Pedidos</th>
            <th>Site</th>
            <th>Cidade</th>
            <th>UF</th>
            <th>Qualidade</th>
            <th>Última Atualização</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fornecedores as $fornecedor): ?>
            <tr>
              <td><?= htmlspecialchars($fornecedor['nome_fantasia']) ?></td>
              <td><?= htmlspecialchars($fornecedor['categoria_ramo'] ?: '-') ?></td>
              <td><?= htmlspecialchars($fornecedor['vendedor'] ?: '-') ?></td>
              <td>
                <?php
                  $whatsappNumeros = preg_replace('/\D/', '', (string) ($fornecedor['whatsapp'] ?? ''));
                  $whatsappUrl = $whatsappNumeros !== '' ? 'https://wa.me/55' . $whatsappNumeros : '';
                  $whatsappExibicao = $whatsappNumeros;
                  if (strlen($whatsappNumeros) === 11) {
                    $whatsappExibicao = '(' . substr($whatsappNumeros, 0, 2) . ') ' . substr($whatsappNumeros, 2, 5) . '-' . substr($whatsappNumeros, 7);
                  } elseif (strlen($whatsappNumeros) === 10) {
                    $whatsappExibicao = '(' . substr($whatsappNumeros, 0, 2) . ') ' . substr($whatsappNumeros, 2, 4) . '-' . substr($whatsappNumeros, 6);
                  }
                ?>
                <?php if ($whatsappUrl !== ''): ?>
                  <a href="<?= htmlspecialchars($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($whatsappExibicao) ?></a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($fornecedor['email_pedidos'])): ?>
                  <button type="button" class="btn btn-link p-0 align-baseline email-copy" data-email="<?= htmlspecialchars($fornecedor['email_pedidos']) ?>" title="Clique para copiar o e-mail">
                    <?= htmlspecialchars($fornecedor['email_pedidos']) ?>
                  </button>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($fornecedor['site'])): ?>
                  <a href="<?= htmlspecialchars($fornecedor['site']) ?>" target="_blank"><?= htmlspecialchars($fornecedor['site']) ?></a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($fornecedor['cidade'] ?: '-') ?></td>
              <td><?= htmlspecialchars($fornecedor['estado_uf'] ?: '-') ?></td>
              <td>
                <?php if (!empty($fornecedor['qualidade'])): ?>
                  <?php $qtdEstrelas = max(1, min(5, (int) $fornecedor['qualidade'])); ?>
                  <span class="text-warning"><?= str_repeat('★', $qtdEstrelas) ?></span><span class="text-muted"><?= str_repeat('☆', 5 - $qtdEstrelas) ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($fornecedor['ultima_atualizacao']))) ?></td>
              <td class="text-right">
                <a class="btn btn-info btn-sm" href="?pagina=fornecedores&acao=editar&id=<?= $fornecedor['id'] ?>">
                  <i class="fas fa-pencil-alt"></i> Editar
                </a>
                <a class="btn btn-danger btn-sm btn-excluir-fornecedor" href="?pagina=fornecedores&acao=excluir&id=<?= $fornecedor['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-center p-4">Nenhum fornecedor cadastrado.</div>
    <?php endif; ?>
  </div>
</div>

<div id="email-copy-toast-container" style="position: fixed; top: 70px; right: 20px; z-index: 1060; width: 360px; max-width: calc(100vw - 40px);"></div>

<div class="modal fade" id="modal-danger-excluir-fornecedor" tabindex="-1" role="dialog" aria-labelledby="modalDangerLabelFornecedor" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-danger">
      <div class="modal-header">
        <h4 class="modal-title" id="modalDangerLabelFornecedor">Excluir Fornecedor</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal-excluir-texto-fornecedor">Tem certeza que deseja excluir este fornecedor? Esta ação não pode ser desfeita.</p>
        <div id="modal-excluir-erro-fornecedor" class="alert alert-warning d-none"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-outline-light" id="btn-confirmar-excluir-fornecedor">Excluir</button>
      </div>
    </div>
  </div>
</div>

<script>
let fornecedorExcluirId = null;
document.querySelectorAll('.btn-excluir-fornecedor').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    fornecedorExcluirId = this.href.split('id=')[1];
    document.getElementById('modal-excluir-erro-fornecedor').classList.add('d-none');
    $('#modal-danger-excluir-fornecedor').modal('show');
  });
});

document.getElementById('btn-confirmar-excluir-fornecedor').addEventListener('click', function() {
  if (fornecedorExcluirId) {
    fetch('modules/fornecedores/excluir.php?id=' + encodeURIComponent(fornecedorExcluirId), {
      method: 'GET'
    })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === '' || result.includes('window.location.href')) {
        location.href = '?pagina=fornecedores';
      } else {
        document.getElementById('modal-excluir-erro-fornecedor').textContent = result;
        document.getElementById('modal-excluir-erro-fornecedor').classList.remove('d-none');
      }
    });
  }
});

document.querySelectorAll('.email-copy').forEach(function(link) {
  link.addEventListener('click', async function(e) {
    e.preventDefault();
    const email = this.getAttribute('data-email') || '';
    if (!email) return;

    const mostrarAlertaCopia = function(tipo, mensagem) {
      const container = document.getElementById('email-copy-toast-container');
      if (!container) return;

      container.innerHTML = '';

      const alerta = document.createElement('div');
      alerta.className = 'alert alert-' + tipo + ' alert-dismissible fade show shadow';
      alerta.setAttribute('role', 'alert');
      alerta.innerHTML =
        '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
        '<h5><i class="icon fas fa-check"></i> Copiado!</h5>' +
        mensagem;

      container.appendChild(alerta);

      setTimeout(function() {
        if (typeof window.jQuery !== 'undefined' && window.jQuery(alerta).alert) {
          window.jQuery(alerta).alert('close');
        } else if (alerta.parentNode) {
          alerta.parentNode.removeChild(alerta);
        }
      }, 2500);
    };

    try {
      await navigator.clipboard.writeText(email);
      mostrarAlertaCopia('success', 'O e-mail foi copiado para a área de transferência.');
    } catch (err) {
      const campoAuxiliar = document.createElement('input');
      campoAuxiliar.value = email;
      document.body.appendChild(campoAuxiliar);
      campoAuxiliar.select();
      const copiou = document.execCommand('copy');
      document.body.removeChild(campoAuxiliar);

      if (copiou) {
        mostrarAlertaCopia('success', 'O e-mail foi copiado para a área de transferência.');
      } else {
        mostrarAlertaCopia('danger', 'Não foi possível copiar automaticamente.');
      }
    }
  });
});
</script>
