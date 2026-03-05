<?php
function renderMenu($pagina_atual) {
  $acao_atual = $_GET['acao'] ?? '';
  $fluxo_miniaturas = ($pagina_atual === 'impressoes') && (($_GET['fluxo'] ?? '') === 'miniaturas');
  $fluxo_torres = ($pagina_atual === 'impressoes') && (($_GET['fluxo'] ?? '') === 'torres');
  $fluxo_mapas = ($pagina_atual === 'mapas') && (($_GET['fluxo'] ?? '') === 'mapas');
  $adicionar_miniatura = ($pagina_atual === 'miniaturas') && ($acao_atual === 'adicionar');
  $adicionar_torre = ($pagina_atual === 'torres') && ($acao_atual === 'adicionar');
  $fluxo_produtos_mapas = ($pagina_atual === 'mapas') && in_array($acao_atual, ['adicionar', 'editar', 'visualizar'], true);
  $menu_produtos_ativo =
    ($pagina_atual === 'produtos') ||
    ($pagina_atual === 'miniaturas') ||
    ($pagina_atual === 'torres') ||
    $fluxo_miniaturas ||
    $fluxo_torres ||
    $fluxo_mapas ||
    $fluxo_produtos_mapas ||
    $adicionar_miniatura ||
    $adicionar_torre;
  $menu_impressoes_ativo = ($pagina_atual === 'impressoes') && !$fluxo_miniaturas && !$fluxo_torres;
?>
<nav class="mt-2">
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    
      <li class="nav-header">ADMINISTRAÇÃO</li>
      <?php if (isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin'): ?>
        <li class="nav-item">
          <a href="?pagina=usuarios" class="nav-link <?= ($pagina_atual === 'usuarios') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-users-cog"></i>
            <p>Gerenciar Usuários</p>
          </a>
        </li>
      <?php endif; ?>
      <li class="nav-item">
      <a href="?pagina=perfil" class="nav-link <?= ($pagina_atual === 'perfil') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-user"></i>
        <p>Perfil</p>
      </a>
    </li>

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
        <i class="nav-icon fas fa-compact-disc"></i>
        <p>Filamentos</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=resinas" class="nav-link <?= ($pagina_atual === 'resinas') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-bottle-water"></i>
        <p>Resinas</p>
      </a>
    </li>
    <li class="nav-item">
      <a href="?pagina=alcool" class="nav-link <?= ($pagina_atual === 'alcool') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-tint"></i>
        <p>Álcool</p>
      </a>
    </li>

    <li class="nav-header">CONTATOS</li>
    <li class="nav-item">
      <a href="?pagina=fornecedores" class="nav-link <?= ($pagina_atual === 'fornecedores') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-truck-loading"></i>
        <p>Fornecedores</p>
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
    <li class="nav-item">
      <a href="?pagina=mapas" class="nav-link <?= ($pagina_atual === 'mapas' && !$fluxo_mapas && !$fluxo_produtos_mapas) ? 'active' : '' ?>">
        <i class="nav-icon fas fa-map"></i>
        <p>Mapas</p>
      </a>
    </li>

    <li class="nav-header">CATÁLOGO</li>
    <li class="nav-item">
      <a href="?pagina=produtos" class="nav-link <?= $menu_produtos_ativo ? 'active' : '' ?>">
        <i class="nav-icon fas fa-box-open"></i>
        <p>Produtos</p>
      </a>
    </li>

    <li class="nav-header">PRODUÇÃO</li>
    <li class="nav-item">
      <a href="?pagina=impressoes" class="nav-link <?= $menu_impressoes_ativo ? 'active' : '' ?>">
        <i class="nav-icon fas fa-boxes-stacked"></i>
        <p>Impressões</p>
      </a>
    </li>

    <!-- Nova sessão Ferramentas -->
    <li class="nav-header">FERRAMENTAS</li>
    <li class="nav-item">
      <a href="?pagina=calculo_rapido" class="nav-link <?= ($pagina_atual === 'calculo_rapido') ? 'active' : '' ?>">
        <i class="nav-icon fas fa-calculator"></i>
        <p>Cálculo Rápido</p>
      </a>
    </li>

    <li class="nav-header">CONTA</li>
    <li class="nav-item">
      <a href="pages/auth/logout.php" class="nav-link">
        <i class="nav-icon fas fa-sign-out-alt"></i>
        <p>Sair</p>
      </a>
    </li>
  </ul>
</nav>
<?php
}
?>