<?php
function salvarImagemUsuario($arquivo, $usuario_uuid, $destino_base) {
    if ($arquivo['error'] !== UPLOAD_ERR_OK) return '';

    $tmp = $arquivo['tmp_name'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $imagem_nome_base = uniqid('insumo_');
    $imagem_media = $imagem_nome_base . '_m.png';
    $imagem_thumb = $imagem_nome_base . '_t.png';

    $user_dir = $destino_base . '/' . $usuario_uuid;
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    if ($extensao === 'png') {
        $img = imagecreatefrompng($tmp);
    } elseif ($extensao === 'jpg' || $extensao === 'jpeg') {
        $img = imagecreatefromjpeg($tmp);
    } elseif ($extensao === 'gif') {
        $img = imagecreatefromgif($tmp);
    } else {
        return '';
    }

    // Média
    $largura = imagesx($img);
    $altura = imagesy($img);
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

    // Thumb
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
    imagedestroy($media);
    imagedestroy($thumb);

    return $usuario_uuid . '/' . $imagem_media;
}