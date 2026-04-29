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

<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4">Email Verification</h1>

    <p class="<?= $success ? 'text-green-600' : 'text-red-500' ?>">
        <?= e($message) ?>
    </p>

    <?php if ($success): ?>
        <a href="/login.php" class="text-blue-600 underline block mt-4">
            Go to login
        </a>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
