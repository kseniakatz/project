<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

$errors = [];
$title = "Login";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    if ($password === '') {
        $errors[] = "Password required";
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("SELECT id, username, password, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "User not found";
        } else {

            if (!password_verify($password, $user['password'])) {
                $errors[] = "Wrong password";
            }

            if ((int)$user['is_verified'] === 0) {
                $errors[] = "Please verify your email first";
            }
        }

        if (empty($errors)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: /");
            exit;
        }
    }
}

require_once __DIR__ . '/helpers.php';

// 👉 начинаем буфер
ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <p class="section-tag">Return To The Map</p>
        <h1 class="auth-title">Login</h1>
        <p class="auth-copy">
            Pick up the trail where you left it. Enter the archive to manage your account and continue the expedition.
        </p>

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
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="captain@camagru.local" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="button-link">Login</button>
        </form>

        <p class="footer-note">
            No account yet? <a href="/register.php">Create one here</a>.
        </p>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
