<?php

require_once __DIR__ . '/../database/connection.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "DB OK";
} catch (Exception $e) {
    echo "DB ERROR";
}
