<?php
// energia.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_energia.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_energia.php';
} elseif ($acao === 'excluir') {
    include 'excluir_energia.php';
} else {
    include 'listar_energia.php';
}