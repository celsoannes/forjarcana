<?php

spl_autoload_register(static function (string $class): void {
    $baseDir = __DIR__ . '/';

    $namespaceMap = [
        'App\\Usuarios\\' => 'usuarios/',
        'App\\Fornecedores\\' => 'fornecedores/',
        'App\\Produtos\\' => 'produtos/',
        'App\\Torres\\' => 'torres/',
        'App\\Miniaturas\\' => 'miniaturas/',
        'App\\Mapas\\' => 'mapas/',
        'App\\Colecoes\\' => 'colecoes/',
        'App\\Componentes\\' => 'componentes/',
        'App\\Impressoras3d\\' => 'impressoras3d/',
        'App\\Filamentos\\' => 'filamentos/',
        'App\\Resinas\\' => 'resinas/',
        'App\\Energia\\' => 'energia/',
        'App\\Alcool\\' => 'alcool/',
        'App\\Estudios\\' => 'estudios/',
        'App\\ImpressoesOld\\' => 'impressoes_old/',
    ];

    foreach ($namespaceMap as $prefix => $subdir) {
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . $subdir . str_replace('\\', '/', $relativeClass) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }

    $prefixMap = [
        'Usuario' => 'usuarios/',
        'Fornecedor' => 'fornecedores/',
        'Produto' => 'produtos/',
        'Torre' => 'torres/',
        'Miniatura' => 'miniaturas/',
        'Mapa' => 'mapas/',
        'Colecao' => 'colecoes/',
        'Componente' => 'componentes/',
        'Impressora3d' => 'impressoras3d/',
        'Filamento' => 'filamentos/',
        'Resina' => 'resinas/',
        'Energia' => 'energia/',
        'Alcool' => 'alcool/',
        'Estudio' => 'estudios/',
        'ImpressaoOld' => 'impressoes_old/',
    ];

    foreach ($prefixMap as $prefix => $subdir) {
        if (strpos($class, $prefix) === 0) {
            $file = $baseDir . $subdir . $class . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }

    $fallback = $baseDir . $class . '.php';
    if (is_file($fallback)) {
        require_once $fallback;
    }
});
