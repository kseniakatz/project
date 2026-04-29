<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function jsonResponse(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        jsonResponse(['success' => false]);
    }

    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        if ($isAjax) {
            jsonResponse(['success' => false]);
        }

        exit('Invalid CSRF token');
    }

    $uploadId = (int)($_POST['upload_id'] ?? 0);
    $userId   = (int)$_SESSION['user_id'];
    $liked = false;

    if ($uploadId > 0) {

        // check image exists
        $stmt = $pdo->prepare('SELECT id FROM uploads WHERE id = ?');
        $stmt->execute([$uploadId]);

        if (!$stmt->fetch()) {
            if ($isAjax) {
                jsonResponse(['success' => false]);
            }

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
            $liked = false;
        } else {
            // like
            $stmt = $pdo->prepare('
                INSERT INTO likes (user_id, upload_id)
                VALUES (?, ?)
            ');
            $stmt->execute([$userId, $uploadId]);
            $liked = true;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE upload_id = ?');
        $stmt->execute([$uploadId]);
        $likes = (int)$stmt->fetchColumn();

        if ($isAjax) {
            jsonResponse([
                'success' => true,
                'likes' => $likes,
                'liked' => $liked,
            ]);
        }
    }
}

if ($isAjax) {
    jsonResponse(['success' => false]);
}

header('Location: /gallery.php');
exit;
