<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    exit('Invalid CSRF token');
}

$userId = (int)$_SESSION['user_id'];
$overlay = basename($_POST['overlay'] ?? '');
$cameraImage = $_POST['camera_image'] ?? '';
$upload = $_FILES['uploaded_image'] ?? null;
$maxImageSize = 5 * 1024 * 1024;

function redirectWithError(string $message): void
{
    $_SESSION['error'] = $message;
    header('Location: /editor.php');
    exit;
}

$allowedOverlays = array_map('basename', glob(__DIR__ . '/overlays/*.png') ?: []);

if (!in_array($overlay, $allowedOverlays, true)) {
    header('Location: /editor.php');
    exit;
}

$base = null;

if ($cameraImage !== '') {
    if (strlen($cameraImage) > $maxImageSize) {
        redirectWithError('Image file is too large.');
    }

    if (!str_starts_with($cameraImage, 'data:image/png;base64,')) {
        redirectWithError('Invalid image content.');
    }

    $data = base64_decode(substr($cameraImage, strlen('data:image/png;base64,')), true);

    if ($data === false) {
        redirectWithError('Invalid image content.');
    }

    $base = @imagecreatefromstring($data);
} elseif (
    is_array($upload)
    && isset($upload['error'], $upload['tmp_name'])
) {
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        redirectWithError('Upload failed. Please try again.');
    }

    if (!is_uploaded_file($upload['tmp_name'])) {
        redirectWithError('Invalid upload.');
    }

    if (($upload['size'] ?? 0) > $maxImageSize) {
        redirectWithError('Image file is too large.');
    }

    $mime = mime_content_type($upload['tmp_name']);

    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
        redirectWithError('Invalid file type. Only JPG and PNG are allowed.');
    }

    $info = getimagesize($upload['tmp_name']);

    if ($info === false) {
        redirectWithError('Invalid image content.');
    }

    $width = $info[0];
    $height = $info[1];

    if ($width > 2000 || $height > 2000) {
        redirectWithError('Image dimensions are too large.');
    }

    $base = $mime === 'image/png'
        ? @imagecreatefrompng($upload['tmp_name'])
        : @imagecreatefromjpeg($upload['tmp_name']);
}

if (!$base) {
    redirectWithError('Invalid image content.');
}

$overlayPath = __DIR__ . '/overlays/' . $overlay;
$overlayImg = @imagecreatefrompng($overlayPath);

if (!$overlayImg) {
    imagedestroy($base);
    header('Location: /editor.php');
    exit;
}

$width = imagesx($base);
$height = imagesy($base);

if ($width > 2000 || $height > 2000) {
    imagedestroy($base);
    imagedestroy($overlayImg);
    redirectWithError('Image dimensions are too large.');
}

$overlayResized = imagecreatetruecolor($width, $height);
imagealphablending($overlayResized, false);
imagesavealpha($overlayResized, true);

imagecopyresampled(
    $overlayResized,
    $overlayImg,
    0,
    0,
    0,
    0,
    $width,
    $height,
    imagesx($overlayImg),
    imagesy($overlayImg)
);

imagecopy($base, $overlayResized, 0, 0, 0, 0, $width, $height);

$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = bin2hex(random_bytes(16)) . '.png';
$path = $uploadDir . '/' . $filename;

if (!imagepng($base, $path)) {
    imagedestroy($base);
    imagedestroy($overlayImg);
    imagedestroy($overlayResized);
    header('Location: /editor.php');
    exit;
}

imagedestroy($base);
imagedestroy($overlayImg);
imagedestroy($overlayResized);

$stmt = $pdo->prepare('
    INSERT INTO uploads (user_id, filename)
    VALUES (?, ?)
');
$stmt->execute([$userId, $filename]);

header('Location: /gallery.php');
exit;
