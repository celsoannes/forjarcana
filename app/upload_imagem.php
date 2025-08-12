<?php
/**
 * Função para upload e redimensionamento de imagem, reutilizável para qualquer entidade.
 * 
 * @param array $file $_FILES['campo']
 * @param string $uuid Identificador único (pasta destino)
 * @param string $baseDir Pasta base (ex: 'usuarios', 'produtos', 'impressoras')
 * @param array $sizes Tamanhos ['thumb'=>[64,64], ...]
 * @return string|false Caminho da imagem principal ou false em erro
 */
function uploadImagem($file, $uuid, $baseDir, $sizes = null, $prefix = 'foto', $apagarAntigas = false) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $tmp = $file['tmp_name'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($tmp);

    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $mimes_permitidos = [
        'image/jpeg',
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
        $padroes = glob("$dir/{$prefix}_*_*.png");
        foreach ($padroes as $arquivo) {
            unlink($arquivo);
        }
    }

    // Tamanhos padrão se não informado
    if (!$sizes) {
        $sizes = [
            'thumb'   => [64, 64],
            'pequena' => [128, 128],
            'media'   => [256, 256],
            'grande'  => [512, 512]
        ];
    }

    // Redimensiona e salva
    foreach ($sizes as $tipo => $dim) {
        $destino = "$dir/{$prefix}_{$hash}_{$tipo}.png";
        redimensionaImagem($tmp, $destino, $dim[0], $dim[1]);
    }

    // Retorna caminho relativo da imagem principal
    return "uploads/$baseDir/$uuid/{$prefix}_{$hash}_media.png";
}

/**
 * Redimensiona imagem para PNG
 */
function redimensionaImagem($origem, $destino, $largura, $altura) {
    $img = imagecreatefromstring(file_get_contents($origem));
    $w = imagesx($img);
    $h = imagesy($img);
    $nova = imagecreatetruecolor($largura, $altura);
    imagealphablending($nova, false);
    imagesavealpha($nova, true);
    imagecopyresampled($nova, $img, 0, 0, 0, 0, $largura, $altura, $w, $h);
    imagepng($nova, $destino);
    imagedestroy($img);
    imagedestroy($nova);
}