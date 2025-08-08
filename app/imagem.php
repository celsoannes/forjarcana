<?php
function salvarImagemUsuario($arquivo, $usuario_uuid, $destino_base) {
    if ($arquivo['error'] !== UPLOAD_ERR_OK) return '';

    $tmp = $arquivo['tmp_name'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $imagem_nome_base = uniqid('insumo_');
    $imagem_grande = $imagem_nome_base . '_g.png';
    $imagem_media = $imagem_nome_base . '_m.png';
    $imagem_thumb = $imagem_nome_base . '_t.png';

    $user_dir = $destino_base . '/' . $usuario_uuid;
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    // Suporte a mais formatos
    if ($extensao === 'png') {
        $img = imagecreatefrompng($tmp);
    } elseif ($extensao === 'jpg' || $extensao === 'jpeg') {
        $img = imagecreatefromjpeg($tmp);
    } elseif ($extensao === 'gif') {
        $img = imagecreatefromgif($tmp);
    } elseif ($extensao === 'webp' && function_exists('imagecreatefromwebp')) {
        $img = imagecreatefromwebp($tmp);
    } else {
        return '';
    }

    $largura = imagesx($img);
    $altura = imagesy($img);

    // Imagem grande (máx 1600x1600)
    $max_dim_grande = 1600;
    $ratio_grande = min($max_dim_grande / $largura, $max_dim_grande / $altura, 1);
    $grande_largura = (int)($largura * $ratio_grande);
    $grande_altura = (int)($altura * $ratio_grande);
    $grande = imagecreatetruecolor($grande_largura, $grande_altura);
    imagealphablending($grande, false);
    imagesavealpha($grande, true);
    $transp_grande = imagecolorallocatealpha($grande, 0, 0, 0, 127);
    imagefill($grande, 0, 0, $transp_grande);
    imagecopyresampled($grande, $img, 0, 0, 0, 0, $grande_largura, $grande_altura, $largura, $altura);
    imagepng($grande, $user_dir . '/' . $imagem_grande, 7);

    // Imagem média (máx 800x800)
    $max_dim = 800;
    $ratio = min($max_dim / $largura, $max_dim / $altura, 1);
    $nova_largura = (int)($largura * $ratio);
    $nova_altura = (int)($altura * $ratio);
    $media = imagecreatetruecolor($nova_largura, $nova_altura);
    imagealphablending($media, false);
    imagesavealpha($media, true);
    $transp = imagecolorallocatealpha($media, 0, 0, 0, 127);
    imagefill($media, 0, 0, $transp);
    imagecopyresampled($media, $img, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura, $altura);
    imagepng($media, $user_dir . '/' . $imagem_media, 7);

    // Thumb (200x200)
    $thumb_dim = 200;
    $ratio_thumb = min($thumb_dim / $largura, $thumb_dim / $altura, 1);
    $thumb_largura = (int)($largura * $ratio_thumb);
    $thumb_altura = (int)($altura * $ratio_thumb);
    $thumb = imagecreatetruecolor($thumb_largura, $thumb_altura);
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    $transp_thumb = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
    imagefill($thumb, 0, 0, $transp_thumb);
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumb_largura, $thumb_altura, $largura, $altura);
    imagepng($thumb, $user_dir . '/' . $imagem_thumb, 7);

    imagedestroy($img);
    imagedestroy($grande);
    imagedestroy($media);
    imagedestroy($thumb);

    // Retorne o caminho da imagem média para salvar no banco (ou ajuste conforme sua necessidade)
    return $usuario_uuid . '/' . $imagem_media;
}