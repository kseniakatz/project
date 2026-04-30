<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$title = 'Reset Password';
$errors = [];
$success = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$user = null;

if ($token !== '') {
    $stmt = $pdo->prepare('
        SELECT id
        FROM users
        WHERE reset_token = ?
        AND reset_token_expires > NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
}

if (!$user) {
    $errors[] = 'Invalid or expired reset link';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        exit('Invalid CSRF token');
    }

    $password = $_POST['password'] ?? '';

    $passwordError = validatePassword($password);
    if ($passwordError !== null) {
        $errors[] = $passwordError;
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            UPDATE users
            SET password = ?,
                reset_token = NULL,
                reset_token_expires = NULL
            WHERE id = ?
        ');
        $stmt->execute([$hash, $user['id']]);

        $success = true;
    }
}

ob_start();
?>
<section class="max-w-md mx-auto">
    <div class="bg-slate-950 border border-slate-800 p-6 rounded-xl">
        <h1 class="text-2xl font-semibold mb-4 text-white">Reset Password</h1>

        <?php if ($success): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
                <p>Password updated. You can login now.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors) && !$success): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($user && !$success): ?>
            <form method="POST" class="flex flex-col gap-4">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div>
                    <label for="password" class="block text-sm font-medium">New password</label>
                    <input id="password" type="password" name="password" class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full" required>
                </div>

                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
