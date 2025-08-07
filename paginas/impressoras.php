<?php
// impressoras.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_impressora.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_impressora.php';
} elseif ($acao === 'excluir') {
    include 'excluir_impressora.php';
} else {
    include 'listar_impressoras.php';
}