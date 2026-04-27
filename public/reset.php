<?php

declare(strict_types=1);

session_start();

$title = 'Reset';

ob_start();
?>
<section class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Reset</h1>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/../src/layout.php';
