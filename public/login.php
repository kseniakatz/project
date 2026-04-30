<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    header('Location: /gallery.php');
    exit;
}

$errors = [];
$title = 'Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        exit('Invalid CSRF token');
    }

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
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /gallery.php');
            exit;
        }
    }
}

ob_start();
?>
<section class="max-w-md mx-auto">
    <div class="bg-white p-6 rounded shadow max-w-md mx-auto">
        <h1 class="text-2xl font-bold mb-4">Login</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div>
                <label for="login" class="block text-sm font-medium">Email or Username</label>
                <input id="login" type="text" name="login"
                       value="<?= e($_POST['login'] ?? '') ?>" class="border p-2 rounded w-full" required autofocus>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" class="border p-2 rounded w-full" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Login</button>
        </form>

        <p class="mt-4 text-sm text-gray-600">
            No account yet? <a href="/register.php">Register</a>.
        </p>
        <p class="mt-2 text-sm text-gray-600">
            <a href="/forgot-password.php">Forgot password?</a>
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
