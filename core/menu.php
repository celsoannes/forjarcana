<?php
$itens_menu = [
    ['pagina' => 'inicio',      'icone' => '🏠', 'titulo' => 'Início'],
    ['pagina' => 'impressoras', 'icone' => '🖨️', 'titulo' => 'Impressoras'],
    // Materiais será tratado separadamente abaixo
    ['pagina' => 'insumos',     'icone' => '🧰', 'titulo' => 'Insumos'],
    ['pagina' => 'produtos',    'icone' => '📦', 'titulo' => 'Produtos'],
    ['pagina' => 'pintura',     'icone' => '🎨', 'titulo' => 'Pintura'],
    ['pagina' => 'energia',     'icone' => '⚡', 'titulo' => 'Energia'],
];

// Adiciona o menu "Usuários" apenas para admin
if (isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin') {
    $itens_menu[] = ['pagina' => 'usuarios', 'icone' => '👤', 'titulo' => 'Usuários'];
}

$pagina_atual = $_GET['pagina'] ?? 'inicio';
?>
<?php foreach ($itens_menu as $item): ?>
    <?php if ($item['titulo'] !== 'Materiais'): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === $item['pagina']) ? 'active' : '' ?>" href="?pagina=<?= $item['pagina'] ?>">
                <?= $item['icone'] . ' ' . $item['titulo'] ?>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Materiais não clicável com submenus -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#ffce54;cursor:default;">⚙️ Materiais</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'resina') ? 'active' : '' ?>" href="?pagina=resina">Resinas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'filamento') ? 'active' : '' ?>" href="?pagina=filamento">Filamentos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'alcool') ? 'active' : '' ?>" href="?pagina=alcool">Álcool Isopropílico</a>
        </li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link" href="logout.php">🚪 Sair</a>
</li>