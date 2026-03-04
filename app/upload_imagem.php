<?php
/**
 * Função para upload e redimensionamento de imagem, reutilizável para qualquer entidade.
 * 
 * @param array $file $_FILES['campo']
 * @param string $uuid Identificador único (pasta destino)
 * @param string $baseDir Pasta base (ex: 'usuarios', 'produtos', 'impressoras')
 * @param array $sizes Tamanhos ['thumbnail'=>[150,150,'crop'], 'media'=>[300,300,'proporcional'], ...]
 * @return string|false Caminho da imagem principal ou false em erro
 */
function uploadImagem($file, $uuid, $baseDir, $sizes = null, $prefix = 'foto', $apagarAntigas = false) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $tamanhoMaximoBytes = 5 * 1024 * 1024;
    $tamanhoArquivo = isset($file['size']) ? (int) $file['size'] : 0;
    if ($tamanhoArquivo <= 0 || $tamanhoArquivo > $tamanhoMaximoBytes) {
        error_log("Upload falhou: arquivo excede 5MB ou tamanho inválido ({$tamanhoArquivo} bytes)");
        return false;
    }

    $tmp = $file['tmp_name'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($tmp);

    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $mimes_permitidos = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    if (!in_array($ext, $extensoes_permitidas) || !in_array($mime, $mimes_permitidos)) {
        error_log("Upload falhou: extensão ou mime inválido ($ext, $mime)");
        return false;
    }

    // Pasta destino
    $dir = __DIR__ . "/../uploads/$baseDir/$uuid";
    if (!is_dir($dir)) {
        error_log("Criando diretório: $dir");
        mkdir($dir, 0755, true);
    }

    if (!file_exists($tmp)) {
        error_log("Arquivo temporário não existe: $tmp");
        return false;
    }

    // Gera hash único para este upload
    $hash = substr(sha1_file($tmp) . uniqid(), 0, 12);

    // Só apaga imagens antigas se explicitamente solicitado
    if ($apagarAntigas) {
        $padroes = array_merge(
            glob("$dir/{$prefix}_*_*.png") ?: [],
            glob("$dir/{$prefix}_*_*.webp") ?: []
        );
        foreach ($padroes as $arquivo) {
            unlink($arquivo);
        }
    }

    // Tamanhos padrão se não informado
    if (!$sizes) {
        $sizes = [
            'thumbnail' => [150, 150, 'crop'],
            'pequena'   => [300, 300, 'proporcional'],
            'media'     => [300, 300, 'proporcional'],
            'grande'    => [1024, 1024, 'proporcional']
        ];
    }

    // Unifica aliases para evitar gerar arquivos duplicados (thumb e thumbnail)
    $sizesNormalizados = [];
    foreach ($sizes as $tipo => $dim) {
        $chave = strtolower(trim((string) $tipo));
        if ($chave === 'thumb') {
            $chave = 'thumbnail';
        }
        $sizesNormalizados[$chave] = $dim;
    }

    // Redimensiona e salva
    foreach ($sizesNormalizados as $tipo => $dim) {
        $largura = isset($dim[0]) ? (int) $dim[0] : 0;
        $altura = isset($dim[1]) ? (int) $dim[1] : 0;
        $modo = isset($dim[2]) ? (string) $dim[2] : 'proporcional';

        if ($largura <= 0 || $altura <= 0) {
            error_log("Upload falhou: tamanho inválido para {$tipo}");
            continue;
        }

        $destino = "$dir/{$prefix}_{$hash}_{$tipo}.webp";
        if (!redimensionaImagem($tmp, $destino, $largura, $altura, $modo)) {
            error_log("Upload falhou: não foi possível gerar a versão {$tipo}.");
        }
    }

    // Retorna caminho relativo da imagem principal
    return "uploads/$baseDir/$uuid/{$prefix}_{$hash}_media.webp";
}

/**
 * Redimensiona imagem para WebP
 */
function redimensionaImagem($origem, $destino, $largura, $altura, $modo = 'proporcional') {
    $conteudo = @file_get_contents($origem);
    if ($conteudo === false) {
        return false;
    }

    $img = @imagecreatefromstring($conteudo);
    if (!$img) {
        return false;
    }

    $w = imagesx($img);
    $h = imagesy($img);

    $modo = strtolower(trim((string) $modo));
    if ($modo === 'crop') {
        $nova = imagecreatetruecolor($largura, $altura);
        imagealphablending($nova, false);
        imagesavealpha($nova, true);

        $proporcaoOrigem = $w / max($h, 1);
        $proporcaoDestino = $largura / max($altura, 1);

        if ($proporcaoOrigem > $proporcaoDestino) {
            $srcH = $h;
            $srcW = (int) round($h * $proporcaoDestino);
            $srcX = (int) round(($w - $srcW) / 2);
            $srcY = 0;
        } else {
            $srcW = $w;
            $srcH = (int) round($w / $proporcaoDestino);
            $srcX = 0;
            $srcY = (int) round(($h - $srcH) / 2);
        }

        imagecopyresampled($nova, $img, 0, 0, $srcX, $srcY, $largura, $altura, $srcW, $srcH);
    } else {
        $escala = min($largura / max($w, 1), $altura / max($h, 1), 1);
        $novaLargura = max(1, (int) round($w * $escala));
        $novaAltura = max(1, (int) round($h * $escala));

        $nova = imagecreatetruecolor($novaLargura, $novaAltura);
        imagealphablending($nova, false);
        imagesavealpha($nova, true);
        imagecopyresampled($nova, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $w, $h);
    }

    if (!function_exists('imagewebp')) {
        imagedestroy($img);
        imagedestroy($nova);
        return false;
    }

    $salvo = imagewebp($nova, $destino, 85);

    imagedestroy($img);
    imagedestroy($nova);

    return $salvo;
}