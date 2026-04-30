<?php
$userId = $_SESSION['user_id'] ?? null;
$pageTitle = $title ?? 'Camagru';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans antialiased">
    <header class="border-b border-slate-800 bg-slate-950">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="/gallery.php" class="text-2xl font-semibold tracking-tight">
                Camagru
            </a>

            <nav class="flex flex-wrap gap-2 text-sm">
                <a class="px-3 py-2 rounded-md text-slate-200 hover:bg-slate-800 transition" href="/gallery.php">Gallery</a>

                <?php if ($userId): ?>
                    <a class="px-3 py-2 rounded-md text-slate-200 hover:bg-slate-800 transition" href="/editor.php">Editor</a>
                    <a class="px-3 py-2 rounded-md text-slate-200 hover:bg-slate-800 transition" href="/profile.php">Profile</a>
                    <a class="px-3 py-2 rounded-md text-slate-300 hover:bg-slate-800 transition" href="/logout.php">Logout</a>
                <?php else: ?>
                    <a class="px-3 py-2 rounded-md text-slate-200 hover:bg-slate-800 transition" href="/login.php">Login</a>
                    <a class="px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-500 transition" href="/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full">
        <div class="max-w-6xl mx-auto px-4 py-8">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5 sm:p-8">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-4 py-4 text-center text-sm text-slate-400">
            © <?= date('Y') ?> Camagru
        </div>
    </footer>
</body>
</html>
