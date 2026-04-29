<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        exit('Invalid CSRF token');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $sendCommentEmail = isset($_POST['is_send_comment_email']) ? 1 : 0;

    if ($username === '' || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
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
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('
                UPDATE users
                SET username = ?,
                    email = ?,
                    is_send_comment_email = ?,
                    password = ?
                WHERE id = ?
            ');
            $stmt->execute([$username, $email, $sendCommentEmail, $hash, $userId]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE users
                SET username = ?,
                    email = ?,
                    is_send_comment_email = ?
                WHERE id = ?
            ');
            $stmt->execute([$username, $email, $sendCommentEmail, $userId]);
        }

        $_SESSION['username'] = $username;
        $user['username'] = $username;
        $user['email'] = $email;
        $user['is_send_comment_email'] = $sendCommentEmail;
        $success = true;
    }
}

ob_start();
?>
<section class="max-w-md mx-auto">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Profile</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                <p>Profile updated.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div>
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= e($user['username']) ?>"
                    class="border p-2 rounded w-full"
                    required
                >
            </div>

            <div>
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($user['email']) ?>"
                    class="border p-2 rounded w-full"
                    required
                >
            </div>

            <div>
                <label for="password">New password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Leave empty to keep current password"
                    class="border p-2 rounded w-full"
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

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Profile</button>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
