<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$title = 'Profile';
$errors = [];
$success = false;

$stmt = $pdo->prepare('
    SELECT username, email, is_send_comment_email
    FROM users
    WHERE id = ?
');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sendCommentEmail = isset($_POST['is_send_comment_email']) ? 1 : 0;

    if ($username === '' || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            SELECT id
            FROM users
            WHERE (username = ? OR email = ?)
            AND id != ?
        ');
        $stmt->execute([$username, $email, $userId]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE users
            SET username = ?,
                email = ?,
                is_send_comment_email = ?
            WHERE id = ?
        ');
        $stmt->execute([$username, $email, $sendCommentEmail, $userId]);

        $_SESSION['username'] = $username;
        $user['username'] = $username;
        $user['email'] = $email;
        $user['is_send_comment_email'] = $sendCommentEmail;
        $success = true;
    }
}

ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Profile</h1>

        <?php if ($success): ?>
            <div class="status-box success">
                <p>Profile updated.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="status-box error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div class="field">
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= e($user['username']) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($user['email']) ?>"
                    required
                >
            </div>

            <label>
                <input
                    type="checkbox"
                    name="is_send_comment_email"
                    value="1"
                    <?= (int)$user['is_send_comment_email'] === 1 ? 'checked' : '' ?>
                >
                Send email notifications for comments
            </label>

            <button type="submit" class="button-link">Update Profile</button>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
