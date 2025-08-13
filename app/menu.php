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
        <i class="nav-icon fas fa-microscope"></i>
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
      <a href="?pagina=alcool" class="nav-link <?= ($pagina_atual === 'alcool') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-tint"></i>
        <p>Álcool</p>
      </a>
    </li>

    <li class="nav-header">BIBLIOTECA</li>
    <li class="nav-item">
      <a href="?pagina=estudios" class="nav-link <?= ($pagina_atual === 'estudios') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-cube"></i>
        <p>Estudios</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=colecoes" class="nav-link <?= ($pagina_atual === 'colecoes') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-layer-group"></i>
        <p>Coleções</p>
      </a>
    </li>

    <li class="nav-header">PRODUÇÃO</li>
    <li class="nav-item">
      <a href="?pagina=impressoes" class="nav-link <?= ($pagina_atual === 'impressoes') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-boxes"></i>
        <p>Impressões</p>
      </a>
    </li>
  </ul>
</nav>
<?php
}
?>