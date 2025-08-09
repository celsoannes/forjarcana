<?php
// impressoes.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_impressao.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_impressao.php';
} elseif ($acao === 'excluir') {
    include 'excluir_impressao.php';
} else {    
    include 'listar_impressoes.php';
}