<?php
// estudios.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_estudio.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_estudio.php';
} elseif ($acao === 'excluir') {
    include 'excluir_estudio.php';
} else {    
    include 'listar_estudios.php';
}