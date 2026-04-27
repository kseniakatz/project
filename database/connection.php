<?php

$requiredEnv = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];

foreach ($requiredEnv as $name) {
    if (getenv($name) === false || getenv($name) === '') {
        die('Missing required environment variable: ' . $name);
    }
}

$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );


} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
