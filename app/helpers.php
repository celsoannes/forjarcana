<?php
function getTituloPagina($pagina) {
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
        case 'impressoes':
            return 'Impressões';
        // ... outros casos ...
        default:
            return 'Dashboard';
    }
}

function getBreadcrumbPagina($pagina) {
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
        case 'impressoes':
            return 'Impressões';
        // ... outros casos ...
        default:
            return 'Dashboard';
    }
}
?>