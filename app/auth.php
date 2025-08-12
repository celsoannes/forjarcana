<?php
// filepath: /var/www/html/forjarcana/app/auth.php
function usuario_logado() {
    return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
}