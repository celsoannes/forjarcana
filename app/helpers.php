<?php
function getTituloPagina($pagina) {
    $acao = $_GET['acao'] ?? '';
    $fluxo = $_GET['fluxo'] ?? '';

    if ($pagina === 'impressoes' && $fluxo === 'miniaturas') {
        return 'Produtos - Seleção de Impressora';
    }

    if ($pagina === 'impressoes' && $fluxo === 'torres') {
        return 'Produtos - Seleção de Impressora';
    }

    if ($pagina === 'miniaturas' && $acao === 'adicionar') {
        return 'Produtos - Adicionar Miniatura';
    }

    if ($pagina === 'torres' && $acao === 'adicionar') {
        return 'Produtos - Adicionar Torre de Dados';
    }

    if ($pagina === 'torres' && $acao === 'visualizar') {
        return 'Produtos - Visualizar Torre de Dados';
    }

    if ($pagina === 'torres' && $acao === 'editar') {
        return 'Produtos - Editar Torre de Dados';
    }

    if ($pagina === 'mapas' && $acao === 'visualizar') {
        return 'Produtos - Visualizar Mapa';
    }

    switch ($pagina) {
        case 'usuarios':
            return 'Usuários';
        case 'energia':
            return 'Energia';
        case 'impressoras3d':
            return 'Impressoras 3D';
        case 'componentes':
            return 'Componentes';
        case 'filamentos':
            return 'Filamentos';
        case 'resinas':
            return 'Resinas';
        case 'alcool':
            return 'Álcool';
        case 'estudios':
            return 'Estudios';
        case 'colecoes':
            return 'Coleções';
        case 'mapas':
            return 'Mapas';
        case 'impressoes':
            return 'Impressões';
        case 'produtos':
            return 'Produtos';
        case 'miniaturas':
            return 'Miniaturas';
        case 'torres':
            return 'Torres de Dados';
        case 'calculo_rapido':
            return 'Cálculo Rápido';
        case 'perfil':
            return 'Perfil do Usuário';
        case 'fornecedores':
            return 'Fornecedores';
        // ... outros casos ...
        default:
            return 'Dashboard';
    }
}

function getBreadcrumbPagina($pagina) {
    $acao = $_GET['acao'] ?? '';
    $fluxo = $_GET['fluxo'] ?? '';

    if ($pagina === 'impressoes' && $fluxo === 'miniaturas') {
        return 'Produtos / Seleção de Impressora';
    }

    if ($pagina === 'impressoes' && $fluxo === 'torres') {
        return 'Produtos / Seleção de Impressora';
    }

    if ($pagina === 'miniaturas' && $acao === 'adicionar') {
        return 'Produtos / Adicionar Miniatura';
    }

    if ($pagina === 'torres' && $acao === 'adicionar') {
        return 'Produtos / Adicionar Torre de Dados';
    }

    if ($pagina === 'torres' && $acao === 'visualizar') {
        return 'Produtos / Visualizar Torre de Dados';
    }

    if ($pagina === 'torres' && $acao === 'editar') {
        return 'Produtos / Editar Torre de Dados';
    }

    if ($pagina === 'mapas' && $acao === 'adicionar') {
        return 'Produtos / Adicionar Mapa';
    }

    if ($pagina === 'mapas' && $acao === 'editar') {
        return 'Produtos / Editar Mapa';
    }

    if ($pagina === 'mapas' && $acao === 'visualizar') {
        return 'Produtos / Visualizar Mapa';
    }

    switch ($pagina) {
        case 'usuarios':
            return 'Usuários';
        case 'energia':
            return 'Energia';
        case 'impressoras3d':
            return 'Impressoras 3D';
        case 'componentes':
            return 'Componentes';
        case 'filamentos':
            return 'Filamentos';
        case 'resinas':
            return 'Resinas';
        case 'alcool':
            return 'Álcool';
        case 'estudios':
            return 'Estudios';
        case 'colecoes':
            return 'Coleções';
        case 'mapas':
            return 'Mapas';
        case 'impressoes':
            return 'Impressões';
        case 'produtos':
            return 'Produtos';
        case 'miniaturas':
            return 'Miniaturas';
        case 'torres':
            return 'Torres de Dados';
        case 'calculo_rapido':
            return 'Cálculo Rápido';
        case 'perfil':
            return 'Perfil do Usuário';
        case 'fornecedores':
            return 'Fornecedores';
        // ... outros casos ...
        default:
            return 'Dashboard';
    }
}
?>