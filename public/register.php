<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

$errors = [];
$success = false;
$title = 'Register';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
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
        $verifyLink = "http://localhost:8080/verify.php?token=$token";

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
<section class="auth-wrap">
    <div class="auth-card">
        <p class="section-tag">New Expedition Member</p>
        <h1 class="auth-title">Register</h1>
        <p class="auth-copy">
            Join the crew and start cataloging scenes, portraits, and hidden clues inside the Camagru archive.
        </p>

        <?php if ($success): ?>
            <div class="mt-4 text-green-600">
                Account created.<br>

                <a href="<?= e($verifyLink) ?>" class="underline">
                    Verify your account
                </a>
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
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    placeholder="Choose your crew name"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="captain@camagru.local"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="At least 6 characters" required>
            </div>

            <button type="submit" class="button-link">Create Account</button>
        </form>

        <p class="footer-note">
            Already in the archive? <a href="/login.php">Login here</a>.
        </p>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../src/layout.php';
