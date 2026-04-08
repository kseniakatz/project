<?php

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'camagru';
$user = getenv('DB_USER') ?: 'camagru_user';
$pass = getenv('DB_PASSWORD') ?: 'camagru_password';

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