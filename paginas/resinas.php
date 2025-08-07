<?php
// resinas.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_resina.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_resina.php';
} elseif ($acao === 'excluir') {
    include 'excluir_resina.php';
} else {
    include 'listar_resinas.php';
}