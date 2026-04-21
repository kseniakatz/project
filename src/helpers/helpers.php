<?php

declare(strict_types=1);

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}