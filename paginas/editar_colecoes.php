<?php
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['usuario_id'])) {
    echo '<div class="alert alert-danger">Você precisa estar logado para editar coleções.</div>';
    return;
}

$id = $_GET['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Busca estudios do usuário para o autocomplete
$stmt = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca a coleção do usuário
$stmt = $pdo->prepare("SELECT * FROM colecoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$colecao = $stmt->fetch();

if (!$colecao) {
    echo '<div class="alert alert-danger">Registro não encontrado ou você não tem permissão para editar.</div>';
    return;
}

// Array de IDs válidos para validação
$ids_validos = array_column($estudios, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estudio_id = $_POST['estudio_id'] ?? '';
    $nome = trim($_POST['nome'] ?? '');

    // Valida se o estudio_id está na lista de estudios do usuário
    if (!$estudio_id || !$nome) {
        $mensagem = "Preencha todos os campos!";
    } elseif (!in_array($estudio_id, $ids_validos)) {
        $mensagem = "Selecione um estudio válido da lista!";
    } else {
        $stmt = $pdo->prepare("UPDATE colecoes SET estudio_id = ?, nome = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$estudio_id, $nome, $id, $usuario_id]);
        echo '<script>window.location.href="?pagina=colecoes";</script>';
        exit;
    }
}

// Preenche o nome do estudio atual
$estudio_nome_atual = '';
foreach ($estudios as $e) {
    if ($e['id'] == $colecao['estudio_id']) {
        $estudio_nome_atual = $e['nome'];
        break;
    }
}
?>

<h2 class="mb-4">Editar Coleção</h2>
<?php if ($mensagem): ?>
    <!-- Modal de alerta de validação -->
    <div class="modal fade show" id="modalAlertaValidacao" tabindex="-1" aria-labelledby="modalAlertaValidacaoLabel" style="display:block;" aria-modal="true" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAlertaValidacaoLabel">Atenção</h5>
            <button type="button" class="btn-close" onclick="document.getElementById('modalAlertaValidacao').style.display='none';" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($mensagem) ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-voltar" onclick="document.getElementById('modalAlertaValidacao').style.display='none';">Fechar</button>
          </div>
        </div>
      </div>
    </div>
    <script>
      // Fecha o modal ao clicar fora dele
      document.addEventListener('click', function(e) {
        var modal = document.getElementById('modalAlertaValidacao');
        if (modal && modal.style.display === 'block' && !modal.contains(e.target)) {
          modal.style.display = 'none';
        }
      });
    </script>
<?php endif; ?>
<form method="POST">
    <div class="mb-3">
        <label class="form-label">Estudio *</label>
        <input type="text" id="inputEstudio" class="form-control mb-2" placeholder="Digite para buscar..." autocomplete="off" required value="<?= htmlspecialchars($estudio_nome_atual) ?>">
        <input type="hidden" name="estudio_id" id="estudioIdHidden" value="<?= htmlspecialchars($colecao['estudio_id']) ?>">
        <div id="sugestoesEstudio" class="list-group" style="position:absolute;z-index:10;max-height:180px;overflow-y:auto;width:100%;"></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Nome da Coleção *</label>
        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? $colecao['nome']) ?>">
    </div>
    <button type="submit" class="btn btn-adicionar">Salvar</button>
    <a href="?pagina=colecoes" class="btn btn-voltar">Cancelar</a>
</form>
<script>
const estudios = <?= json_encode($estudios) ?>;
const input = document.getElementById('inputEstudio');
const sugestoes = document.getElementById('sugestoesEstudio');
const estudioIdHidden = document.getElementById('estudioIdHidden');

input.addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    sugestoes.innerHTML = '';
    estudioIdHidden.value = '';
    if (termo.length === 0) return;
    const filtrados = estudios.filter(e => e.nome.toLowerCase().includes(termo));
    filtrados.forEach(e => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action';
        item.textContent = e.nome;
        item.onclick = function() {
            input.value = e.nome;
            estudioIdHidden.value = e.id;
            sugestoes.innerHTML = '';
        };
        sugestoes.appendChild(item);
    });
});

input.addEventListener('blur', function() {
    setTimeout(() => {
        sugestoes.innerHTML = '';
        // Se o texto não corresponde a nenhum estudio, limpa o id oculto
        const encontrado = estudios.find(e => e.nome === input.value);
        if (!encontrado) {
            estudioIdHidden.value = '';
        } else {
            estudioIdHidden.value = encontrado.id;
        }
    }, 200);
});

// Validação no submit do formulário (front-end) com modal
document.querySelector('form').addEventListener('submit', function(e) {
    if (!estudioIdHidden.value) {
        e.preventDefault();
        // Cria e mostra o modal se não existir
        if (!document.getElementById('modalAlertaValidacao')) {
            var modalHtml = `
            <div class="modal fade show" id="modalAlertaValidacao" tabindex="-1" aria-labelledby="modalAlertaValidacaoLabel" style="display:block;" aria-modal="true" role="dialog">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalAlertaValidacaoLabel">Atenção</h5>
                    <button type="button" class="btn-close" onclick="document.getElementById('modalAlertaValidacao').style.display='none';" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                    Selecione um estudio válido da lista!
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-voltar" onclick="document.getElementById('modalAlertaValidacao').style.display='none';">Fechar</button>
                  </div>
                </div>
              </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        } else {
            document.getElementById('modalAlertaValidacao').style.display = 'block';
        }
        input.focus();
    }
});
</script>