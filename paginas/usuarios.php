<?php
// usuarios.php
$acao = $_GET['acao'] ?? 'listar';

if ($acao === 'editar') {
    include 'editar_usuario.php';
} elseif ($acao === 'adicionar') {
    include 'adicionar_usuario.php';
} elseif ($acao === 'excluir') {
    include 'excluir_usuario.php';
} else {
    include 'listar_usuarios.php';
}