<?php
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
require_once __DIR__ . '/../../app/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$id = $_GET['id'] ?? '';
$erro = '';

// Busca resina
$stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$resina = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resina) {
    header('Location: /404.php');
    exit;
}

$coresDisponiveis = [];
try {
  $stmtCores = $pdo->prepare("SELECT DISTINCT cor FROM resinas WHERE usuario_id = ? AND cor IS NOT NULL AND cor <> '' ORDER BY cor ASC");
  $stmtCores->execute([(int) $usuario_id]);
  $coresDisponiveis = $stmtCores->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
  $coresDisponiveis = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $cor = trim($_POST['cor'] ?? '');
    $preco_litro = str_replace(',', '.', $_POST['preco_litro'] ?? '');

    if (!$nome || !$marca || !$cor || !$preco_litro) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE resinas SET nome = ?, marca = ?, cor = ?, preco_litro = ?, ultima_atualizacao = NOW() WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $marca, $cor, $preco_litro, $id, $usuario_id]);
            echo '<script>window.location.href="?pagina=resinas";</script>';
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    }
}
?>
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Editar Resina</h3>
  </div>
  <form method="POST">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($resina['nome']) ?>">
      </div>
      <div class="form-group">
        <label for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" required value="<?= htmlspecialchars($resina['marca']) ?>">
      </div>
      <div class="form-group position-relative">
        <label for="cor">Cor</label>
        <input type="text" class="form-control" id="cor" name="cor" required autocomplete="off" value="<?= htmlspecialchars((string) ($resina['cor'] ?? '')) ?>">
        <ul id="cor-sugestoes" class="autocomplete-sugestoes list-group position-absolute w-100 d-none" style="top:100%; left:0; z-index:1060; max-height:220px; overflow-y:auto;"></ul>
      </div>
      <div class="form-group">
        <label for="preco_litro">Preço por Litro (R$)</label>
        <input type="number" step="0.01" class="form-control" id="preco_litro" name="preco_litro" required value="<?= htmlspecialchars($resina['preco_litro']) ?>">
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <a href="?pagina=resinas" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var inputCor = document.getElementById('cor');
  var corSugestoesList = document.getElementById('cor-sugestoes');
  var coresDisponiveis = <?= json_encode(array_values(array_unique(array_filter(array_map('trim', is_array($coresDisponiveis) ? $coresDisponiveis : []), static function ($cor) { return $cor !== ''; }))), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  if (!inputCor || !corSugestoesList) {
    return;
  }

  var indiceSelecionado = -1;

  var fecharSugestoes = function () {
    corSugestoesList.classList.remove('d-block');
    corSugestoesList.classList.add('d-none');
    corSugestoesList.innerHTML = '';
    indiceSelecionado = -1;
  };

  var atualizarSelecao = function () {
    var itens = corSugestoesList.querySelectorAll('li');
    itens.forEach(function (li, index) {
      li.classList.toggle('active', index === indiceSelecionado);
    });
  };

  var renderizarSugestoes = function (termo) {
    var termoNormalizado = (termo || '').toLocaleLowerCase();
    var sugeridas = Array.isArray(coresDisponiveis)
      ? coresDisponiveis
        .filter(function (cor) {
          return typeof cor === 'string' && cor.trim() !== '';
        })
        .filter(function (cor) {
          if (!termoNormalizado) {
            return true;
          }
          return cor.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 20)
      : [];

    corSugestoesList.innerHTML = '';
    indiceSelecionado = -1;

    if (!sugeridas.length) {
      fecharSugestoes();
      return;
    }

    sugeridas.forEach(function (sugestao, index) {
      var li = document.createElement('li');
      li.className = 'list-group-item list-group-item-action py-2';
      li.textContent = sugestao;
      li.addEventListener('mousedown', function (evento) {
        evento.preventDefault();
        inputCor.value = sugestao;
        fecharSugestoes();
      });
      li.addEventListener('mouseenter', function () {
        indiceSelecionado = index;
        atualizarSelecao();
      });
      corSugestoesList.appendChild(li);
    });

    corSugestoesList.classList.remove('d-none');
    corSugestoesList.classList.add('d-block');
  };

  inputCor.addEventListener('input', function () {
    renderizarSugestoes(this.value.trim());
  });

  inputCor.addEventListener('focus', function () {
    renderizarSugestoes(this.value.trim());
  });

  inputCor.addEventListener('keydown', function (evento) {
    var itens = corSugestoesList.querySelectorAll('li');
    if (!itens.length) {
      return;
    }

    if (evento.key === 'ArrowDown') {
      evento.preventDefault();
      indiceSelecionado = (indiceSelecionado + 1) % itens.length;
      atualizarSelecao();
    } else if (evento.key === 'ArrowUp') {
      evento.preventDefault();
      indiceSelecionado = (indiceSelecionado - 1 + itens.length) % itens.length;
      atualizarSelecao();
    } else if (evento.key === 'Enter') {
      if (indiceSelecionado >= 0 && indiceSelecionado < itens.length) {
        evento.preventDefault();
        inputCor.value = itens[indiceSelecionado].textContent || '';
        fecharSugestoes();
      }
    } else if (evento.key === 'Escape') {
      fecharSugestoes();
    }
  });

  document.addEventListener('click', function (evento) {
    if (evento.target !== inputCor && !corSugestoesList.contains(evento.target)) {
      fecharSugestoes();
    }
  });
});
</script>