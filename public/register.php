<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/helpers.php';

$errors = [];
$success = false;
<<<<<<< ours
=======
$verificationLink = null;
>>>>>>> theirs
$title = 'Register';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

<<<<<<< ours
=======
    // Валидация
>>>>>>> theirs
    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

<<<<<<< ours
=======
    // Проверка существования
>>>>>>> theirs
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);

        if ($stmt->fetch()) {
            $errors[] = "User already exists";
        }
    }

<<<<<<< ours
=======
    // Создание пользователя
>>>>>>> theirs
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

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

<<<<<<< ours
=======
        // ссылка подтверждения (для subject достаточно)
        $verificationLink = "http://localhost:8080/verify.php?token=" . $token;

>>>>>>> theirs
        $success = true;
    }
}

ob_start();
?>
<<<<<<< ours
<section class="auth-wrap">
    <div class="auth-card">
        <p class="section-tag">New Expedition Member</p>
        <h1 class="auth-title">Register</h1>
        <p class="auth-copy">
            Join the crew and start cataloging scenes, portraits, and hidden clues inside the Camagru archive.
        </p>

        <?php if ($success): ?>
            <div class="status-box success">
                <p>Account created. The next step is email verification once that flow is wired in.</p>
=======

<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Register</h1>

        <?php if ($success): ?>
            <div class="status-box success">
                <p>Account created.</p>
                <p>
                    Verify your email:
                    <a href="<?= e($verificationLink) ?>">Click here</a>
                </p>
>>>>>>> theirs
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
<<<<<<< ours
                    placeholder="Choose your crew name"
=======
>>>>>>> theirs
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
<<<<<<< ours
                    placeholder="captain@camagru.local"
=======
>>>>>>> theirs
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
<<<<<<< ours
                <input id="password" type="password" name="password" placeholder="At least 6 characters" required>
=======
                <input id="password" type="password" name="password" required>
>>>>>>> theirs
            </div>

            <button type="submit" class="button-link">Create Account</button>
        </form>
<<<<<<< ours

        <p class="footer-note">
            Already in the archive? <a href="/login.php">Login here</a>.
        </p>
    </div>
</section>
=======
    </div>
</section>

>>>>>>> theirs
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
