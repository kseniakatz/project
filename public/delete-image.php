<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uploadId = (int)($_POST['upload_id'] ?? 0);

    if ($uploadId > 0) {

        // 1. Проверяем, что изображение принадлежит пользователю
        $stmt = $pdo->prepare('
            SELECT filename 
            FROM uploads 
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$uploadId, $userId]);
        $image = $stmt->fetch();

        if ($image) {

            // 2. Удаляем файл с сервера
            $filePath = __DIR__ . '/uploads/' . basename($image['filename']);

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // 3. Удаляем из БД
            $stmt = $pdo->prepare('
                DELETE FROM uploads 
                WHERE id = ?
            ');
            $stmt->execute([$uploadId]);
        }
    }
}

header('Location: /editor.php');
exit;