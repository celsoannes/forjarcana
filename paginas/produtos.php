<?php
// produtos.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_produto.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_produto.php';
} elseif ($acao === 'excluir') {
    include 'excluir_produto.php';
} else {    
    include 'listar_produtos.php';
}