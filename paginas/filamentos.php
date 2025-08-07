<?php
// filamentos.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_filamento.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_filamento.php';
} elseif ($acao === 'excluir') {
    include 'excluir_filamento.php';
} else {
    include 'listar_filamentos.php';
}