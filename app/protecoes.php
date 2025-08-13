<?php
// filepath: /var/www/html/forjarcana/app/protecoes.php

// Redireciona para login se não estiver autenticado

if (!usuario_logado()) {
    header('Location: app/login.php');
    exit;
}

// Protege todas as rotas de usuários para admin
if ($pagina_atual === 'usuarios' && (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin')) {
    require_once __DIR__ . '/../404.php';
    exit;
}

// Protege edição de componente para o próprio usuário
if ($pagina_atual === 'componentes' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM componentes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $componente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$componente) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de energia para o próprio usuário
if ($pagina_atual === 'energia' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM energia WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $energia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$energia) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de filamento para o próprio usuário
if ($pagina_atual === 'filamentos' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM filamento WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $filamento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$filamento) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de resina para o próprio usuário
if ($pagina_atual === 'resinas' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM resinas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $resina = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resina) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de álcool para o próprio usuário
if ($pagina_atual === 'alcool' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM alcool WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $alcool = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$alcool) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de estudio para o próprio usuário
if ($pagina_atual === 'estudios' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM estudios WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $estudio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$estudio) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de coleção para o próprio usuário
if ($pagina_atual === 'colecoes' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM colecoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $colecao = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$colecao) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de impressora para o próprio usuário
if ($pagina_atual === 'impressoras3d' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $impressora = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$impressora) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege exclusão de impressora para o próprio usuário
if ($pagina_atual === 'impressoras3d' && $acao === 'excluir') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM impressoras WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $impressora = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$impressora) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege edição de impressão para o próprio usuário
if ($pagina_atual === 'impressoes' && $acao === 'editar') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM impressoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $impressao = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$impressao) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}

// Protege exclusão de impressão para o próprio usuário
if ($pagina_atual === 'impressoes' && $acao === 'excluir') {
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM impressoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $impressao = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$impressao) {
        require_once __DIR__ . '/../404.php';
        exit;
    }
}
?>