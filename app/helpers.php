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
        // ... outros casos ...
        default:
            return 'Dashboard';
    }
}
?>