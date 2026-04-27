<?php

declare(strict_types=1);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /gallery.php');
} else {
    header('Location: /login.php');
}
exit;
