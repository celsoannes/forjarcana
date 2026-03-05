<?php
require_once __DIR__ . '/../../app/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0 || $id <= 0) {
    header('Location: /404.php');
    exit;
}

$temColunaTabela = static function (PDO $pdo, string $tabela, string $coluna): bool {
    static $cache = [];
    $chave = $tabela . ':' . $coluna;

    if (array_key_exists($chave, $cache)) {
        return $cache[$chave];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
        $cache[$chave] = false;
        return false;
    }

    $sql = "SHOW COLUMNS FROM `{$tabela}` LIKE " . $pdo->quote($coluna);
    $stmt = $pdo->query($sql);
    $cache[$chave] = (bool) ($stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false);

    return $cache[$chave];
};

$possuiColunaFornecedorId = $temColunaTabela($pdo, 'mapas', 'fornecedor_id');
$possuiColunaFornecedorTexto = $temColunaTabela($pdo, 'mapas', 'fornecedor');
$possuiColunaLinkCompra = $temColunaTabela($pdo, 'mapas', 'link_compra');

$sqlSelect = "SELECT
    m.*,
    p.nome AS produto_nome,
    p.descricao AS produto_descricao,
    p.preco_lojista,
    p.preco_consumidor_final,
    p.lucro_lojista,
    p.lucro_consumidor_final,
    p.markup,
    c.custo_total,
    c.custo_por_unidade,
    s.sku AS sku_codigo";

if ($possuiColunaFornecedorId) {
    $sqlSelect .= ", f.nome_fantasia AS fornecedor_nome";
} elseif ($possuiColunaFornecedorTexto) {
    $sqlSelect .= ", m.fornecedor AS fornecedor_nome";
} else {
    $sqlSelect .= ", NULL AS fornecedor_nome";
}

$sqlSelect .= "
FROM mapas m
LEFT JOIN produtos p ON p.id = m.produto_id
LEFT JOIN custos c ON c.produto_id = m.produto_id
LEFT JOIN sku s ON s.produto_id = m.produto_id AND s.usuario_id = m.usuario_id";

if ($possuiColunaFornecedorId) {
    $sqlSelect .= "\nLEFT JOIN fornecedores f ON f.id = m.fornecedor_id";
}

$sqlSelect .= "\nWHERE m.id = ? AND m.usuario_id = ?\nLIMIT 1";

$stmtMapa = $pdo->prepare($sqlSelect);
$stmtMapa->execute([$id, $usuario_id]);
$mapa = $stmtMapa->fetch(PDO::FETCH_ASSOC);

if (!$mapa) {
    header('Location: /404.php');
    exit;
}

$nomeProduto = trim((string) ($mapa['produto_nome'] ?? $mapa['nome'] ?? 'Mapa sem nome'));
$skuCodigo = trim((string) ($mapa['sku_codigo'] ?? $mapa['id_sku'] ?? '-'));
$fornecedorNome = trim((string) ($mapa['fornecedor_nome'] ?? '-'));
if ($fornecedorNome === '') {
    $fornecedorNome = '-';
}

$formatoGrade = trim((string) ($mapa['formato_grade'] ?? '-'));
$formatoGradeOpcoes = [
  'sq-25' => 'Quadrado 1" (25.4mm)',
  'sq-38' => 'Quadrado 1.5" (38mm)',
  'hx-25' => 'Hexágono 1" (25mm)',
  'hx-32' => 'Hexágono 1.25" (32mm)',
  'hx-12' => 'Hexágono Hexcrawl (12mm)',
  'hx-30' => 'Escaramuça (30mm)',
  'dt-25' => 'Grade de Pontos (Dots)',
  'none' => 'Sem Grade (Liso)',
];
$formatoGradeExibicao = $formatoGradeOpcoes[$formatoGrade] ?? ($formatoGrade !== '' ? $formatoGrade : '-');
$material = trim((string) ($mapa['material'] ?? '-'));
$largura = (float) ($mapa['largura'] ?? 0);
$comprimento = (float) ($mapa['comprimento'] ?? 0);
$unidadesProduzidas = (int) ($mapa['unidades_produzidas'] ?? 0);
$descricaoLinhaTexto = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($mapa['produto_descricao'] ?? $mapa['descricao'] ?? ''))));

$linkCompra = $possuiColunaLinkCompra ? trim((string) ($mapa['link_compra'] ?? '')) : '';

$custoTotal = round((float) ($mapa['custo_total'] ?? 0), 2);
$custoPorUnidade = round((float) ($mapa['custo_por_unidade'] ?? 0), 2);
$precoLojista = round((float) ($mapa['preco_lojista'] ?? 0), 2);
$precoConsumidorFinal = round((float) ($mapa['preco_consumidor_final'] ?? 0), 2);
$lucroLojista = round((float) ($mapa['lucro_lojista'] ?? ($precoLojista - $custoPorUnidade)), 2);
$lucroConsumidorFinal = round((float) ($mapa['lucro_consumidor_final'] ?? ($precoConsumidorFinal - $custoPorUnidade)), 2);

$markup = (float) ($mapa['markup'] ?? 0);
if ($markup <= 0 && $custoPorUnidade > 0) {
    $markup = round($precoConsumidorFinal / $custoPorUnidade, 2);
}

$precoVendaSugeridoUnidadeExibicao = $precoConsumidorFinal;
$lucroPorUnidadeExibicao = $lucroConsumidorFinal;
$lucroTotalExibicao = $lucroConsumidorFinal;
$porcentagemLucroExibicao = $custoPorUnidade > 0 ? (int) round(($lucroTotalExibicao / $custoPorUnidade) * 100) : 0;

$imagemPrincipal = trim((string) ($mapa['imagem_capa'] ?? ''));

$imagensGaleria = [];
if ($imagemPrincipal !== '') {
    $imagensGaleria[$imagemPrincipal] = $imagemPrincipal;
}

$imagensJson = trim((string) ($mapa['imagens'] ?? ''));
if ($imagensJson !== '') {
    $listaImagens = json_decode($imagensJson, true);
    if (is_array($listaImagens)) {
        foreach ($listaImagens as $itemImagem) {
            if (is_string($itemImagem) && trim($itemImagem) !== '') {
                $imagensGaleria[trim($itemImagem)] = trim($itemImagem);
            } elseif (is_array($itemImagem)) {
                $urlImagem = '';
                if (!empty($itemImagem['grande']) && is_string($itemImagem['grande'])) {
                    $urlImagem = trim($itemImagem['grande']);
                } elseif (!empty($itemImagem['url']) && is_string($itemImagem['url'])) {
                    $urlImagem = trim($itemImagem['url']);
                }

                if ($urlImagem !== '') {
                    $imagensGaleria[$urlImagem] = $urlImagem;
                }
            }
        }
    }
}

$imagensGaleria = array_values($imagensGaleria);
if ($imagemPrincipal === '' && !empty($imagensGaleria)) {
    $imagemPrincipal = (string) $imagensGaleria[0];
}
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Visualizar Mapa</h3>
  </div>

  <div class="card-body">
    <div class="row mt-3">
      <div class="col-md-4 mb-3 mb-md-0">
        <?php if ($imagemPrincipal !== ''): ?>
          <div class="mb-2 text-center">
            <img id="mapaImagemPrincipal" src="<?= htmlspecialchars($imagemPrincipal) ?>" alt="Imagem do mapa" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid #dee2e6; display: block;">
          </div>
          <?php if (count($imagensGaleria) > 1): ?>
            <div class="product-image-thumbs" id="mapaImageThumbs">
              <?php foreach ($imagensGaleria as $indice => $urlImagem): ?>
                <div class="product-image-thumb mapa-image-thumb <?= $indice === 0 ? 'active' : '' ?>" data-image="<?= htmlspecialchars((string) $urlImagem) ?>">
                  <img src="<?= htmlspecialchars((string) $urlImagem) ?>" alt="Miniatura <?= (int) ($indice + 1) ?>">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="border rounded d-flex align-items-center justify-content-center text-muted" style="height: 260px;">
            Sem imagem
          </div>
        <?php endif; ?>
      </div>

      <div class="col-md-8">
        <h4 class="mb-3"><?= htmlspecialchars($nomeProduto) ?></h4>
        <p class="mb-1"><strong>SKU:</strong> <?= htmlspecialchars($skuCodigo !== '' ? $skuCodigo : '-') ?></p>
        <p class="mb-1"><strong>Fornecedor:</strong> <?= htmlspecialchars($fornecedorNome) ?></p>
        <p class="mb-1"><strong>Material:</strong> <?= htmlspecialchars($material !== '' ? $material : '-') ?></p>
        <p class="mb-1"><strong>Formato da Grade:</strong> <?= htmlspecialchars($formatoGradeExibicao) ?></p>
        <p class="mb-1"><strong>Tamanho:</strong> Largura <?= number_format($largura, 2, ',', '.') ?> x Comprimento <?= number_format($comprimento, 2, ',', '.') ?></p>
        <p class="mb-1"><strong>Unidades Produzidas:</strong> <?= $unidadesProduzidas > 0 ? $unidadesProduzidas : '-' ?></p>
        <p class="mb-1"><strong>Link de Compra:</strong>
          <?php if ($linkCompra !== ''): ?>
            <a href="<?= htmlspecialchars($linkCompra) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($linkCompra) ?></a>
          <?php else: ?>
            -
          <?php endif; ?>
        </p>
        <p class="mb-0"><strong>Descrição:</strong> <?= htmlspecialchars($descricaoLinhaTexto !== '' ? $descricaoLinhaTexto : '-') ?></p>
      </div>
    </div>

    <hr class="my-4">
    <h5 class="mb-3">Resumo Financeiro</h5>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Custos</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <div class="info-box bg-warning">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold" style="font-size:1.2em;">
                      <i class="fas fa-coins"></i> Custo Total Impressão
                    </span>
                    <span class="info-box-number text-center font-weight-bold" style="font-size:1.5em;">
                      R$ <?= number_format($custoTotal, 2, ',', '.') ?>
                    </span>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="info-box bg-primary mb-0">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold" style="font-size:1.2em;">
                      <i class="fas fa-balance-scale"></i> Custo por Unidade
                    </span>
                    <span class="info-box-number text-center font-weight-bold" style="font-size:1.5em;">
                      R$ <?= number_format($custoPorUnidade, 2, ',', '.') ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 mt-2">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Lucro</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12 col-md-6">
                <div class="info-box bg-success">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold">Preço Venda Sugerido (Unidade)</span>
                    <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format($precoVendaSugeridoUnidadeExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="info-box bg-success">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold">Lucro por Unidade</span>
                    <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format($lucroPorUnidadeExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="info-box bg-light mb-0">
              <div class="info-box-content">
                <span class="info-box-text text-center text-muted">Markup Aplicado</span>
                <span class="info-box-number text-center text-muted mb-0"><?= number_format($markup, 2, ',', '.') ?></span>
                <span class="info-box-text text-center text-muted">Lucro Total: R$ <?= number_format($lucroTotalExibicao, 2, ',', '.') ?> (<?= $porcentagemLucroExibicao ?>%)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <h5 class="mb-3">Preços e Margem</h5>
      </div>

      <div class="col-12 col-md-6 mt-2">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Lojista</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoPorUnidade, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Lucro por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($lucroLojista, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="info-box bg-info">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold">Preço Lojista</span>
                    <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format($precoLojista, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 mt-2">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Consumidor Final</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoPorUnidade, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Lucro por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($lucroConsumidorFinal, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="info-box bg-success">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold">Preço Consumidor Final</span>
                    <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format($precoConsumidorFinal, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer">
    <a href="?pagina=mapas&acao=adicionar" class="btn btn-primary">Adicionar Novo Mapa</a>
    <a href="?pagina=mapas&acao=editar&id=<?= (int) $mapa['id'] ?>&fluxo=mapas" class="btn btn-info">Editar Este Mapa</a>
    <a href="?pagina=produtos" class="btn btn-secondary">Voltar para Listagem de Produtos</a>
  </div>
</div>

<script>
  (function() {
    var imagemPrincipal = document.getElementById('mapaImagemPrincipal');
    var thumbs = document.querySelectorAll('.mapa-image-thumb');

    if (!imagemPrincipal || !thumbs.length) {
      return;
    }

    thumbs.forEach(function(thumb) {
      thumb.addEventListener('click', function() {
        var novaImagem = thumb.getAttribute('data-image');
        if (!novaImagem) {
          return;
        }

        imagemPrincipal.setAttribute('src', novaImagem);
        thumbs.forEach(function(item) { item.classList.remove('active'); });
        thumb.classList.add('active');
      });
    });
  })();
</script>
