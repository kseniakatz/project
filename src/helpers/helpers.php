<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function isLogged(): bool
{
    return isset($_SESSION['user_id']);
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');
    return $baseUrl . '/' . ltrim($path, '/');
}

function validatePassword(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters';
    }

    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number';
    }

    return null;
}
