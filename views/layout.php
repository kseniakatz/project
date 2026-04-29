<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers/helpers.php';

$pageTitle = isset($title) ? e($title) : 'Camagru';
$isAuthenticated = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-900">

<header class="bg-white shadow">
    <div class="max-w-5xl mx-auto flex justify-between items-center p-4">
        <a href="/gallery.php" class="font-bold text-lg">Camagru</a>

        <nav class="flex gap-4">
            <?php if ($isAuthenticated): ?>
                <a href="/gallery.php">Gallery</a>
                <a href="/editor.php">Editor</a>
                <a href="/profile.php">Profile</a>
                <a href="/logout.php" class="text-red-500">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a href="/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="max-w-5xl mx-auto p-6">
    <?= $content ?? '' ?>
</main>

<footer class="text-center text-sm text-gray-500 p-4">
    Camagru
</footer>

</body>
</html>
