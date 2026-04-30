<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$title = 'Register';
$mailSent = false;

function sendMail(string $to, string $subject, string $message): bool
{
    return mail($to, $subject, $message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        exit('Invalid CSRF token');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    $passwordError = validatePassword($password);
    if ($passwordError !== null) {
        $errors[] = $passwordError;
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);

        if ($stmt->fetch()) {
            $errors[] = "User already exists";
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $verifyLink = app_url('verify.php?token=' . $token);

        $mailSent = sendMail(
            $email,
            'Verify your Camagru account',
            "Click this link to verify your account:\n\n" . $verifyLink
        );

        if (!$mailSent) {
            $errors[] = 'Verification email could not be sent. Please try again later.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, verification_token)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $email,
            $hashedPassword,
            $token,
        ]);

        $success = true;
    }
}

ob_start();
?>
<section class="max-w-md mx-auto">
    <div class="bg-white p-6 rounded shadow max-w-md mx-auto">
        <p class="text-sm text-gray-500 mb-2">New Expedition Member</p>
        <h1 class="text-2xl font-bold mb-4">Register</h1>
        <p class="text-gray-600 mb-4">
            Join the crew and start cataloging scenes, portraits, and hidden clues inside the Camagru archive.
        </p>

        <?php if ($success): ?>
            <div class="mt-4 text-green-600">
                Account created. Check your email to verify your account.<br>

                <?php if (!$mailSent): ?>
                    Mail could not be sent. Please try again later.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded">
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
                <label for="username" class="block text-sm font-medium">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    placeholder="Choose your crew name"
                    class="border p-2 rounded w-full"
                    required
                >
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="captain@camagru.local"
                    class="border p-2 rounded w-full"
                    required
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" placeholder="At least 8 chars, upper, lower, number" class="border p-2 rounded w-full" required>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create Account</button>
        </form>

        <p class="mt-4 text-sm text-gray-600">
            Already in the archive? <a href="/login.php">Login here</a>.
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../views/layout.php';
