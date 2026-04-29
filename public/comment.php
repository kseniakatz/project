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
    $content  = trim($_POST['content'] ?? '');
    $userId   = (int)$_SESSION['user_id'];

    if ($uploadId > 0 && $content !== '' && strlen($content) <= 500) {

        // check image exists
        $stmt = $pdo->prepare('SELECT id FROM uploads WHERE id = ?');
        $stmt->execute([$uploadId]);

        if (!$stmt->fetch()) {
            header('Location: /gallery.php');
            exit;
        }

        // insert comment
        $stmt = $pdo->prepare('
            INSERT INTO comments (user_id, upload_id, content)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$userId, $uploadId, $content]);

        // get image owner
        $stmt = $pdo->prepare('
            SELECT u.id, u.email, u.is_send_comment_email
            FROM uploads up
            JOIN users u ON u.id = up.user_id
            WHERE up.id = ?
        ');
        $stmt->execute([$uploadId]);
        $owner = $stmt->fetch();

        // send email if enabled and not self
        if ($owner && $owner['is_send_comment_email'] && (int)$owner['id'] !== $userId) {

            $to      = $owner['email'];
            $subject = 'New comment on your photo';
            $message = "Someone commented on your photo:\n\n" . $content;

            mail($to, $subject, $message);
        }
    }
}

header('Location: /gallery.php');
exit;
