<?php
// filepath: /var/www/html/forjarcana/pages/auth/logout.php

session_start();
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/auth.php';

revogar_login_lembrado_atual($pdo);
session_unset();
session_destroy();
header('Location: ../../app/login.php');
exit;