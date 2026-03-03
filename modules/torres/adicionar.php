<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/upload_imagem.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$usuario_uuid = trim($_SESSION['usuario_uuid'] ?? '');
$erro = '';
$impressora_id = (int) ($_GET['impressora_id'] ?? 0);
$filamento_id = (int) ($_GET['filamento_id'] ?? 0);
$resina_id = (int) ($_GET['resina_id'] ?? 0);
$selecao_confirmacao = null;
$aviso_selecao = '';
$estudios_disponiveis = [];
$colecoes_disponiveis = [];
$tematicas_disponiveis = [];
$outras_caracteristicas_disponiveis = [];

function normalizarListaTags(string $valor): array {
  $itens = array_map('trim', explode(',', $valor));
  $resultado = [];
  $chaves = [];

  foreach ($itens as $item) {
    if ($item === '') {
      continue;
    }

    $chave = function_exists('mb_strtolower') ? mb_strtolower($item, 'UTF-8') : strtolower($item);
    if (isset($chaves[$chave])) {
      continue;
    }

    $chaves[$chave] = true;
    $resultado[] = $item;
  }

  return $resultado;
}

function resolverEstudioTorre(PDO $pdo, int $usuarioId, string $entrada): ?array {
  $entrada = trim($entrada);
  if ($entrada === '') {
    return null;
  }

  $stmtEstudio = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1");
  $stmtEstudio->execute([$usuarioId, $entrada]);
  $estudio = $stmtEstudio->fetch(PDO::FETCH_ASSOC);

  if ($estudio) {
    return $estudio;
  }

  $stmtNovoEstudio = $pdo->prepare("INSERT INTO estudios (nome, site, usuario_id) VALUES (?, ?, ?)");
  $stmtNovoEstudio->execute([$entrada, 'https://pendente.local', $usuarioId]);

  return [
    'id' => (int) $pdo->lastInsertId(),
    'nome' => $entrada,
  ];
}

function resolverColecaoTorre(PDO $pdo, int $usuarioId, int $estudioId, string $entrada): ?array {
  $entrada = trim($entrada);
  if ($entrada === '') {
    return null;
  }

  if ($estudioId > 0) {
    $stmtColecao = $pdo->prepare("SELECT c.id, c.nome, c.estudio_id
      FROM colecoes c
      WHERE c.usuario_id = ?
        AND c.estudio_id = ?
        AND LOWER(c.nome) = LOWER(?)
      LIMIT 1");
    $stmtColecao->execute([$usuarioId, $estudioId, $entrada]);
    $colecao = $stmtColecao->fetch(PDO::FETCH_ASSOC);

    if ($colecao) {
      return $colecao;
    }

    $stmtNovaColecao = $pdo->prepare("INSERT INTO colecoes (estudio_id, nome, usuario_id) VALUES (?, ?, ?)");
    $stmtNovaColecao->execute([$estudioId, $entrada, $usuarioId]);

    return [
      'id' => (int) $pdo->lastInsertId(),
      'nome' => $entrada,
      'estudio_id' => $estudioId,
    ];
  }

  $stmtColecaoSemEstudio = $pdo->prepare("SELECT c.id, c.nome, c.estudio_id
    FROM colecoes c
    WHERE c.usuario_id = ?
      AND LOWER(c.nome) = LOWER(?)
    ORDER BY c.id DESC
    LIMIT 1");
  $stmtColecaoSemEstudio->execute([$usuarioId, $entrada]);
  $colecaoSemEstudio = $stmtColecaoSemEstudio->fetch(PDO::FETCH_ASSOC);

  return $colecaoSemEstudio ?: null;
}

function resolverTematicaTorre(PDO $pdo, string $entrada): ?array {
  $entrada = trim($entrada);
  if ($entrada === '') {
    return null;
  }

  $stmtTematica = $pdo->prepare("SELECT id, nome FROM tematicas WHERE LOWER(nome) = LOWER(?) LIMIT 1");
  $stmtTematica->execute([$entrada]);
  $tematica = $stmtTematica->fetch(PDO::FETCH_ASSOC);

  if ($tematica) {
    return $tematica;
  }

  $stmtNovaTematica = $pdo->prepare("INSERT INTO tematicas (nome) VALUES (?)");
  $stmtNovaTematica->execute([$entrada]);

  return [
    'id' => (int) $pdo->lastInsertId(),
    'nome' => $entrada,
  ];
}

function gerarSiglaEstudioTorre(string $estudioNome): string {
  $estudioNome = trim($estudioNome);
  if ($estudioNome === '') {
    return 'XXX';
  }

  $partesOriginais = preg_split('/\s+/', $estudioNome) ?: [];
  $partes = [];

  foreach ($partesOriginais as $parteOriginal) {
    $parteOriginal = trim((string) $parteOriginal);
    if ($parteOriginal === '') {
      continue;
    }

    $normalizada = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $parteOriginal);
    if ($normalizada === false) {
      $normalizada = $parteOriginal;
    }

    $normalizada = strtoupper($normalizada);
    $normalizada = preg_replace('/[^A-Z0-9]/', '', $normalizada);
    if ($normalizada === '') {
      continue;
    }

    $partes[] = $normalizada;
  }

  if (empty($partes)) {
    return 'XXX';
  }

  if (count($partes) === 1) {
    return str_pad(substr($partes[0], 0, 3), 3, 'X');
  }

  $iniciais = '';
  foreach ($partes as $parte) {
    $iniciais .= substr($parte, 0, 1);
  }

  return $iniciais;
}

function gerarSkuTorre(PDO $pdo, string $estudioNome): string {
  $prefixo = 'TOR-' . gerarSiglaEstudioTorre($estudioNome);

  do {
    $numero = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $sku = $prefixo . '-' . $numero;
    $stmtSku = $pdo->prepare("SELECT COUNT(*) FROM sku WHERE sku = ?");
    $stmtSku->execute([$sku]);
    $existe = (int) $stmtSku->fetchColumn() > 0;
  } while ($existe);

  return $sku;
}

if ($usuario_uuid === '' && $usuario_id > 0) {
    try {
        $stmtUuid = $pdo->prepare("SELECT uuid FROM usuarios WHERE id = ? LIMIT 1");
        $stmtUuid->execute([$usuario_id]);
        $usuario_uuid = (string) ($stmtUuid->fetchColumn() ?: '');
    } catch (Throwable $e) {
        $usuario_uuid = '';
    }
}

if ($impressora_id > 0) {
  $stmtImpressora = $pdo->prepare("SELECT id, marca, modelo, tipo, potencia, fator_uso, custo_hora FROM impressoras WHERE id = ? AND usuario_id = ?");
  $stmtImpressora->execute([$impressora_id, $usuario_id]);
  $impressoraSelecionada = $stmtImpressora->fetch(PDO::FETCH_ASSOC);

  if ($impressoraSelecionada) {
    $materialSelecionado = null;

    if ($impressoraSelecionada['tipo'] === 'Resina' && $resina_id > 0) {
      $stmtMaterial = $pdo->prepare("SELECT id, nome, marca, cor, preco_litro FROM resinas WHERE id = ? AND usuario_id = ?");
      $stmtMaterial->execute([$resina_id, $usuario_id]);
      $materialSelecionado = $stmtMaterial->fetch(PDO::FETCH_ASSOC);

      if ($materialSelecionado) {
        $selecao_confirmacao = [
          'impressora' => $impressoraSelecionada,
          'material_tipo' => 'Resina',
          'material' => $materialSelecionado,
        ];
      } else {
        $aviso_selecao = 'A resina selecionada não foi encontrada para este usuário.';
      }
    } elseif ($impressoraSelecionada['tipo'] === 'FDM' && $filamento_id > 0) {
      $stmtMaterial = $pdo->prepare("SELECT id, nome, marca, cor, tipo, preco_kilo FROM filamento WHERE id = ? AND usuario_id = ?");
      $stmtMaterial->execute([$filamento_id, $usuario_id]);
      $materialSelecionado = $stmtMaterial->fetch(PDO::FETCH_ASSOC);

      if ($materialSelecionado) {
        $selecao_confirmacao = [
          'impressora' => $impressoraSelecionada,
          'material_tipo' => 'Filamento',
          'material' => $materialSelecionado,
        ];
      } else {
        $aviso_selecao = 'O filamento selecionado não foi encontrado para este usuário.';
      }
    } else {
      $aviso_selecao = 'Seleção de material não corresponde ao tipo da impressora escolhida.';
    }
  } else {
    $aviso_selecao = 'A impressora selecionada não foi encontrada para este usuário.';
  }
}

  try {
    $stmtEstudios = $pdo->prepare("SELECT id, nome FROM estudios WHERE usuario_id = ? ORDER BY nome");
    $stmtEstudios->execute([$usuario_id]);
    $estudios_disponiveis = $stmtEstudios->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $estudios_disponiveis = [];
  }

  try {
    $stmtColecoes = $pdo->prepare("SELECT c.id, c.nome, e.nome AS estudio_nome, e.id AS estudio_id FROM colecoes c INNER JOIN estudios e ON e.id = c.estudio_id WHERE c.usuario_id = ? ORDER BY e.nome, c.nome");
    $stmtColecoes->execute([$usuario_id]);
    $colecoes_disponiveis = $stmtColecoes->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $colecoes_disponiveis = [];
  }

  try {
    $stmtTematicas = $pdo->query("SELECT id, nome FROM tematicas ORDER BY nome");
    $tematicas_disponiveis = $stmtTematicas->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $tematicas_disponiveis = [];
  }

  try {
    $stmtOutrasCaracteristicas = $pdo->prepare("SELECT DISTINCT outras_caracteristicas FROM miniaturas WHERE usuario_id = ? AND outras_caracteristicas IS NOT NULL AND outras_caracteristicas <> ''");
    $stmtOutrasCaracteristicas->execute([$usuario_id]);
    $linhasOutrasCaracteristicas = $stmtOutrasCaracteristicas->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $itensUnicos = [];
    $controleUnicos = [];
    foreach ($linhasOutrasCaracteristicas as $linhaOutrasCaracteristicas) {
      if (!is_string($linhaOutrasCaracteristicas) || trim($linhaOutrasCaracteristicas) === '') {
        continue;
      }

      $itensLinha = array_map('trim', explode(',', $linhaOutrasCaracteristicas));
      foreach ($itensLinha as $itemLinha) {
        if ($itemLinha === '') {
          continue;
        }

        $chaveItem = function_exists('mb_strtolower') ? mb_strtolower($itemLinha, 'UTF-8') : strtolower($itemLinha);
        if (isset($controleUnicos[$chaveItem])) {
          continue;
        }

        $controleUnicos[$chaveItem] = true;
        $itensUnicos[] = $itemLinha;
      }
    }

    natcasesort($itensUnicos);
    $outras_caracteristicas_disponiveis = array_values($itensUnicos);
  } catch (Throwable $e) {
    $outras_caracteristicas_disponiveis = [];
  }

  $foto = null;
  $imagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $nome_original = trim($_POST['nome_original'] ?? '');
    $estudio = trim($_POST['estudio'] ?? '');
    $colecao = trim($_POST['colecao'] ?? '');
    $colecoesSelecionadas = normalizarListaTags($colecao);
    $tematica = trim($_POST['tematica'] ?? '');
    $outras_caracteristicas = trim($_POST['outras_caracteristicas'] ?? '');
    $descricao_produto = trim($_POST['descricao_produto'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $markup_lojista = (float) str_replace(',', '.', trim($_POST['markup_lojista'] ?? '2'));
    $markup_consumidor_final = (float) str_replace(',', '.', trim($_POST['markup_consumidor_final'] ?? '5'));
    $gramas = (float) str_replace(',', '.', trim($_POST['gramas'] ?? '0'));
    $tempo_dias = (int) ($_POST['tempo_dias'] ?? 0);
    $tempo_horas = (int) ($_POST['tempo_horas'] ?? 0);
    $tempo_minutos = (int) ($_POST['tempo_minutos'] ?? 0);
    $tempo_total_min = ($tempo_dias * 24 * 60) + ($tempo_horas * 60) + $tempo_minutos;
    $unidades_produzidas = (int) ($_POST['unidades_produzidas'] ?? 0);
    $taxa_falha = (float) str_replace(',', '.', trim($_POST['taxa_falha'] ?? '10'));

    $custo_material = 0.00;
    $custo_lavagem_alcool = 0.00;
    $custo_energia = 0.00;
    $custo_depreciacao = 0.00;
    $custo_total_impressao = 0.00;

    $fotoExistente = trim((string) ($_POST['foto_existente'] ?? ''));
    $foto = $fotoExistente !== '' ? $fotoExistente : null;
    $imagens = [];
    $imagensExistentesRaw = trim((string) ($_POST['imagens_existentes'] ?? ''));
    if ($imagensExistentesRaw !== '') {
      $imagensExistentes = json_decode($imagensExistentesRaw, true);
      if (is_array($imagensExistentes)) {
        foreach ($imagensExistentes as $imagemExistente) {
          if (is_string($imagemExistente) && trim($imagemExistente) !== '') {
            $imagens[] = trim($imagemExistente);
          }
        }
      }
    }

    if (!$nome) {
        $erro = 'Preencha o nome da torre.';
    }

    if (!$erro && $usuario_uuid === '') {
        $erro = 'Não foi possível identificar o UUID do usuário para upload das imagens.';
    }

    if (!$erro && !$selecao_confirmacao) {
      $erro = 'Selecione impressora e material antes de adicionar a torre de dados.';
    }
    if (!$erro && $gramas <= 0) {
      $erro = 'Informe um valor válido para Gramas (g).';
    }
    if (!$erro && $tempo_total_min <= 0) {
      $erro = 'Informe um tempo de impressão válido.';
    }
    if (!$erro && $unidades_produzidas <= 0) {
      $erro = 'Informe um valor válido para Unidades Produzidas.';
    }
    if (!$erro && $taxa_falha <= 0) {
      $erro = 'Informe uma Taxa de Falha (%) maior que zero.';
    }

    if (!$erro) {
      $tempo_total_horas = $tempo_total_min / 60;
      $potencia = isset($selecao_confirmacao['impressora']['potencia']) ? (float) $selecao_confirmacao['impressora']['potencia'] : 0.0;
      $fator_uso = isset($selecao_confirmacao['impressora']['fator_uso']) ? (float) $selecao_confirmacao['impressora']['fator_uso'] : 1.0;
      $custo_hora = isset($selecao_confirmacao['impressora']['custo_hora']) ? (float) $selecao_confirmacao['impressora']['custo_hora'] : 0.0;

      $stmtEnergia = $pdo->prepare("SELECT valor_kwh FROM energia WHERE usuario_id = ?");
      $stmtEnergia->execute([$usuario_id]);
      $energia = $stmtEnergia->fetch(PDO::FETCH_ASSOC);
      $valor_kwh = $energia ? (float) $energia['valor_kwh'] : 1.0;

      $custo_energia = ($potencia * $tempo_total_horas * $fator_uso * $valor_kwh) / 1000;
      $custo_depreciacao = ($custo_hora / 60) * $tempo_total_min;

      if ($selecao_confirmacao['material_tipo'] === 'Filamento') {
        $preco_kilo = isset($selecao_confirmacao['material']['preco_kilo']) ? (float) $selecao_confirmacao['material']['preco_kilo'] : 0.0;
        $custo_material = ($gramas / 1000) * $preco_kilo;
        $base_custo = $custo_material + $custo_energia + $custo_depreciacao;
        $custo_total_impressao = $base_custo + (($base_custo * 0.7) / $taxa_falha);
      } else {
        $preco_litro = isset($selecao_confirmacao['material']['preco_litro']) ? (float) $selecao_confirmacao['material']['preco_litro'] : 0.0;
        $custo_material = ($gramas / 1000) * $preco_litro;

        $stmtAlcool = $pdo->prepare("SELECT preco_litro FROM alcool WHERE usuario_id = ?");
        $stmtAlcool->execute([$usuario_id]);
        $alcool = $stmtAlcool->fetch(PDO::FETCH_ASSOC);
        $preco_litro_alcool = $alcool ? (float) $alcool['preco_litro'] : 0.0;
        $custo_lavagem_alcool = ($preco_litro_alcool / 1000) * $gramas;

        $base_custo = $custo_material + $custo_energia + $custo_depreciacao + $custo_lavagem_alcool;
        $custo_total_impressao = $base_custo + (($base_custo * 0.7) / $taxa_falha);
      }

      $custo_total_impressao = round($custo_total_impressao, 2);
    }

    $tamanhosUpload = [
        'thumbnail' => [64, 64],
        'pequena' => [128, 128],
        'media' => [256, 256],
        'grande' => [512, 512],
    ];

    if (!$erro && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fotoUpload = uploadImagem($_FILES['foto'], $usuario_uuid, 'usuarios', $tamanhosUpload, 'torre_CAPA', false);
        if ($fotoUpload === false) {
            $erro = 'Erro ao enviar a imagem de capa.';
        } else {
            $foto = $fotoUpload;
        }
    }

    if (!$erro && isset($_FILES['fotos']) && isset($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
        $totalArquivos = count($_FILES['fotos']['name']);
        for ($i = 0; $i < $totalArquivos; $i++) {
            $nomeArquivo = trim((string) ($_FILES['fotos']['name'][$i] ?? ''));
            $erroArquivo = $_FILES['fotos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

        if ($nomeArquivo === '' || $erroArquivo === UPLOAD_ERR_NO_FILE) {
          continue;
        }

        if ($erroArquivo !== UPLOAD_ERR_OK) {
                continue;
            }

            $arquivoImagem = [
                'name' => $nomeArquivo,
                'type' => $_FILES['fotos']['type'][$i] ?? '',
                'tmp_name' => $_FILES['fotos']['tmp_name'][$i] ?? '',
                'error' => $erroArquivo,
                'size' => $_FILES['fotos']['size'][$i] ?? 0,
            ];

            $imagemUpload = uploadImagem($arquivoImagem, $usuario_uuid, 'usuarios', $tamanhosUpload, 'torre_IMAGEM', false);
            if ($imagemUpload !== false) {
                $imagens[] = $imagemUpload;
            }
        }
    }

    if (!$erro) {
        try {
            $pdo->beginTransaction();

            $stmtCategoria = $pdo->prepare("SELECT id FROM categorias WHERE nome = ? LIMIT 1");
            $stmtCategoria->execute(['Torre de Dados']);
            $categoriaId = (int) ($stmtCategoria->fetchColumn() ?: 0);

            if ($categoriaId === 0) {
                $stmtInsertCategoria = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
                $stmtInsertCategoria->execute(['Torre de Dados']);
                $categoriaId = (int) $pdo->lastInsertId();
            }

            $imagensJson = !empty($imagens) ? json_encode($imagens, JSON_UNESCAPED_UNICODE) : null;
            $preco_lojista = $custo_total_impressao * $markup_lojista;
            $preco_consumidor_final = $custo_total_impressao * $markup_consumidor_final;

            $stmtProduto = $pdo->prepare("INSERT INTO produtos
                (usuario_id, nome, categoria, imagem_capa, imagens, descricao, observacoes, markup_lojista, markup_consumidor_final, preco_lojista, preco_consumidor_final)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtProduto->execute([
                $usuario_id,
                $nome,
                $categoriaId,
                $foto,
                $imagensJson,
              $descricao_produto !== '' ? $descricao_produto : null,
                $observacoes !== '' ? $observacoes : null,
                $markup_lojista,
                $markup_consumidor_final,
                $preco_lojista,
                $preco_consumidor_final,
            ]);
            $produtoId = (int) $pdo->lastInsertId();

            $estudioResolvido = resolverEstudioTorre($pdo, $usuario_id, $estudio);
            $colecaoPrincipal = (string) ($colecoesSelecionadas[0] ?? '');
            $colecaoResolvida = resolverColecaoTorre(
              $pdo,
              $usuario_id,
              (int) ($estudioResolvido['id'] ?? 0),
              $colecaoPrincipal
            );
            $tematicaResolvida = resolverTematicaTorre($pdo, $tematica);

            $estudioIdTorre = (int) ($estudioResolvido['id'] ?? ($colecaoResolvida['estudio_id'] ?? 0));
            $colecaoIdTorre = (int) ($colecaoResolvida['id'] ?? 0);
            $tematicaIdTorre = (int) ($tematicaResolvida['id'] ?? 0);
            $tematicaNomeTorre = (string) ($tematicaResolvida['nome'] ?? $tematica);
            $estudioNomeParaSku = (string) ($estudioResolvido['nome'] ?? $estudio);
            $skuCodigoTorre = gerarSkuTorre($pdo, $estudioNomeParaSku);

            $stmtInsertSku = $pdo->prepare("INSERT INTO sku (produto_id, sku, usuario_id) VALUES (?, ?, ?)");
            $stmtInsertSku->execute([$produtoId, $skuCodigoTorre, $usuario_id]);

            $markupImpressao = max(1, (int) round($markup_consumidor_final));
            $taxaFalhaImpressao = max(1, (int) round($taxa_falha));
            $pesoMaterial = max(1, (int) round($gramas));
            $custoPorUnidade = $unidades_produzidas > 0 ? round($custo_total_impressao / $unidades_produzidas, 2) : 0.00;
            $precoVendaSugerido = round($custo_total_impressao * $markup_consumidor_final, 2);
            $precoVendaSugeridoUnidade = $unidades_produzidas > 0 ? round($precoVendaSugerido / $unidades_produzidas, 2) : 0.00;
            $lucroTotal = round($precoVendaSugerido - $custo_total_impressao, 2);
            $lucroPorUnidade = $unidades_produzidas > 0 ? round($lucroTotal / $unidades_produzidas, 2) : 0.00;
            $porcentagemLucro = $custo_total_impressao > 0 ? (int) round(($lucroTotal / $custo_total_impressao) * 100) : 0;
            $filamentoIdImpressao = $selecao_confirmacao['material_tipo'] === 'Filamento' ? (int) $selecao_confirmacao['material']['id'] : null;
            $resinaIdImpressao = $selecao_confirmacao['material_tipo'] === 'Resina' ? (int) $selecao_confirmacao['material']['id'] : null;

            $stmtInsertCusto = $pdo->prepare("INSERT INTO custos (produto_id, custo_total, custo_por_unidade) VALUES (?, ?, ?)");
            $stmtInsertCusto->execute([$produtoId, $custo_total_impressao, $custoPorUnidade]);

            $stmtImpressao = $pdo->prepare("INSERT INTO impressoes
              (impressora_id, tempo_impressao, unidades_produzidas, markup, taxa_falha, valor_energia, peso_material, custo_material, custo_lavagem_alcool, custo_energia, depreciacao, custo_total_impressao, custo_por_unidade, lucro_total_impressao, lucro_por_unidade, porcentagem_lucro, preco_venda_sugerido, preco_venda_sugerido_unidade, observacoes, usuario_id, filamento_id, resina_id)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtImpressao->execute([
              (int) $selecao_confirmacao['impressora']['id'],
              $tempo_total_min,
              $unidades_produzidas,
              $markupImpressao,
              $taxaFalhaImpressao,
              $valor_kwh,
              $pesoMaterial,
              round($custo_material, 2),
              round($custo_lavagem_alcool, 2),
              round($custo_energia, 2),
              round($custo_depreciacao, 2),
              $custo_total_impressao,
              $custoPorUnidade,
              $lucroTotal,
              $lucroPorUnidade,
              $porcentagemLucro,
              $precoVendaSugerido,
              $precoVendaSugeridoUnidade,
              $observacoes !== '' ? $observacoes : null,
              $usuario_id,
              $filamentoIdImpressao,
              $resinaIdImpressao,
            ]);
            $impressaoId = (int) $pdo->lastInsertId();

            $stmtTorre = $pdo->prepare("INSERT INTO torres (id_sku, produto_id, usuario_id, id_impressao, nome_original, id_estudio, id_colecao, id_tematica, tematica, capa, imagens, outras_caracteristicas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtTorre->execute([
              $skuCodigoTorre,
                $produtoId,
                $usuario_id,
              $impressaoId > 0 ? $impressaoId : null,
                $nome_original !== '' ? $nome_original : null,
              $estudioIdTorre > 0 ? $estudioIdTorre : null,
              $colecaoIdTorre > 0 ? $colecaoIdTorre : null,
              $tematicaIdTorre > 0 ? $tematicaIdTorre : null,
              $tematicaNomeTorre !== '' ? $tematicaNomeTorre : null,
              $foto,
              $imagensJson,
              $outras_caracteristicas !== '' ? $outras_caracteristicas : null,
            ]);

            $pdo->commit();
            echo '<script>window.location.href="?pagina=torres";</script>';
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Erro ao cadastrar torre: ' . $e->getMessage();
        }
    }
}
?>

<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">Adicionar Torre de Dados</h3>
  </div>
  <form method="POST" enctype="multipart/form-data" id="form-adicionar-torre">
    <input type="hidden" id="foto_existente" name="foto_existente" value="<?= htmlspecialchars((string) ($foto ?? '')) ?>">
    <input type="hidden" id="imagens_existentes" name="imagens_existentes" value="<?= htmlspecialchars(json_encode($imagens ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body">
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <?php if ($selecao_confirmacao): ?>
        <h5 class="mb-3">Seleção confirmada</h5>
        <div class="selecao-grid mb-4">
          <div class="selecao-card">
            <div class="selecao-icon"><i class="fas fa-microscope"></i></div>
            <div class="selecao-content">
              <h2><?= htmlspecialchars($selecao_confirmacao['impressora']['marca'] . ' ' . $selecao_confirmacao['impressora']['modelo']) ?></h2>
              <p>
                <strong>Tipo:</strong> <?= htmlspecialchars($selecao_confirmacao['impressora']['tipo']) ?><br>
                <strong>Etapa:</strong> Impressora selecionada
              </p>
            </div>
          </div>

          <div class="selecao-card">
            <div class="selecao-icon">
              <i class="<?= $selecao_confirmacao['material_tipo'] === 'Resina' ? 'fa-solid fa-bottle-water' : 'fas fa-compact-disc' ?>"></i>
            </div>
            <div class="selecao-content">
              <h2><?= htmlspecialchars($selecao_confirmacao['material']['nome']) ?></h2>
              <p>
                <strong>Tipo:</strong> <?= htmlspecialchars($selecao_confirmacao['material_tipo']) ?><br>
                <strong>Marca:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['marca']) ?><br>
                <strong>Cor:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['cor']) ?>
                <?php if (!empty($selecao_confirmacao['material']['tipo'])): ?>
                  <br><strong>Subtipo:</strong> <?= htmlspecialchars($selecao_confirmacao['material']['tipo']) ?>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      <?php elseif ($aviso_selecao): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($aviso_selecao) ?></div>
      <?php endif; ?>

      <style>
        .selecao-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
          gap: 20px;
        }

        .selecao-card {
          position: relative;
          background: #fff;
          border-radius: 12px;
          padding: 28px 24px;
          box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
          border: 1px solid #e9ecef;
          display: flex;
          flex-direction: row;
          align-items: flex-start;
          gap: 16px;
          min-height: 180px;
        }

        .selecao-icon {
          font-size: 2.2rem;
          color: #007bff;
          margin-top: 2px;
          flex-shrink: 0;
        }

        .selecao-content {
          flex: 1;
        }

        .selecao-card h2 {
          font-size: 1.35rem;
          font-weight: 600;
          margin-bottom: 10px;
          color: #343a40;
        }

        .selecao-card p {
          color: #6c757d;
          font-size: 0.95rem;
          margin-bottom: 0;
        }

        .outras_caracteristicas-container,
        .colecao-container {
          border: 1px solid #ced4da;
          border-radius: 0.25rem;
          padding: 0.25rem 0.5rem;
          min-height: calc(2.25rem + 2px);
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          gap: 0.3rem;
          position: relative;
          background: #fff;
        }

        .colecao-input,
        .outras_caracteristicas-input {
          border: none;
          outline: none;
          flex: 1;
          min-width: 180px;
          padding: 0.2rem;
        }

        .colecao-tag,
        .outras_caracteristicas-tag {
          background: #e9f2ff;
          color: #004085;
          border: 1px solid #b8daff;
          border-radius: 16px;
          padding: 0.2rem 0.55rem;
          font-size: 0.85rem;
          display: inline-flex;
          align-items: center;
          gap: 0.35rem;
        }

        .colecao-tag small,
        .outras_caracteristicas-tag small {
          cursor: pointer;
          font-size: 1rem;
          line-height: 1;
          font-weight: 700;
        }

        .autocomplete-list {
          list-style: none;
          margin: 0.25rem 0 0;
          padding: 0;
          border: 1px solid #dee2e6;
          border-radius: 0.25rem;
          background: #fff;
          max-height: 180px;
          overflow-y: auto;
          display: none;
          position: absolute;
          z-index: 1050;
          width: 100%;
          left: 0;
          top: 100%;
        }

        .autocomplete-list.active {
          display: block;
        }

        .autocomplete-list li {
          padding: 0.45rem 0.65rem;
          cursor: pointer;
        }

        .autocomplete-list li:hover,
        .autocomplete-list li.selected {
          background: #f1f3f5;
        }
      </style>

      <div class="form-row">
        <div class="col-md-3">
          <div class="form-group h-100">
            <label for="foto">Capa</label>
            <div id="capa-preview-area" class="border rounded bg-light position-relative" style="min-height: 470px; cursor: pointer;" onclick="document.getElementById('foto').click();">
              <img id="preview-capa" src="" alt="Pré-visualização da capa" class="img-fluid w-100 h-100 d-none" style="min-height: 470px; object-fit: cover;">
              <button type="button" id="remove-capa-btn" class="btn btn-danger btn-sm rounded-circle d-none" style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; line-height:26px;" onclick="event.stopPropagation();">&times;</button>
              <div id="capa-placeholder" class="align-items-center justify-content-center text-muted" style="position:absolute; top:0; right:0; bottom:0; left:0; display:flex;">
                Clique para selecionar a capa
              </div>
            </div>
            <input type="file" id="foto" name="foto" accept=".jpg,.png,.webp" style="display:none;">
          </div>
        </div>
        <div class="col-md-9">
          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="nome">Nome *</label>
              <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="nome_original">Nome Original</label>
              <input type="text" class="form-control" id="nome_original" name="nome_original" value="<?= htmlspecialchars($_POST['nome_original'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="estudio">Estúdio</label>
              <input type="text" class="form-control" id="estudio" name="estudio" value="<?= htmlspecialchars($_POST['estudio'] ?? '') ?>" placeholder="Digite ou selecione um estúdio" autocomplete="off">
              <ul id="estudio-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="colecao-input">Coleção</label>
              <div class="colecao-container">
                <div id="colecao-tags" class="colecao-tags"></div>
                <input type="text" class="colecao-input" id="colecao-input" placeholder="Digite uma coleção..." autocomplete="off">
                <input type="hidden" id="colecao" name="colecao" value="<?= htmlspecialchars($_POST['colecao'] ?? '') ?>">
              </div>
              <ul id="colecao-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="tematica">Temática
                <i id="tematica-tooltip-trigger" class="fas fa-info-circle text-muted ml-1" title="Ver tipos e descrições" style="cursor: pointer;"></i>
              </label>
              <select class="form-control" id="tematica" name="tematica">
                <option value="">-- Selecione --</option>
                <option value="Cyberpunk" <?= (($_POST['tematica'] ?? '') === 'Cyberpunk') ? 'selected' : '' ?>>Cyberpunk</option>
                <option value="Fantasia" <?= (($_POST['tematica'] ?? '') === 'Fantasia') ? 'selected' : '' ?>>Fantasia</option>
                <option value="Faroeste" <?= (($_POST['tematica'] ?? '') === 'Faroeste') ? 'selected' : '' ?>>Faroeste</option>
                <option value="Horror Cósmico" <?= (($_POST['tematica'] ?? '') === 'Horror Cósmico') ? 'selected' : '' ?>>Horror Cósmico</option>
                <option value="Paranormal" <?= (($_POST['tematica'] ?? '') === 'Paranormal') ? 'selected' : '' ?>>Paranormal</option>
                <option value="Space Opera" <?= (($_POST['tematica'] ?? '') === 'Space Opera') ? 'selected' : '' ?>>Space Opera</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12 position-relative">
              <label for="outras_caracteristicas-input">Outras Características</label>
              <div class="outras_caracteristicas-container">
                <div id="outras_caracteristicas-tags" class="outras_caracteristicas-tags"></div>
                <input type="text" class="outras_caracteristicas-input" id="outras_caracteristicas-input" placeholder="Digite uma característica..." autocomplete="off">
                <input type="hidden" id="outras_caracteristicas" name="outras_caracteristicas" value="<?= htmlspecialchars($_POST['outras_caracteristicas'] ?? '') ?>">
              </div>
              <ul id="outras_caracteristicas-sugestoes" class="autocomplete-list"></ul>
            </div>
          </div>

        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="descricao_produto">Descrição</label>
          <textarea class="form-control" id="descricao_produto" name="descricao_produto" rows="2"><?= htmlspecialchars($_POST['descricao_produto'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-group">
        <label for="fotos">Imagens</label>
        <div class="custom-file">
          <input type="file" class="custom-file-input" id="fotos" name="fotos[]" accept=".jpg,.png,.webp" multiple>
          <label class="custom-file-label" for="fotos" data-browse="Escolher arquivo">Nenhum arquivo escolhido</label>
        </div>
        <small class="form-text text-muted">Formatos: JPG, PNG, WEBP (max 2MB por arquivo)</small>
        <div class="mt-2 d-none" id="preview-imagens-container"></div>
      </div>

      <hr>
      <h5>Dados Técnicos da Impressão</h5>

      <div class="form-row">
        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="gramas">Gramas (g)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="gramas" name="gramas" value="<?= htmlspecialchars($_POST['gramas'] ?? '') ?>">
            </div>
            <div class="form-group col-md-8">
              <label>Tempo de impressão (dias, horas, minutos)</label>
              <div class="form-row">
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_dias" name="tempo_dias" placeholder="Dias" value="<?= htmlspecialchars($_POST['tempo_dias'] ?? '') ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_horas" name="tempo_horas" placeholder="Horas" value="<?= htmlspecialchars($_POST['tempo_horas'] ?? '') ?>">
                </div>
                <div class="col-4">
                  <input type="number" min="0" class="form-control" id="tempo_minutos" name="tempo_minutos" placeholder="Min" value="<?= htmlspecialchars($_POST['tempo_minutos'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="unidades_produzidas">Unidades Produzidas</label>
              <input type="number" min="0" class="form-control" id="unidades_produzidas" name="unidades_produzidas" value="<?= htmlspecialchars($_POST['unidades_produzidas'] ?? '') ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="taxa_falha">Taxa de Falha (%)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="taxa_falha" name="taxa_falha" value="<?= htmlspecialchars($_POST['taxa_falha'] ?? '10') ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="markup_consumidor_final">Markup Consumidor Final</label>
              <select class="form-control" id="markup_consumidor_final" name="markup_consumidor_final">
                <option value="">-- Selecione --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <option value="<?= $i ?>" <?= (string)($_POST['markup_consumidor_final'] ?? '5') === (string)$i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary" id="btn-salvar-torre">Salvar</button>
      <a href="?pagina=torres" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<div class="modal fade" id="tematicaInfoModal" tabindex="-1" role="dialog" aria-labelledby="tematicaInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tematicaInfoModalLabel">Tipos de Temática</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="tematica-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var estudiosDisponiveis = <?= json_encode($estudios_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var colecoesDisponiveis = <?= json_encode($colecoes_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var tematicasDisponiveis = <?= json_encode($tematicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var outrasCaracteristicasDisponiveis = <?= json_encode($outras_caracteristicas_disponiveis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  var inputEstudio = document.getElementById('estudio');
  var estudioSugestoesList = document.getElementById('estudio-sugestoes');
  var inputColecao = document.getElementById('colecao-input');
  var colecaoTagsContainer = document.getElementById('colecao-tags');
  var colecaoHiddenInput = document.getElementById('colecao');
  var colecaoSugestoesList = document.getElementById('colecao-sugestoes');
  var selectTematica = document.getElementById('tematica');
  var tooltipTematica = document.getElementById('tematica-tooltip-trigger');
  var tematicaModalBody = document.getElementById('tematica-modal-body');

  var outrasCaracteristicasInput = document.getElementById('outras_caracteristicas-input');
  var outrasCaracteristicasTagsContainer = document.getElementById('outras_caracteristicas-tags');
  var outrasCaracteristicasHiddenInput = document.getElementById('outras_caracteristicas');
  var outrasCaracteristicasSugestoesList = document.getElementById('outras_caracteristicas-sugestoes');

  var inputFoto = document.getElementById('foto');
  var previewImagem = document.getElementById('preview-capa');
  var capaPlaceholder = document.getElementById('capa-placeholder');
  var removeCapaBtn = document.getElementById('remove-capa-btn');
  var inputFotos = document.getElementById('fotos');
  var formAdicionarTorre = document.getElementById('form-adicionar-torre');
  var btnSalvarTorre = document.getElementById('btn-salvar-torre');
  var labelFotos = document.querySelector('label.custom-file-label[for="fotos"]');
  var previewImagensContainer = document.getElementById('preview-imagens-container');
  var fotoExistenteInput = document.getElementById('foto_existente');
  var imagensExistentesInput = document.getElementById('imagens_existentes');

  var estudiosNomesDisponiveis = Array.isArray(estudiosDisponiveis)
    ? Array.from(new Set(estudiosDisponiveis
      .map(function (itemEstudio) {
        return itemEstudio && typeof itemEstudio.nome === 'string' ? itemEstudio.nome.trim() : '';
      })
      .filter(function (nomeEstudio) {
        return nomeEstudio !== '';
      })))
    : [];

  var colecoesNomesDisponiveis = Array.isArray(colecoesDisponiveis)
    ? Array.from(new Set(colecoesDisponiveis
      .map(function (itemColecao) {
        return itemColecao && typeof itemColecao.nome === 'string' ? itemColecao.nome.trim() : '';
      })
      .filter(function (nomeColecao) {
        return nomeColecao !== '';
      })))
    : [];

  var initAutocompleteField = function (inputElement, sugestoesList, options) {
    if (!inputElement || !sugestoesList) {
      return;
    }

    options = options || {};
    var minChars = Number.isFinite(Number(options.minChars)) ? Number(options.minChars) : 2;
    var showOnFocus = options.showOnFocus === true;
    var indiceSelecionado = -1;

    var fecharSugestoes = function () {
      sugestoesList.classList.remove('active');
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;
    };

    var renderizarSugestoes = function (termo) {
      if (!Array.isArray(options.localSuggestions)) {
        return;
      }

      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = options.localSuggestions
        .filter(function (itemSugestao) {
          if (typeof itemSugestao !== 'string') {
            return false;
          }

          if (termoNormalizado === '') {
            return true;
          }

          return itemSugestao.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 10);

      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;

      if (!dadosFiltrados.length) {
        fecharSugestoes();
        return;
      }

      dadosFiltrados.forEach(function (sugestao, index) {
        var li = document.createElement('li');
        li.textContent = sugestao;
        li.dataset.index = String(index);
        li.addEventListener('click', function () {
          inputElement.value = sugestao;
          fecharSugestoes();
          inputElement.focus();
        });
        sugestoesList.appendChild(li);
      });

      sugestoesList.classList.add('active');
    };

    var atualizarSelecao = function (listaItens) {
      listaItens.forEach(function (li, index) {
        li.classList.toggle('selected', index === indiceSelecionado);
      });
    };

    inputElement.addEventListener('input', function () {
      var termo = this.value.trim();

      if (termo.length < minChars) {
        if (showOnFocus && termo.length === 0) {
          renderizarSugestoes('');
          return;
        }
        fecharSugestoes();
        return;
      }

      renderizarSugestoes(termo);
    });

    inputElement.addEventListener('focus', function () {
      if (!showOnFocus) {
        return;
      }
      renderizarSugestoes(this.value.trim());
    });

    inputElement.addEventListener('keydown', function (e) {
      var listaItens = sugestoesList.querySelectorAll('li');

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceSelecionado = Math.min(indiceSelecionado + 1, listaItens.length - 1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceSelecionado = Math.max(indiceSelecionado - 1, -1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'Enter') {
        if (indiceSelecionado >= 0 && listaItens[indiceSelecionado]) {
          e.preventDefault();
          inputElement.value = listaItens[indiceSelecionado].textContent || '';
          fecharSugestoes();
        }
      } else if (e.key === 'Escape') {
        fecharSugestoes();
      }
    });

    document.addEventListener('click', function (e) {
      if (e.target !== inputElement && !sugestoesList.contains(e.target)) {
        fecharSugestoes();
      }
    });
  };

  var initTagsField = function (inputElement, tagsContainer, hiddenInput, sugestoesList, fieldName, options) {
    if (!inputElement || !tagsContainer || !hiddenInput || !sugestoesList) {
      return;
    }

    options = options || {};
    var minChars = Number.isFinite(Number(options.minChars)) ? Number(options.minChars) : 2;
    var showOnFocus = options.showOnFocus === true;

    var itens = [];
    var indiceSelecionado = -1;
    var ignorarBlurUmaVez = false;

    var valorInicial = (hiddenInput.value || '').trim();
    if (valorInicial !== '') {
      itens = valorInicial.split(',').map(function (item) { return item.trim(); }).filter(function (item) { return item.length > 0; });
    }

    var atualizarDisplay = function () {
      tagsContainer.innerHTML = '';

      itens.forEach(function (item, index) {
        var tag = document.createElement('div');
        tag.className = fieldName + '-tag';
        tag.innerHTML = item + '<small>×</small>';

        var botaoRemover = tag.querySelector('small');
        if (botaoRemover) {
          botaoRemover.addEventListener('click', function (e) {
            e.stopPropagation();
            itens.splice(index, 1);
            atualizarDisplay();
          });
        }

        tagsContainer.appendChild(tag);
      });

      hiddenInput.value = itens.join(',');
    };

    var fecharSugestoes = function () {
      sugestoesList.classList.remove('active');
      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;
    };

    var adicionarItem = function (item) {
      var valor = (item || '').trim();
      if (valor === '') {
        return;
      }

      var existe = itens.some(function (itemAtual) {
        return itemAtual.toLocaleLowerCase() === valor.toLocaleLowerCase();
      });

      if (!existe) {
        itens.push(valor);
        atualizarDisplay();
      }

      inputElement.value = '';
      fecharSugestoes();
      inputElement.focus();
    };

    var atualizarSelecao = function (listaItens) {
      listaItens.forEach(function (li, index) {
        li.classList.toggle('selected', index === indiceSelecionado);
      });
    };

    var renderizarSugestoesLocais = function (termo) {
      if (!Array.isArray(options.localSuggestions)) {
        return false;
      }

      var termoNormalizado = (termo || '').toLocaleLowerCase();
      var dadosFiltrados = options.localSuggestions
        .filter(function (itemSugestao) {
          if (typeof itemSugestao !== 'string') {
            return false;
          }

          if (termoNormalizado === '') {
            return true;
          }

          return itemSugestao.toLocaleLowerCase().indexOf(termoNormalizado) !== -1;
        })
        .slice(0, 10);

      sugestoesList.innerHTML = '';
      indiceSelecionado = -1;

      if (!dadosFiltrados.length) {
        fecharSugestoes();
        return true;
      }

      dadosFiltrados.forEach(function (sugestao, index) {
        var li = document.createElement('li');
        li.textContent = sugestao;
        li.dataset.index = String(index);
        li.addEventListener('click', function () {
          adicionarItem(sugestao);
        });
        sugestoesList.appendChild(li);
      });

      sugestoesList.classList.add('active');
      return true;
    };

    inputElement.addEventListener('input', function () {
      var termo = this.value.trim();

      if (termo.length < minChars) {
        fecharSugestoes();
        if (showOnFocus && termo.length === 0) {
          renderizarSugestoesLocais('');
        }
        return;
      }

      renderizarSugestoesLocais(termo);
    });

    inputElement.addEventListener('keydown', function (e) {
      var listaItens = sugestoesList.querySelectorAll('li');

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceSelecionado = Math.min(indiceSelecionado + 1, listaItens.length - 1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceSelecionado = Math.max(indiceSelecionado - 1, -1);
        atualizarSelecao(listaItens);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indiceSelecionado >= 0 && listaItens[indiceSelecionado]) {
          adicionarItem(listaItens[indiceSelecionado].textContent || '');
        } else {
          adicionarItem(this.value);
        }
      } else if (e.key === 'Escape') {
        fecharSugestoes();
      }
    });

    sugestoesList.addEventListener('mousedown', function () {
      ignorarBlurUmaVez = true;
    });

    inputElement.addEventListener('blur', function () {
      var valorPendente = this.value.trim();

      if (ignorarBlurUmaVez) {
        ignorarBlurUmaVez = false;
        return;
      }

      if (valorPendente !== '') {
        adicionarItem(valorPendente);
      } else {
        fecharSugestoes();
      }
    });

    inputElement.addEventListener('focus', function () {
      if (!showOnFocus) {
        return;
      }
      renderizarSugestoesLocais(this.value.trim());
    });

    document.addEventListener('click', function (e) {
      if (e.target !== inputElement && !sugestoesList.contains(e.target)) {
        fecharSugestoes();
      }
    });

    tagsContainer.addEventListener('click', function () {
      inputElement.focus();
    });

    atualizarDisplay();
  };

  initAutocompleteField(inputEstudio, estudioSugestoesList, {
    localSuggestions: estudiosNomesDisponiveis,
    minChars: 0,
    showOnFocus: true
  });

  initTagsField(
    inputColecao,
    colecaoTagsContainer,
    colecaoHiddenInput,
    colecaoSugestoesList,
    'colecao',
    {
      localSuggestions: colecoesNomesDisponiveis,
      minChars: 0,
      showOnFocus: true
    }
  );

  initTagsField(
    outrasCaracteristicasInput,
    outrasCaracteristicasTagsContainer,
    outrasCaracteristicasHiddenInput,
    outrasCaracteristicasSugestoesList,
    'outras_caracteristicas',
    {
      localSuggestions: outrasCaracteristicasDisponiveis,
      minChars: 0,
      showOnFocus: true
    }
  );

  if (selectTematica && tooltipTematica) {
    var descricoesTematica = {
      'Fantasia': {
        sistema: 'Dungeons & Dragons',
        foco: 'Aventura e Poder'
      },
      'Horror Cósmico': {
        sistema: 'Call of Cthulhu',
        foco: 'Mistério e Fragilidade Humana'
      },
      'Paranormal': {
        sistema: 'Ordem Paranormal',
        foco: 'Investigação e Combate ao Oculto'
      },
      'Cyberpunk': {
        sistema: 'Cyberpunk 2020/RED',
        foco: 'Rebeldia e Tecnologia'
      },
      'Space Opera': {
        sistema: 'Star Wars / Starfinder',
        foco: 'Exploração Espacial e Política Galáctica'
      },
      'Faroeste': {
        sistema: 'Deadlands',
        foco: 'Duelos e Exploração de Fronteira'
      }
    };

    var opcoesTematica = Array.from(selectTematica.options)
      .filter(function (opcao) { return opcao.value !== ''; })
      .map(function (opcao) { return opcao.text; });

    var linhasTematica = opcoesTematica.map(function (tematicaNome) {
      var dadosTematica = descricoesTematica[tematicaNome] || { sistema: '', foco: '' };
      return '<tr>'
        + '<td><strong>' + tematicaNome + '</strong></td>'
        + '<td>' + dadosTematica.sistema + '</td>'
        + '<td>' + dadosTematica.foco + '</td>'
        + '</tr>';
    }).join('');

    var tabelaTematicaHtml = ''
      + '<div class="table-responsive">'
      + '<table class="table table-sm table-bordered mb-0">'
      + '<thead><tr><th>Temática</th><th>Exemplo de Sistema</th><th>Foco Principal</th></tr></thead>'
      + '<tbody>' + linhasTematica + '</tbody>'
      + '</table>'
      + '</div>';

    if (tematicaModalBody) {
      tematicaModalBody.innerHTML = tabelaTematicaHtml;
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(tooltipTematica).off('click.tematicaModal').on('click.tematicaModal', function () {
        window.jQuery('#tematicaInfoModal').modal('show');
      });
    }
  }

  if (inputFoto && previewImagem && capaPlaceholder && removeCapaBtn) {
    var renderizarCapaExistente = function () {
      var caminhoCapaExistente = fotoExistenteInput && typeof fotoExistenteInput.value === 'string'
        ? fotoExistenteInput.value.trim()
        : '';

      if (caminhoCapaExistente !== '') {
        previewImagem.src = caminhoCapaExistente;
        previewImagem.classList.remove('d-none');
        capaPlaceholder.classList.add('d-none');
        capaPlaceholder.style.display = 'none';
        removeCapaBtn.classList.remove('d-none');
      } else {
        previewImagem.src = '';
        previewImagem.classList.add('d-none');
        capaPlaceholder.classList.remove('d-none');
        capaPlaceholder.style.display = 'flex';
        removeCapaBtn.classList.add('d-none');
      }
    };

    renderizarCapaExistente();

    inputFoto.addEventListener('change', function () {
      var arquivo = this.files && this.files[0] ? this.files[0] : null;

      if (!arquivo) {
        renderizarCapaExistente();
        return;
      }

      if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
        renderizarCapaExistente();
        return;
      }

      var leitor = new FileReader();
      leitor.onload = function (evento) {
        previewImagem.src = evento.target.result;
        previewImagem.classList.remove('d-none');
        capaPlaceholder.classList.add('d-none');
        capaPlaceholder.style.display = 'none';
        removeCapaBtn.classList.remove('d-none');
      };
      leitor.readAsDataURL(arquivo);
    });

    removeCapaBtn.addEventListener('click', function () {
      if (inputFoto.value) {
        inputFoto.value = '';
        renderizarCapaExistente();
        return;
      }

      if (fotoExistenteInput) {
        fotoExistenteInput.value = '';
      }

      renderizarCapaExistente();
    });
  }

  if (inputFotos && labelFotos && previewImagensContainer) {
    var arquivosSelecionados = [];
    var imagensPersistidas = [];

    if (imagensExistentesInput && typeof imagensExistentesInput.value === 'string' && imagensExistentesInput.value.trim() !== '') {
      try {
        var imagensPersistidasRaw = JSON.parse(imagensExistentesInput.value);
        if (Array.isArray(imagensPersistidasRaw)) {
          imagensPersistidas = imagensPersistidasRaw
            .filter(function (item) { return typeof item === 'string' && item.trim() !== ''; })
            .map(function (item) { return item.trim(); });
        }
      } catch (e) {
        imagensPersistidas = [];
      }
    }

    var sincronizarImagensPersistidas = function () {
      if (!imagensExistentesInput) {
        return;
      }
      imagensExistentesInput.value = JSON.stringify(imagensPersistidas);
    };

    var sincronizarInputArquivos = function () {
      var dataTransfer = new DataTransfer();
      arquivosSelecionados.forEach(function (arquivo) {
        dataTransfer.items.add(arquivo);
      });
      inputFotos.files = dataTransfer.files;
    };

    var atualizarListaArquivos = function () {
      var totalArquivos = imagensPersistidas.length + arquivosSelecionados.length;

      if (!totalArquivos) {
        labelFotos.textContent = 'Nenhum arquivo escolhido';
        previewImagensContainer.innerHTML = '';
        previewImagensContainer.classList.add('d-none');
        return;
      }

      labelFotos.textContent = totalArquivos === 1
        ? '1 arquivo selecionado'
        : totalArquivos + ' arquivos selecionados';

      previewImagensContainer.innerHTML = '';
      previewImagensContainer.classList.remove('d-none');

      imagensPersistidas.forEach(function (caminhoImagem, indicePersistido) {
        var wrapperPersistido = document.createElement('div');
        wrapperPersistido.style.position = 'relative';
        wrapperPersistido.style.display = 'inline-block';
        wrapperPersistido.className = 'mr-2 mb-2';

        var imagemPersistida = document.createElement('img');
        imagemPersistida.src = caminhoImagem;
        imagemPersistida.alt = 'Pré-visualização';
        imagemPersistida.className = 'img-thumbnail';
        imagemPersistida.style.width = '90px';
        imagemPersistida.style.height = '90px';
        imagemPersistida.style.objectFit = 'cover';

        var botaoRemoverPersistido = document.createElement('button');
        botaoRemoverPersistido.type = 'button';
        botaoRemoverPersistido.innerHTML = '&times;';
        botaoRemoverPersistido.setAttribute('aria-label', 'Remover imagem');
        botaoRemoverPersistido.style.position = 'absolute';
        botaoRemoverPersistido.style.top = '2px';
        botaoRemoverPersistido.style.right = '2px';
        botaoRemoverPersistido.style.width = '22px';
        botaoRemoverPersistido.style.height = '22px';
        botaoRemoverPersistido.style.border = 'none';
        botaoRemoverPersistido.style.borderRadius = '50%';
        botaoRemoverPersistido.style.background = 'rgba(220,53,69,0.9)';
        botaoRemoverPersistido.style.color = '#fff';
        botaoRemoverPersistido.style.fontSize = '16px';
        botaoRemoverPersistido.style.lineHeight = '20px';
        botaoRemoverPersistido.style.cursor = 'pointer';
        botaoRemoverPersistido.style.padding = '0';

        botaoRemoverPersistido.addEventListener('click', function () {
          imagensPersistidas.splice(indicePersistido, 1);
          sincronizarImagensPersistidas();
          atualizarListaArquivos();
        });

        wrapperPersistido.appendChild(imagemPersistida);
        wrapperPersistido.appendChild(botaoRemoverPersistido);
        previewImagensContainer.appendChild(wrapperPersistido);
      });

      arquivosSelecionados.forEach(function (arquivo, indice) {
        if (!arquivo.type || arquivo.type.indexOf('image/') !== 0) {
          return;
        }

        var leitor = new FileReader();
        leitor.onload = function (evento) {
          var wrapper = document.createElement('div');
          wrapper.style.position = 'relative';
          wrapper.style.display = 'inline-block';
          wrapper.className = 'mr-2 mb-2';

          var imagem = document.createElement('img');
          imagem.src = evento.target.result;
          imagem.alt = 'Pré-visualização';
          imagem.className = 'img-thumbnail';
          imagem.style.width = '90px';
          imagem.style.height = '90px';
          imagem.style.objectFit = 'cover';

          var botaoRemover = document.createElement('button');
          botaoRemover.type = 'button';
          botaoRemover.innerHTML = '&times;';
          botaoRemover.setAttribute('aria-label', 'Remover imagem');
          botaoRemover.style.position = 'absolute';
          botaoRemover.style.top = '2px';
          botaoRemover.style.right = '2px';
          botaoRemover.style.width = '22px';
          botaoRemover.style.height = '22px';
          botaoRemover.style.border = 'none';
          botaoRemover.style.borderRadius = '50%';
          botaoRemover.style.background = 'rgba(220,53,69,0.9)';
          botaoRemover.style.color = '#fff';
          botaoRemover.style.fontSize = '16px';
          botaoRemover.style.lineHeight = '20px';
          botaoRemover.style.cursor = 'pointer';
          botaoRemover.style.padding = '0';

          botaoRemover.addEventListener('click', function () {
            arquivosSelecionados.splice(indice, 1);
            sincronizarInputArquivos();
            atualizarListaArquivos();
          });

          wrapper.appendChild(imagem);
          wrapper.appendChild(botaoRemover);
          previewImagensContainer.appendChild(wrapper);
        };
        leitor.readAsDataURL(arquivo);
      });
    };

    inputFotos.addEventListener('change', function () {
      var novosArquivos = this.files ? Array.from(this.files) : [];

      novosArquivos.forEach(function (novoArquivo) {
        var jaExiste = arquivosSelecionados.some(function (arquivoAtual) {
          return arquivoAtual.name === novoArquivo.name
            && arquivoAtual.size === novoArquivo.size
            && arquivoAtual.lastModified === novoArquivo.lastModified;
        });

        if (!jaExiste) {
          arquivosSelecionados.push(novoArquivo);
        }
      });

      sincronizarInputArquivos();
      atualizarListaArquivos();
    });

    sincronizarImagensPersistidas();
    atualizarListaArquivos();
  }

  if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.summernote === 'function') {
    window.jQuery('#descricao_produto').summernote({
      placeholder: 'Place some text here',
      height: 180
    });
  }

  if (formAdicionarTorre && btnSalvarTorre) {
    formAdicionarTorre.addEventListener('submit', function () {
      btnSalvarTorre.disabled = true;
      btnSalvarTorre.textContent = 'Salvando...';
    });
  }
});
</script>
