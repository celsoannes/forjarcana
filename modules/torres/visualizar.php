<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../../app/componentes/impressora_material_cards.php';

use App\Torres\TorreController;

$id = (int) ($_GET['id'] ?? 0);
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0 || $id <= 0) {
  header('Location: /404.php');
  exit;
}

$torreController = new TorreController($pdo);
$torre = $torreController->buscarParaVisualizacao($id, $usuario_id);

if (!$torre) {
  header('Location: /404.php');
  exit;
}

$tempoMinutos = (int) ($torre['tempo_impressao'] ?? 0);
$dias = intdiv($tempoMinutos, 1440);
$resto = $tempoMinutos % 1440;
$horas = intdiv($resto, 60);
$minutos = $resto % 60;

$materialNome = '-';
$materialDetalhe = '-';
if (!empty($torre['filamento_nome'])) {
  $materialNome = (string) $torre['filamento_nome'];
  $materialDetalhe = trim((string) (($torre['filamento_marca'] ?? '') . ' ' . ($torre['filamento_tipo'] ?? '') . ' ' . ($torre['filamento_cor'] ?? '')));
  if ($materialDetalhe === '') {
    $materialDetalhe = '-';
  }
} elseif (!empty($torre['resina_nome'])) {
  $materialNome = (string) $torre['resina_nome'];
  $materialDetalhe = trim((string) (($torre['resina_marca'] ?? '') . ' ' . ($torre['resina_cor'] ?? '')));
  if ($materialDetalhe === '') {
    $materialDetalhe = '-';
  }
}

$custoMaterialValor = (float) ($torre['custo_material'] ?? 0);
$custoEnergiaValor = (float) ($torre['custo_energia'] ?? 0);
$custoDepreciacaoValor = (float) ($torre['depreciacao'] ?? 0);
$custoLavagemValor = (float) ($torre['custo_lavagem_alcool'] ?? 0);
$taxaFalhaPercentual = (float) ($torre['taxa_falha'] ?? 0);
$isFdm = strtoupper(trim((string) ($torre['impressora_tipo'] ?? ''))) === 'FDM';

$custoMaterialExibicao = round($custoMaterialValor, 2);
$custoEnergiaExibicao = round($custoEnergiaValor, 2);
$custoDepreciacaoExibicao = round($custoDepreciacaoValor, 2);
$custoLavagemExibicao = $isFdm ? 0.00 : round($custoLavagemValor, 2);

$baseCustoExibicao = $custoMaterialExibicao + $custoEnergiaExibicao + $custoDepreciacaoExibicao + $custoLavagemExibicao;
$custoTaxaFalhaValor = round($baseCustoExibicao * ($taxaFalhaPercentual / 100), 2);
if ($custoTaxaFalhaValor < 0) {
  $custoTaxaFalhaValor = 0.00;
}

$custoTotalImpressaoValor = round($baseCustoExibicao + $custoTaxaFalhaValor, 2);
$unidadesProduzidas = (int) ($torre['unidades_produzidas'] ?? 0);
$custoPorUnidadeExibicao = $unidadesProduzidas > 0 ? round($custoTotalImpressaoValor / $unidadesProduzidas, 2) : 0.00;

$markupAplicadoExibicao = (float) ($torre['markup_impressao'] ?? $torre['markup'] ?? 0);
$precoVendaSugeridoTotalExibicao = round($custoTotalImpressaoValor * $markupAplicadoExibicao, 2);
$precoVendaSugeridoUnidadeExibicao = $unidadesProduzidas > 0 ? round($precoVendaSugeridoTotalExibicao / $unidadesProduzidas, 2) : 0.00;

$lucroTotalExibicao = round($precoVendaSugeridoTotalExibicao - $custoTotalImpressaoValor, 2);
$lucroPorUnidadeExibicao = $unidadesProduzidas > 0 ? round($lucroTotalExibicao / $unidadesProduzidas, 2) : 0.00;
$porcentagemLucroExibicao = $custoTotalImpressaoValor > 0 ? (int) round(($lucroTotalExibicao / $custoTotalImpressaoValor) * 100) : 0;

$precoLojistaExibicao = round((float) ($torre['preco_lojista'] ?? 0), 2);
$lucroLojistaPorUnidadeExibicao = round($precoLojistaExibicao - $custoPorUnidadeExibicao, 2);
$descricaoLinhaTexto = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($torre['produto_descricao'] ?? ''))));

$tipoImpressoraBruto = strtoupper(trim((string) ($torre['impressora_tipo'] ?? '')));
$tipoImpressoraExibicao = $tipoImpressoraBruto === 'FDM' ? 'Filamento' : ($tipoImpressoraBruto === 'RESINA' ? 'Resina' : ((string) ($torre['impressora_tipo'] ?? '-')));

$materialMarcaDetalhamento = '';
$materialTipoDetalhamento = '';
$materialSubtipoDetalhamento = '';
$materialNomeDetalhamento = '';
$materialCorDetalhamento = '';
if (!empty($torre['filamento_nome'])) {
  $materialMarcaDetalhamento = trim((string) ($torre['filamento_marca'] ?? ''));
  $materialTipoDetalhamento = 'Filamento';
  $materialSubtipoDetalhamento = trim((string) ($torre['filamento_tipo'] ?? ''));
  $materialNomeDetalhamento = trim((string) ($torre['filamento_nome'] ?? ''));
  $materialCorDetalhamento = trim((string) ($torre['filamento_cor'] ?? ''));
} elseif (!empty($torre['resina_nome'])) {
  $materialMarcaDetalhamento = trim((string) ($torre['resina_marca'] ?? ''));
  $materialTipoDetalhamento = 'Resina';
  $materialNomeDetalhamento = trim((string) ($torre['resina_nome'] ?? ''));
  $materialCorDetalhamento = trim((string) ($torre['resina_cor'] ?? ''));
}

$detalheMaterialParteDireita = implode(' ', array_values(array_filter([
  $materialTipoDetalhamento,
  $materialSubtipoDetalhamento,
  $materialNomeDetalhamento,
], static function ($valor) {
  return $valor !== '';
})));

$materialMarcaExibicao = $materialMarcaDetalhamento !== '' ? $materialMarcaDetalhamento : '-';
$materialNomeExibicao = $detalheMaterialParteDireita !== '' ? $detalheMaterialParteDireita : '-';
$materialCorExibicao = $materialCorDetalhamento !== '' ? $materialCorDetalhamento : '-';
$materialNomeCard = $materialNomeDetalhamento !== '' ? $materialNomeDetalhamento : $materialNomeExibicao;
$materialTipoCard = $materialTipoDetalhamento !== '' ? $materialTipoDetalhamento : '-';

if ($materialMarcaDetalhamento !== '' && $detalheMaterialParteDireita !== '') {
  $materialTextoDetalhamento = $materialMarcaDetalhamento . ' - ' . $detalheMaterialParteDireita;
} elseif ($materialMarcaDetalhamento !== '') {
  $materialTextoDetalhamento = $materialMarcaDetalhamento;
} elseif ($detalheMaterialParteDireita !== '') {
  $materialTextoDetalhamento = $detalheMaterialParteDireita;
} else {
  $materialTextoDetalhamento = '-';
}

$resolverVariacoesImagem = static function (string $url): array {
  $url = trim($url);
  if ($url === '') {
    return ['grande' => '', 'thumbnail' => ''];
  }

  if (preg_match('/^(.*)_(thumbnail|thumb|pequena|media|grande)\.(png|webp)$/i', $url, $m)) {
    $base = $m[1];
    $ext = strtolower($m[3]);
    return [
      'grande' => $base . '_grande.' . $ext,
      'thumbnail' => $base . '_thumbnail.' . $ext,
    ];
  }

  return ['grande' => $url, 'thumbnail' => $url];
};

$galeriaItens = [];

if (!empty($torre['capa'])) {
  $variacoesCapa = $resolverVariacoesImagem((string) $torre['capa']);
  if ($variacoesCapa['grande'] !== '') {
    $galeriaItens[$variacoesCapa['grande']] = $variacoesCapa;
  }
}

$imagensDecodificadas = [];
if (!empty($torre['imagens'])) {
  $jsonImagens = json_decode((string) $torre['imagens'], true);
  if (is_array($jsonImagens)) {
    $imagensDecodificadas = $jsonImagens;
  }
}

foreach ($imagensDecodificadas as $img) {
  $urlGrande = '';
  $urlThumb = '';

  if (is_string($img) && trim($img) !== '') {
    $variacoes = $resolverVariacoesImagem(trim($img));
    $urlGrande = $variacoes['grande'];
    $urlThumb = $variacoes['thumbnail'];
  } elseif (is_array($img)) {
    if (!empty($img['grande']) && is_string($img['grande'])) {
      $urlGrande = trim($img['grande']);
    }
    if (!empty($img['thumbnail']) && is_string($img['thumbnail'])) {
      $urlThumb = trim($img['thumbnail']);
    }

    if ($urlGrande === '') {
      $chavesGrande = ['media', 'pequena', 'url', 'path', 'original'];
      foreach ($chavesGrande as $chave) {
        if (!empty($img[$chave]) && is_string($img[$chave])) {
          $variacoes = $resolverVariacoesImagem(trim($img[$chave]));
          $urlGrande = $variacoes['grande'];
          if ($urlThumb === '') {
            $urlThumb = $variacoes['thumbnail'];
          }
          break;
        }
      }
    }
  }

  if ($urlGrande !== '') {
    if ($urlThumb === '') {
      $urlThumb = $resolverVariacoesImagem($urlGrande)['thumbnail'];
    }
    $galeriaItens[$urlGrande] = ['grande' => $urlGrande, 'thumbnail' => $urlThumb];
  }
}

$galeriaItens = array_values($galeriaItens);
$imagemPrincipal = $galeriaItens[0]['grande'] ?? '';
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Visualizar Torre de Dados</h3>
  </div>

  <div class="card-body">
    <?php
      renderImpressoraMaterialCards([
        'impressora_nome' => trim((string) (($torre['impressora_marca'] ?? '') . ' ' . ($torre['impressora_modelo'] ?? ''))),
        'impressora_tipo' => $tipoImpressoraExibicao,
        'impressora_detalhe_label' => 'Custo Hora',
        'impressora_detalhe_valor' => 'R$ ' . number_format((float) ($torre['impressora_custo_hora'] ?? 0), 4, ',', '.'),
        'material_nome' => $materialNomeCard,
        'material_tipo' => $materialTipoCard,
        'material_marca' => $materialMarcaExibicao,
        'material_cor' => $materialCorExibicao,
        'material_subtipo' => $materialSubtipoDetalhamento,
      ], 'mb-4');
    ?>

    <div class="row mt-3">
      <div class="col-md-4 mb-3 mb-md-0">
        <?php if ($imagemPrincipal !== ''): ?>
          <div class="mb-2 text-center">
            <img id="torreImagemPrincipal" src="<?= htmlspecialchars($imagemPrincipal) ?>" alt="Imagem da torre" style="width: 100%; height: auto; border-radius: 8px; border: 1px solid #dee2e6; display: block;">
          </div>
          <?php if (count($galeriaItens) > 1): ?>
            <div class="product-image-thumbs" id="torreImageThumbs">
              <?php foreach ($galeriaItens as $indice => $itemGaleria): ?>
                <div class="product-image-thumb torre-image-thumb <?= $indice === 0 ? 'active' : '' ?>" data-image="<?= htmlspecialchars((string) $itemGaleria['grande']) ?>">
                  <img src="<?= htmlspecialchars((string) $itemGaleria['thumbnail']) ?>" alt="Miniatura <?= (int) ($indice + 1) ?>">
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
        <h4 class="mb-3"><?= htmlspecialchars((string) ($torre['produto_nome'] ?: 'Torre sem nome')) ?></h4>
        <p class="mb-1"><strong>SKU:</strong> <?= htmlspecialchars((string) ($torre['sku_codigo'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Nome Original:</strong> <?= htmlspecialchars((string) ($torre['nome_original'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Estúdio:</strong> <?= htmlspecialchars((string) ($torre['estudio_nome'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Coleção:</strong> <?= htmlspecialchars((string) ($torre['colecao_nome'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Temática:</strong> <?= htmlspecialchars((string) (($torre['tematica_nome'] ?? '') !== '' ? $torre['tematica_nome'] : ($torre['tematica'] ?? '-'))) ?></p>
        <p class="mb-1"><strong>Descrição:</strong> <?= htmlspecialchars($descricaoLinhaTexto !== '' ? $descricaoLinhaTexto : '-') ?></p>

        <hr class="my-3">
        <h5 class="mb-3">Detalhamento do Cálculo</h5>
        <p class="mb-1"><strong>Peso Material (g):</strong> <?= number_format((float) ($torre['peso_material'] ?? 0), 0, ',', '.') ?></p>
        <p class="mb-1"><strong>Tempo de Impressão:</strong> <?= (int) $dias ?>d <?= (int) $horas ?>h <?= (int) $minutos ?>min (<?= (int) $tempoMinutos ?> min)</p>
        <p class="mb-1"><strong>Unidades Produzidas:</strong> <?= (int) ($torre['unidades_produzidas'] ?? 0) ?></p>
        <p class="mb-1"><strong>Taxa de Falha:</strong> <?= number_format($taxaFalhaPercentual, 0, ',', '.') ?>%</p>
        <p class="mb-1"><strong>Markup:</strong> <?= number_format($markupAplicadoExibicao, 2, ',', '.') ?></p>
        <p class="mb-0"><strong>Observações:</strong> <?= htmlspecialchars((string) (($torre['observacoes'] ?? '') !== '' ? $torre['observacoes'] : '-')) ?></p>
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
              <div class="col-12 col-sm-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo Material</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoMaterialExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo Energia</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoEnergiaExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo Depreciação</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoDepreciacaoExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <?php if (!$isFdm): ?>
                <div class="col-12 col-sm-6">
                  <div class="info-box bg-light">
                    <div class="info-box-content">
                      <span class="info-box-text text-center text-muted">Custo Lavagem Álcool</span>
                      <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoLavagemExibicao, 2, ',', '.') ?></span>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
              <div class="col-12 col-sm-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Custo Taxa de Falha</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoTaxaFalhaValor, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="info-box bg-warning">
                  <div class="info-box-content">
                    <span class="info-box-text text-center font-weight-bold" style="font-size:1.2em;">
                      <i class="fas fa-coins"></i> Custo Total Impressão
                    </span>
                    <span class="info-box-number text-center font-weight-bold" style="font-size:1.5em;">
                      R$ <?= number_format($custoTotalImpressaoValor, 2, ',', '.') ?>
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
                      R$ <?= number_format($custoPorUnidadeExibicao, 2, ',', '.') ?>
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
                <span class="info-box-number text-center text-muted mb-0"><?= number_format($markupAplicadoExibicao, 2, ',', '.') ?></span>
                <span class="info-box-text text-center text-muted">Lucro Total: R$ <?= number_format($lucroTotalExibicao, 2, ',', '.') ?> (<?= $porcentagemLucroExibicao ?>%)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <h5 class="mt-4 mb-3">Preços e Margem</h5>
    <div class="row">
      <div class="col-12 col-md-6">
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
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoPorUnidadeExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Lucro por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($lucroLojistaPorUnidadeExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="info-box bg-info mb-0">
              <div class="info-box-content">
                <span class="info-box-text text-center font-weight-bold">Preço Lojista</span>
                <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format($precoLojistaExibicao, 2, ',', '.') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 mt-2 mt-md-0">
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
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format($custoPorUnidadeExibicao, 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="info-box bg-light">
                  <div class="info-box-content">
                    <span class="info-box-text text-center text-muted">Lucro por unidade</span>
                    <span class="info-box-number text-center text-muted mb-0">R$ <?= number_format((float) ($torre['lucro_consumidor_final'] ?? 0), 2, ',', '.') ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="info-box bg-success mb-0">
              <div class="info-box-content">
                <span class="info-box-text text-center font-weight-bold">Preço Consumidor Final</span>
                <span class="info-box-number text-center font-weight-bold mb-0">R$ <?= number_format((float) ($torre['preco_consumidor_final'] ?? 0), 2, ',', '.') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="card-footer">
    <a href="?pagina=torres&acao=adicionar" class="btn btn-primary">Adicionar Novo Produto</a>
    <a href="?pagina=torres&acao=editar&id=<?= (int) $torre['id'] ?>" class="btn btn-info">Editar Este Produto</a>
    <a href="?pagina=produtos" class="btn btn-secondary">Voltar para Listagem de Produtos</a>
  </div>
</div>

<script>
  (function() {
    var imagemPrincipal = document.getElementById('torreImagemPrincipal');
    var thumbs = document.querySelectorAll('.torre-image-thumb');

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