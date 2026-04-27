<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

$message = '';
$status = 'error';
$title = 'Verify';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    $message = 'Verification token is missing.';
} else {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE verification_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = 'Invalid or expired verification link.';
    } else {
        $stmt = $pdo->prepare('
            UPDATE users
            SET is_verified = 1, verification_token = NULL
            WHERE id = ?
        ');
        $stmt->execute([$user['id']]);

        $status = 'success';
        $message = 'Account verified. You can now log in.';
    }
}

ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Verify</h1>
        <div class="status-box <?= e($status) ?>">
            <p><?= e($message) ?></p>
        </div>

        <?php if ($status === 'success'): ?>
            <p class="footer-note">
                <a href="/login.php">Go to login</a>.
            </p>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../src/layout.php';
