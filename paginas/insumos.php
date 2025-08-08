<?php
// insumos.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_insumo.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_insumo.php';
} elseif ($acao === 'excluir') {
    include 'excluir_insumo.php';
} else {    
    include 'listar_insumos.php';
}