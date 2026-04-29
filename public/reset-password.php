<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$title = 'Reset';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $_SESSION['user_id']]);

        $success = true;
    }
}

ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Reset</h1>

        <?php if ($success): ?>
            <div class="status-box success">
                <p>Password updated.</p>
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
                <label for="password">New password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <button type="submit" class="button-link">Update Password</button>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
