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
    <div class="bg-slate-950 border border-slate-800 p-6 rounded-xl max-w-md mx-auto">
        <h1 class="text-2xl font-semibold mb-4 text-white">Login</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md mb-4">
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
                       value="<?= e($_POST['login'] ?? '') ?>" class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full" required autofocus>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" class="bg-slate-900 border border-slate-700 text-white p-2 rounded-md w-full" required>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md">Login</button>
        </form>

        <p class="mt-4 text-sm text-slate-400">
            No account yet? <a href="/register.php" class="text-indigo-300 hover:text-indigo-200">Register</a>.
        </p>
        <p class="mt-2 text-sm text-slate-400">
            <a href="/forgot-password.php" class="text-indigo-300 hover:text-indigo-200">Forgot password?</a>
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
