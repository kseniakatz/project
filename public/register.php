<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Получаем и очищаем данные
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Валидация
    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    // 3. Проверка уникальности
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);

        if ($stmt->fetch()) {
            $errors[] = "User already exists";
        }
    }

    // 4. Если всё ок — создаём пользователя
    if (empty($errors)) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // токен для подтверждения email
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, verification_token)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $email,
            $hashedPassword,
            $token
        ]);

        $success = true;
    }
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>

<h1>Register</h1>

<?php if ($success): ?>
    <p style="color: green;">Account created! Please check your email.</p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <ul style="color: red;">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="POST">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Register</button>
</form>

</body>
</html>
