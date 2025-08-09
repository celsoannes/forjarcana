<?php
// colecoes.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_colecoes.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_colecoes.php';
} elseif ($acao === 'excluir') {
    include 'excluir_colecoes.php';
} else {
    include 'listar_colecoes.php';
}