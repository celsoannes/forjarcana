<?php
// alcool.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_alcool.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_alcool.php';
} elseif ($acao === 'excluir') {
    include 'excluir_alcool.php';
} else {
    include 'listar_alcool.php';
}