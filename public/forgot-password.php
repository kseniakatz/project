<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$title = 'Forgot Password';
$errors = [];
$success = false;
$mailSent = false;
$mailAttempted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        exit('Invalid CSRF token');
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $pdo->prepare('
                UPDATE users
                SET reset_token = ?,
                    reset_token_expires = ?
                WHERE id = ?
            ');
            $stmt->execute([$token, $expires, $user['id']]);

            $resetLink = app_url('reset-password.php?token=' . $token);
            $mailAttempted = true;
            $mailSent = mail($email, 'Reset your Camagru password', "Reset your password:\n\n" . $resetLink);

            if (!$mailSent) {
                error_log('Password reset email failed for user id ' . $user['id']);
            }
        }

        $success = true;
    }
}

ob_start();
?>
<section class="max-w-md mx-auto">
    <div class="bg-slate-950 border border-slate-800 p-6 rounded-xl">
        <h1 class="text-2xl font-semibold mb-4 text-white">Forgot Password</h1>

        <?php if ($success): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
                <p>If the email exists, a reset link has been sent.</p>
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
                <label for="email" class="block text-sm font-medium">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full"
                    required
                >
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md">Send Reset Link</button>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
