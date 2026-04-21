<?php

declare(strict_types=1);

<<<<<<< ours
echo 'Logout page';
=======
session_start();

$_SESSION = [];

session_destroy();

header('Location: /login.php');
exit;
>>>>>>> theirs
