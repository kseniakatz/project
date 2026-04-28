<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageId = (int)($_POST['image_id'] ?? 0);
    $userId  = (int)$_SESSION['user_id'];

    if ($imageId > 0) {
        $stmt = $pdo->prepare('
            INSERT IGNORE INTO likes (user_id, image_id)
            VALUES (?, ?)
        ');
        $stmt->execute([$userId, $imageId]);
    }
}

header('Location: /?page=gallery');
exit;
