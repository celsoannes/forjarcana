<?php
// filepath: /var/www/html/forjarcana/app/auth.php
function lembrar_me_cookie_nome() {
    return 'forjarcana_remember';
}

function lembrar_me_cookie_limpar() {
    setcookie(lembrar_me_cookie_nome(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[lembrar_me_cookie_nome()]);
}

function lembrar_me_cookie_definir($valor, $expiracaoUnix) {
    setcookie(lembrar_me_cookie_nome(), $valor, [
        'expires' => $expiracaoUnix,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function garantir_tabela_lembrar_me(PDO $pdo) {
    static $garantida = false;
    if ($garantida) {
        return true;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNSIGNED NOT NULL,
            selector CHAR(24) NOT NULL UNIQUE,
            token_hash CHAR(64) NOT NULL,
            expira_em DATETIME NOT NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_remember_usuario_id (usuario_id),
            INDEX idx_remember_expira_em (expira_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        return false;
    }

    $garantida = true;
    return true;
}

function definir_sessao_usuario(array $usuario) {
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_sobrenome'] = $usuario['sobrenome'];
    $_SESSION['usuario_cargo'] = $usuario['cargo'];
    $_SESSION['usuario_uuid'] = $usuario['uuid'];
    $_SESSION['usuario_celular'] = $usuario['celular'];
    $_SESSION['usuario_cpf'] = $usuario['cpf'];

    if (!empty($usuario['foto'])) {
        $foto = $usuario['foto'];
        $thumb = str_replace('_media.png', '_thumb.png', $foto);
        $thumbPath = __DIR__ . '/../' . $thumb;
        if (file_exists($thumbPath)) {
            $_SESSION['usuario_foto'] = $thumb;
        } else {
            $_SESSION['usuario_foto'] = $foto;
        }
    } else {
        $_SESSION['usuario_foto'] = '';
    }
}

function criar_login_lembrado(PDO $pdo, $usuarioId, $dias = 30) {
    if (!garantir_tabela_lembrar_me($pdo)) {
        return;
    }

    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiracaoUnix = time() + ($dias * 86400);
    $expiraEm = date('Y-m-d H:i:s', $expiracaoUnix);

    $stmt = $pdo->prepare("INSERT INTO remember_tokens (usuario_id, selector, token_hash, expira_em) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuarioId, $selector, $tokenHash, $expiraEm]);

    $cookieValor = $selector . ':' . $validator;
    lembrar_me_cookie_definir($cookieValor, $expiracaoUnix);
}

function revogar_login_lembrado_atual(PDO $pdo) {
    if (!garantir_tabela_lembrar_me($pdo)) {
        lembrar_me_cookie_limpar();
        return;
    }

    $cookie = $_COOKIE[lembrar_me_cookie_nome()] ?? '';
    if ($cookie && strpos($cookie, ':') !== false) {
        [$selector] = explode(':', $cookie, 2);
        if ($selector) {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->execute([$selector]);
        }
    }

    lembrar_me_cookie_limpar();
}

function tentar_login_por_cookie(PDO $pdo) {
    $cookie = $_COOKIE[lembrar_me_cookie_nome()] ?? '';
    if (!$cookie || strpos($cookie, ':') === false) {
        return false;
    }

    [$selector, $validator] = explode(':', $cookie, 2);

    if (!$selector || !$validator || !ctype_xdigit($selector) || !ctype_xdigit($validator)) {
        lembrar_me_cookie_limpar();
        return false;
    }

    if (!garantir_tabela_lembrar_me($pdo)) {
        lembrar_me_cookie_limpar();
        return false;
    }

    $stmt = $pdo->prepare("SELECT rt.id AS remember_id, rt.usuario_id, rt.token_hash, rt.expira_em,
                                  u.id, u.nome, u.sobrenome, u.cargo, u.data_expiracao, u.uuid, u.foto, u.celular, u.cpf
                           FROM remember_tokens rt
                           INNER JOIN usuarios u ON u.id = rt.usuario_id
                           WHERE rt.selector = ?");
    $stmt->execute([$selector]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        lembrar_me_cookie_limpar();
        return false;
    }

    if (strtotime($registro['expira_em']) < time()) {
        $del = $pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
        $del->execute([$registro['remember_id']]);
        lembrar_me_cookie_limpar();
        return false;
    }

    $hashAtual = hash('sha256', $validator);
    if (!hash_equals($registro['token_hash'], $hashAtual)) {
        $del = $pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
        $del->execute([$registro['remember_id']]);
        lembrar_me_cookie_limpar();
        return false;
    }

    if (!empty($registro['data_expiracao']) && strtotime($registro['data_expiracao']) < time()) {
        $del = $pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
        $del->execute([$registro['remember_id']]);
        lembrar_me_cookie_limpar();
        return false;
    }

    definir_sessao_usuario($registro);

    $del = $pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
    $del->execute([$registro['remember_id']]);
    criar_login_lembrado($pdo, $registro['usuario_id']);

    return true;
}

function usuario_logado() {
    if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
        return true;
    }

    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return false;
    }

    return tentar_login_por_cookie($pdo);
}