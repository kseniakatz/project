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
    SELECT username, email, password, is_send_comment_email
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
    $currentPassword = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $sendCommentEmail = isset($_POST['is_send_comment_email']) ? 1 : 0;
    $emailChanged = $email !== $user['email'];
    $verificationToken = null;

    if ($username === '' || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }

    if ($password !== '') {
        $passwordError = validatePassword($password);
        if ($passwordError !== null) {
            $errors[] = $passwordError;
        }
    }

    if (($emailChanged || $password !== '') && !password_verify($currentPassword, $user['password'])) {
        $errors[] = 'Current password is incorrect';
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
        if ($emailChanged) {
            $verificationToken = bin2hex(random_bytes(32));
            $verifyLink = app_url('verify.php?token=' . $verificationToken);

            if (!mail($email, 'Verify your new Camagru email', "Click this link to verify your new email:\n\n" . $verifyLink)) {
                $errors[] = 'Verification email could not be sent. Please try again later.';
            }
        }
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($emailChanged) {
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET username = ?,
                        email = ?,
                        is_send_comment_email = ?,
                        password = ?,
                        is_verified = 0,
                        verification_token = ?
                    WHERE id = ?
                ');
                $stmt->execute([$username, $email, $sendCommentEmail, $hash, $verificationToken, $userId]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET username = ?,
                        email = ?,
                        is_send_comment_email = ?,
                        password = ?
                    WHERE id = ?
                ');
                $stmt->execute([$username, $email, $sendCommentEmail, $hash, $userId]);
            }
        } else {
            if ($emailChanged) {
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET username = ?,
                        email = ?,
                        is_send_comment_email = ?,
                        is_verified = 0,
                        verification_token = ?
                    WHERE id = ?
                ');
                $stmt->execute([$username, $email, $sendCommentEmail, $verificationToken, $userId]);
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
        }

        if ($emailChanged) {
            session_destroy();
            header('Location: /login.php');
            exit;
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
    <div class="bg-slate-950 border border-slate-800 p-6 rounded-xl">
        <h1 class="text-2xl font-semibold mb-4 text-white">Profile</h1>

        <?php if ($success): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
                <p>Profile updated.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div>
                <label for="username" class="block text-sm font-medium">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= e($user['username']) ?>"
                    class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full"
                    required
                >
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($user['email']) ?>"
                    class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full"
                    required
                >
            </div>

            <div>
                <label for="current_password" class="block text-sm font-medium">Current password</label>
                <input
                    id="current_password"
                    type="password"
                    name="current_password"
                    placeholder="Required to change email or password"
                    class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">New password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Leave empty to keep current password"
                    class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full"
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

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md">Update Profile</button>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
