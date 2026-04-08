<?php

declare(strict_types=1);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') : 'Camagru' ?></title>
</head>
<body>
<?= $content ?? '' ?>
</body>
</html>
