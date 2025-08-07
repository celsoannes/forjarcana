<?php
$itens_menu = [
    ['pagina' => 'inicio',      'icone' => '🏠', 'titulo' => 'Início'],
    ['pagina' => 'impressoras', 'icone' => '🖨️', 'titulo' => 'Impressoras'],
    ['pagina' => 'materiais',   'icone' => '⚙️', 'titulo' => 'Materiais'],
    ['pagina' => 'insumos',     'icone' => '🧰', 'titulo' => 'Insumos'],
    ['pagina' => 'produtos',    'icone' => '📦', 'titulo' => 'Produtos'],
    ['pagina' => 'pintura',     'icone' => '🎨', 'titulo' => 'Pintura'],
    ['pagina' => 'energia',     'icone' => '⚡', 'titulo' => 'Energia'],
];

// Adiciona o menu "Usuários" apenas para admin
if (isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin') {
    $itens_menu[] = ['pagina' => 'usuarios', 'icone' => '👤', 'titulo' => 'Usuários'];
}
?>
<?php foreach ($itens_menu as $item): ?>
    <li class="nav-item">
        <a class="nav-link" href="?pagina=<?= $item['pagina'] ?>">
            <?= $item['icone'] . ' ' . $item['titulo'] ?>
        </a>
    </li>
<?php endforeach; ?>
<li class="nav-item">
    <a class="nav-link" href="logout.php">🚪 Sair</a>
</li>