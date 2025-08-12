<?php
require __DIR__ . '/db.php';
if (!isset($pdo)) {
    die('Erro: $pdo não foi definido!');
}