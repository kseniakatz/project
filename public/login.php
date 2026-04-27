<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /gallery.php');
    exit;
}

$errors = [];
$title = 'Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '') {
        $errors[] = 'Email or username required';
    }
    if ($password === '') {
        $errors[] = 'Password required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT id, username, password, is_verified FROM users WHERE email = ? OR username = ? LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid credentials';
        } elseif ((int)$user['is_verified'] !== 1) {
            $errors[] = 'Please verify your email before logging in';
        } else {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /gallery.php');
            exit;
        }
    }
}

ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Login</h1>

        <?php if (!empty($errors)): ?>
            <div class="status-box error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div class="field">
                <label for="login">Email or Username</label>
                <input id="login" type="text" name="login"
                       value="<?= e($_POST['login'] ?? '') ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>
            <button type="submit" class="button-link">Login</button>
        </form>

        <p class="footer-note">
            No account yet? <a href="/register.php">Register</a>.
        </p>
        <p class="footer-note">
            <a href="/reset.php">Forgot password?</a>
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../src/layout.php';
