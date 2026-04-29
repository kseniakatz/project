<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers/helpers.php';

$pageTitle = isset($title) ? e($title) : 'Camagru';
$isAuthenticated = isset($_SESSION['user_id']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        :root {
            --bg: #08141d;
            --bg-soft: #102838;
            --panel: rgba(10, 19, 27, 0.76);
            --panel-strong: rgba(8, 15, 21, 0.92);
            --ink: #e0f5f4;
            --muted: #91c8c9;
            --line: rgba(83, 177, 181, 0.24);
            --gold: #3fb1b6;
            --gold-soft: #1f767d;
            --danger-bg: rgba(120, 27, 20, 0.32);
            --danger-line: rgba(228, 127, 104, 0.4);
            --success-bg: rgba(40, 88, 54, 0.32);
            --success-line: rgba(143, 212, 146, 0.35);
            --shadow: 0 24px 60px rgba(0, 0, 0, 0.38);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            background:
                radial-gradient(circle at top, rgba(63, 177, 182, 0.16), transparent 30%),
                radial-gradient(circle at 20% 20%, rgba(35, 101, 122, 0.38), transparent 24%),
                linear-gradient(180deg, #04131b 0%, #0a2430 38%, #07161d 100%);
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -2;
        }

        body::before {
            background:
                linear-gradient(120deg, rgba(255, 255, 255, 0.03), transparent 35%),
                repeating-linear-gradient(
                    90deg,
                    transparent 0,
                    transparent 74px,
                    rgba(255, 255, 255, 0.015) 75px,
                    transparent 76px
                );
            opacity: 0.35;
        }

        body::after {
            background:
                radial-gradient(circle at 50% 0%, rgba(117, 219, 223, 0.12), transparent 36%),
                linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.2));
            z-index: -1;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .site-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(12px);
            background: rgba(6, 14, 20, 0.72);
            border-bottom: 1px solid rgba(63, 177, 182, 0.2);
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            min-height: 76px;
        }

        .brand-lockup {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .brand-kicker {
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.32em;
            text-transform: uppercase;
        }

        .brand-mark {
            font-size: clamp(1.6rem, 2vw, 2rem);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-link,
        .button-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
        }

        .nav-link {
            color: var(--muted);
        }

        .nav-link:hover,
        .button-link:hover {
            transform: translateY(-1px);
            border-color: rgba(63, 177, 182, 0.34);
        }

        .button-link {
            background: linear-gradient(135deg, var(--gold) 0%, #1f767d 100%);
            color: #04181a;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(225, 255, 255, 0.3);
        }

        .page-main {
            padding: 36px 0 72px;
        }

        .panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(13, 24, 34, 0.9), rgba(8, 17, 24, 0.86));
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: var(--shadow);
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(140deg, rgba(255, 255, 255, 0.05), transparent 40%),
                radial-gradient(circle at top right, rgba(63, 177, 182, 0.18), transparent 28%);
            pointer-events: none;
        }

        .hero {
            min-height: 72vh;
            padding: clamp(32px, 5vw, 64px);
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
            gap: 28px;
            align-items: end;
        }

        .eyebrow,
        .section-tag {
            color: var(--muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.36em;
            margin: 0 0 16px;
        }

        .hero-title,
        .section-title,
        .auth-title {
            margin: 0;
            text-transform: uppercase;
            line-height: 0.92;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .hero-title {
            font-size: clamp(3.4rem, 10vw, 8rem);
            max-width: 8ch;
        }

        .hero-subtitle,
        .section-copy,
        .auth-copy {
            color: #c5e8e7;
            line-height: 1.7;
            font-size: 1.02rem;
            max-width: 62ch;
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border: 1px solid rgba(63, 177, 182, 0.26);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.03);
            color: var(--ink);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-card {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 20px;
            padding: 28px;
            border-radius: 24px;
            background:
                linear-gradient(180deg, rgba(95, 197, 201, 0.14), rgba(9, 18, 25, 0.6)),
                rgba(12, 20, 29, 0.82);
            border: 1px solid rgba(95, 197, 201, 0.24);
        }

        .hero-card-title {
            margin: 0;
            font-size: 1.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hero-card-list,
        .feature-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .hero-card-list li,
        .feature-list li {
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero-card-list li:last-child,
        .feature-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 22px;
        }

        .story-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 22px;
        }

        .section-card,
        .story-card,
        .auth-card {
            padding: 26px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }

        .story-card-highlight {
            background:
                linear-gradient(180deg, rgba(63, 177, 182, 0.12), rgba(10, 18, 26, 0.82)),
                var(--panel-strong);
        }

        .section {
            margin-top: 28px;
            padding: clamp(26px, 4vw, 40px);
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3.4rem);
            margin-bottom: 12px;
        }

        .metric {
            display: block;
            margin-bottom: 8px;
            font-size: 2.2rem;
            color: var(--gold);
        }

        .muted {
            color: var(--muted);
        }

        .auth-wrap {
            width: min(580px, 100%);
            margin: 6vh auto 0;
        }

        .auth-card {
            padding: clamp(28px, 4vw, 40px);
            background:
                linear-gradient(180deg, rgba(63, 177, 182, 0.1), rgba(7, 14, 20, 0.82)),
                var(--panel-strong);
        }

        .auth-title {
            font-size: clamp(2.3rem, 6vw, 4.2rem);
            margin-bottom: 12px;
        }

        .form-grid {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field label {
            color: var(--muted);
            font-size: 0.82rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        .field input {
            width: 100%;
            min-height: 54px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(63, 177, 182, 0.2);
            background: rgba(3, 9, 14, 0.62);
            color: var(--ink);
            font: inherit;
        }

        .field input:focus {
            outline: 2px solid rgba(63, 177, 182, 0.24);
            border-color: rgba(63, 177, 182, 0.42);
        }

        .status-box {
            padding: 14px 16px;
            border-radius: 16px;
            margin-top: 18px;
        }

        .status-box.error {
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
        }

        .status-box.success {
            background: var(--success-bg);
            border: 1px solid var(--success-line);
        }

        .status-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .status-box p {
            margin: 0;
        }

        .footer-note {
            margin-top: 16px;
            color: var(--muted);
        }

        .footer-note a {
            color: var(--ink);
        }

        @media (max-width: 960px) {
            .hero,
            .section-grid,
            .story-grid {
                grid-template-columns: 1fr;
            }

            .topbar-inner {
                padding: 14px 0;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 640px) {
            .site-shell {
                width: min(100% - 20px, 1180px);
            }

            .page-main {
                padding-top: 20px;
            }

            .hero {
                min-height: auto;
                padding: 24px;
            }

            .section,
            .section-card,
            .story-card,
            .auth-card {
                padding: 22px;
            }

            .button-link,
            .button-secondary,
            .nav-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="site-shell topbar-inner">
            <a href="/gallery.php" class="brand-lockup">
                <strong class="brand-mark">Home</strong>
            </a>

            <nav class="nav" aria-label="Primary">
                <?php if ($isAuthenticated): ?>
                    <a href="/reset-password.php" class="nav-link">Reset</a>
                    <a href="/logout.php" class="button-link">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="nav-link">Login</a>
                    <a href="/register.php" class="button-link">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="site-shell page-main">
        <?= $content ?? '' ?>
    </main>
</body>
</html>
