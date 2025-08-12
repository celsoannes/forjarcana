<?php
function renderMenu($pagina_atual) {
?>
<nav class="mt-2">
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <?php if (isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin'): ?>
      <li class="nav-header">ADMINISTRAÇÃO</li>
      <li class="nav-item">
        <a href="?pagina=usuarios" class="nav-link <?= ($pagina_atual === 'usuarios') ? 'active' : '' ?>">
          <i class="nav-icon fas fa-users-cog"></i>
          <p>Gerenciar Usuários</p>
        </a>
      </li>
    <?php endif; ?>

    <li class="nav-header">EQUIPAMENTOS</li>
    <li class="nav-item">
      <a href="?pagina=impressoras3d" class="nav-link <?= ($pagina_atual === 'impressoras3d') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-cube"></i>
        <p>Impressoras 3D</p>
      </a>
    </li>

    <li class="nav-header">INFRAESTRUTURA</li>
    <li class="nav-item">
      <a href="?pagina=energia" class="nav-link <?= ($pagina_atual === 'energia') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-bolt"></i>
        <p>Energia</p>
      </a>
    </li>

    <li class="nav-header">CONSUMÍVEIS</li>
    <li class="nav-item">
      <a href="?pagina=componentes" class="nav-link <?= ($pagina_atual === 'componentes') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-cogs"></i>
        <p>Componentes</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=filamentos" class="nav-link <?= ($pagina_atual === 'filamentos') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-stream"></i>
        <p>Filamentos</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=resinas" class="nav-link <?= ($pagina_atual === 'resinas') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-vial"></i>
        <p>Resinas</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=alcool_isopropilico" class="nav-link <?= ($pagina_atual === 'alcool_isopropilico') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-tint"></i>
        <p>Álcool Isopropílico</p>
      </a>
    </li>
  </ul>
</nav>
<?php
}
?>