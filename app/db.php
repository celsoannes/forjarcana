<?php
$host = 'localhost';
$db   = 'forjarcana';      // Nome do seu banco de dados
$user = 'forjarcana';     // Usuário do MySQL
$pass = 'minhasenha';       // Senha do MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}