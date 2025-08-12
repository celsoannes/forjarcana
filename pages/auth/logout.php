<?php
// filepath: /var/www/html/forjarcana/pages/auth/logout.php

session_start();
session_unset();
session_destroy();
header('Location: ../../app/login.php');
exit;