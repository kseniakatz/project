<?php

declare(strict_types=1);

$routes = [
    'editor' => __DIR__ . '/editor.php',
    'gallery' => __DIR__ . '/gallery.php',
    'login' => __DIR__ . '/login.php',
    'logout' => __DIR__ . '/logout.php',
    'register' => __DIR__ . '/register.php',
    'reset-password' => __DIR__ . '/reset-password.php',
    'verify' => __DIR__ . '/verify.php',
];

$route = $_GET['page'] ?? null;

if ($route === null || $route === '') {
    session_start();
    $route = isset($_SESSION['user_id']) ? 'gallery' : 'login';
    session_write_close();
}

if (!isset($routes[$route])) {
    http_response_code(404);
    $title = 'Not Found';
    $content = '<section class="auth-wrap"><div class="auth-card"><h1 class="auth-title">Not Found</h1></div></section>';
    require __DIR__ . '/../views/layout.php';
    exit;
}

require $routes[$route];
