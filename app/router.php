<?php
$pagina = $_GET['pagina'] ?? 'dashboard';
$acao = $_GET['acao'] ?? '';

// Protege todas as rotas de usuários para admin
if ($pagina === 'usuarios' && (!isset($_SESSION['usuario_cargo']) || $_SESSION['usuario_cargo'] !== 'admin')) {
    header('Content-Type: text/html; charset=utf-8');
    require_once __DIR__ . '/../404.php';
    exit;
}

if ($pagina === 'energia' && $acao === 'adicionar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../modules/energia/adicionar.php';
    exit;
}

switch ($pagina) {
    case 'energia':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/energia/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/energia/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/energia/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/energia/listar.php';
        }
        break;
    case 'usuarios':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/usuarios/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/usuarios/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/usuarios/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/usuarios/listar.php';
        }
        break;
    case 'impressoras3d':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/impressoras3d/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/impressoras3d/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/impressoras3d/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/impressoras3d/listar.php';
        }
        break;
    case 'componentes':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/componentes/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/componentes/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/componentes/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/componentes/listar.php';
        }
        break;
    case 'filamentos':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/filamentos/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/filamentos/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/filamentos/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/filamentos/listar.php';
        }
        break;
    case 'resinas':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/resinas/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/resinas/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/resinas/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/resinas/listar.php';
        }
        break;
    case 'alcool':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/alcool/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/alcool/editar.php';
                break;
            default:
                include __DIR__ . '/../modules/alcool/listar.php';
        }
        break;
    case 'estudios':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/estudios/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/estudios/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/estudios/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/estudios/listar.php';
        }
        break;
    case 'colecoes':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/colecoes/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/colecoes/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/colecoes/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/colecoes/listar.php';
        }
        break;
    case 'impressoes':
        switch ($acao) {
            case 'adicionar':
                include __DIR__ . '/../modules/impressoes/adicionar.php';
                break;
            case 'editar':
                include __DIR__ . '/../modules/impressoes/editar.php';
                break;
            case 'excluir':
                include __DIR__ . '/../modules/impressoes/excluir.php';
                break;
            default:
                include __DIR__ . '/../modules/impressoes/listar.php';
        }
        break;
    default:
        include __DIR__ . '/../modules/dashboard.php';
}