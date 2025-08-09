<?php
$pagina_atual = $_GET['pagina'] ?? 'inicio';
?>

<!-- Sessão Infraestrutura -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#5bc0de;cursor:default;">Infraestrutura</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'energia') ? 'active' : '' ?>" href="?pagina=energia">⚡ Energia</a>
        </li>
    </ul>
</li>

<!-- Sessão Equipamentos -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#5bc0de;cursor:default;">Equipamentos</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'impressoras') ? 'active' : '' ?>" href="?pagina=impressoras">🖨️ Impressoras</a>
        </li>
    </ul>
</li>

<!-- Sessão Consumíveis -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#ffce54;cursor:default;">Consumíveis</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'insumos') ? 'active' : '' ?>" href="?pagina=insumos">🧰 Insumos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'filamentos') ? 'active' : '' ?>" href="?pagina=filamentos">🧵 Filamentos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'resinas') ? 'active' : '' ?>" href="?pagina=resinas">🧪 Resinas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'alcool') ? 'active' : '' ?>" href="?pagina=alcool">💧 Álcool Isopropílico</a>
        </li>
    </ul>
</li>

<!-- Sessão Sistema -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#d9534f;cursor:default;">Sistema</span>
    <ul class="nav flex-column ms-3">
        <?php if (isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'usuarios') ? 'active' : '' ?>" href="?pagina=usuarios">👥 Usuários</a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'conta') ? 'active' : '' ?>" href="?pagina=conta">👤 Conta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">🚪 Sair</a>
        </li>
    </ul>
</li>

<!-- Outros menus principais -->
<li class="nav-item">
    <a class="nav-link <?= ($pagina_atual === 'inicio') ? 'active' : '' ?>" href="?pagina=inicio">🏠 Início</a>
</li>
<li class="nav-item">
    <a class="nav-link <?= ($pagina_atual === 'impressoes') ? 'active' : '' ?>" href="?pagina=impressoes">🖼️ Impressões</a>
</li>
<li class="nav-item">
    <a class="nav-link <?= ($pagina_atual === 'pintura') ? 'active' : '' ?>" href="?pagina=pintura">🎨 Pintura</a>
</li>

<!-- Sessão Catálogo -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#337ab7;cursor:default;">Catálogo</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'produtos') ? 'active' : '' ?>" href="?pagina=produtos">📦 Produtos</a>
        </li>
    </ul>
</li>

<!-- Sessão Ferramentas -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#5cb85c;cursor:default;">Ferramentas</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'calculo_rapido' || $pagina_atual === 'calculo_detalhado') ? 'active' : '' ?>" href="?pagina=calculo_rapido">🧮 Cálculo Rápido</a>
        </li>
    </ul>
</li>

<!-- Sessão Biblioteca -->
<li class="nav-item">
    <span class="nav-link disabled" style="color:#f08080;cursor:default;">Biblioteca</span>
    <ul class="nav flex-column ms-3">
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'estudios') ? 'active' : '' ?>" href="?pagina=estudios">🎬 Estudios</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($pagina_atual === 'colecoes') ? 'active' : '' ?>" href="?pagina=colecoes">📚 Coleções</a>
        </li>
    </ul>
</li>