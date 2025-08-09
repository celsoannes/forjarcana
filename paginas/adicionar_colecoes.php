<?php
require __DIR__ . '/../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$mensagem = '';

// Busca estudios do usuário para o select/autocomplete
$stmt = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Array de nomes para validação
$nomes_estudios = array_map('mb_strtolower', array_column($estudios, 'nome'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estudio_id = $_POST['estudio_id'] ?? '';
    $nome_estudio = trim($_POST['inputEstudio'] ?? '');
    $nome = trim($_POST['nome'] ?? '');

    if (!$nome_estudio || !$nome) {
        $mensagem = "Preencha todos os campos!";
    } else {
        // Se não foi selecionado um estudio existente, cria um novo
        if (!$estudio_id) {
            // Verifica se já existe um estudio com esse nome (case-insensitive)
            $nome_estudio_lower = mb_strtolower($nome_estudio);
            $id_existente = null;
            foreach ($estudios as $e) {
                if (mb_strtolower($e['nome']) === $nome_estudio_lower) {
                    $id_existente = $e['id'];
                    break;
                }
            }
            if ($id_existente) {
                $estudio_id = $id_existente;
            } else {
                // Adiciona novo estudio
                $stmt = $pdo->prepare("INSERT INTO estudios (nome, site, usuario_id) VALUES (?, '', ?)");
                $stmt->execute([$nome_estudio, $usuario_id]);
                $estudio_id = $pdo->lastInsertId();
            }
        }
        // Insere coleção vinculada ao estudio
        $stmt = $pdo->prepare("INSERT INTO colecoes (estudio_id, nome, usuario_id) VALUES (?, ?, ?)");
        $stmt->execute([$estudio_id, $nome, $usuario_id]);
        echo '<script>window.location.href="?pagina=colecoes";</script>';
        exit;
    }
}
?>

<h2 class="mb-4">Adicionar Coleção</h2>
<?php if ($mensagem): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>
<form method="POST">
    <div class="mb-3" style="position:relative;">
        <label class="form-label">Estudio *</label>
        <input type="text" id="inputEstudio" name="inputEstudio" class="form-control mb-2" placeholder="Digite para buscar..." autocomplete="off" required>
        <input type="hidden" name="estudio_id" id="estudioIdHidden">
        <div id="sugestoesEstudio" class="list-group" style="position:absolute;z-index:10;max-height:180px;overflow-y:auto;min-width:100%;width:100%;"></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Nome da Coleção *</label>
        <input type="text" name="nome" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-adicionar">Adicionar</button>
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
    if (filtrados.length === 0) {
        const item = document.createElement('div');
        item.className = 'list-group-item text-danger';
        item.textContent = 'Nenhuma opção encontrada ou ainda não cadastrada';
        sugestoes.appendChild(item);
    } else {
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
    }
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
</script>