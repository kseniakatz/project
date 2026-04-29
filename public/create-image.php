<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$overlay = basename($_POST['overlay'] ?? '');
$cameraImage = $_POST['camera_image'] ?? '';
$upload = $_FILES['uploaded_image'] ?? null;
$maxImageSize = 5 * 1024 * 1024;

$allowedOverlays = array_map('basename', glob(__DIR__ . '/overlays/*.png') ?: []);

if (!in_array($overlay, $allowedOverlays, true)) {
    header('Location: /editor.php');
    exit;
}

$base = null;

if ($cameraImage !== '') {
    if (strlen($cameraImage) > $maxImageSize) {
        header('Location: /editor.php');
        exit;
    }

    if (!str_starts_with($cameraImage, 'data:image/png;base64,')) {
        header('Location: /editor.php');
        exit;
    }

    $data = base64_decode(substr($cameraImage, strlen('data:image/png;base64,')), true);

    if ($data === false) {
        header('Location: /editor.php');
        exit;
    }

    $base = imagecreatefromstring($data);
} elseif (
    is_array($upload)
    && isset($upload['error'], $upload['tmp_name'])
    && $upload['error'] === UPLOAD_ERR_OK
    && is_uploaded_file($upload['tmp_name'])
) {
    if (($upload['size'] ?? 0) > $maxImageSize) {
        header('Location: /editor.php');
        exit;
    }

    $mime = mime_content_type($upload['tmp_name']);

    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
        header('Location: /editor.php');
        exit;
    }

    $info = getimagesize($upload['tmp_name']);

    if ($info === false) {
        header('Location: /editor.php');
        exit;
    }

    $base = $mime === 'image/png'
        ? imagecreatefrompng($upload['tmp_name'])
        : imagecreatefromjpeg($upload['tmp_name']);
}

if (!$base) {
    header('Location: /editor.php');
    exit;
}

$overlayPath = __DIR__ . '/overlays/' . $overlay;
$overlayImg = imagecreatefrompng($overlayPath);

if (!$overlayImg) {
    imagedestroy($base);
    header('Location: /editor.php');
    exit;
}

$width = imagesx($base);
$height = imagesy($base);

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
