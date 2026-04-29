<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadId = (int)($_POST['upload_id'] ?? 0);
    $userId   = (int)$_SESSION['user_id'];

    if ($uploadId > 0) {

        // check image exists
        $stmt = $pdo->prepare('SELECT id FROM uploads WHERE id = ?');
        $stmt->execute([$uploadId]);

        if (!$stmt->fetch()) {
            header('Location: /gallery.php');
            exit;
        }

        // check if already liked
        $stmt = $pdo->prepare('
            SELECT 1 FROM likes 
            WHERE user_id = ? AND upload_id = ?
        ');
        $stmt->execute([$userId, $uploadId]);

        if ($stmt->fetch()) {
            // unlike
            $stmt = $pdo->prepare('
                DELETE FROM likes 
                WHERE user_id = ? AND upload_id = ?
            ');
            $stmt->execute([$userId, $uploadId]);
        } else {
            // like
            $stmt = $pdo->prepare('
                INSERT INTO likes (user_id, upload_id)
                VALUES (?, ?)
            ');
            $stmt->execute([$userId, $uploadId]);
        }
    }
}

header('Location: /gallery.php');
exit;
