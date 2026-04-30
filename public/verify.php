<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

$title = 'Verify';

$message = 'Invalid verification link.';
$success = false;

$token = $_GET['token'] ?? '';

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE verification_token = ?');
    $stmt->execute([$token]);

    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare('
            UPDATE users
            SET is_verified = 1,
                verification_token = NULL
            WHERE id = ?
        ');
        $stmt->execute([$user['id']]);

        $success = true;
        $message = 'Your account is now verified. You can login.';
    }
}

ob_start();
?>

<div class="max-w-md mx-auto bg-slate-950 border border-slate-800 p-6 rounded-xl">
    <h1 class="text-xl font-semibold mb-4 text-white">Email Verification</h1>

    <p class="<?= $success ? 'text-indigo-200' : 'text-slate-300' ?>">
        <?= e($message) ?>
    </p>

    <?php if ($success): ?>
        <a href="/login.php" class="text-indigo-300 underline block mt-4">
            Go to login
        </a>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
