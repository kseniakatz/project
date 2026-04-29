<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$overlays = [
    '1cat.png',
    '2cat.png',
    '3cat.png',
    '4cat.png',
    '5cat.png',
    '6cat.png',
    '7cat.png',
    '8cat.png',
    '9cat.png',
    'pikachu.png'
];

$stmt = $pdo->prepare('
    SELECT id, filename
    FROM uploads
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$images = $stmt->fetchAll();

ob_start();
?>

<h1>Editor</h1>

<div style="display:flex; gap:20px;">
    <div>
        <video id="video" width="400" autoplay></video>
        <canvas id="preview" width="400" height="300" style="display:block; margin-top:10px; border:1px solid #ccc;"></canvas>
        <canvas id="canvas" style="display:none;"></canvas>

        <form method="POST" action="/create-image.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="camera_image" id="camera_image">
            <input type="hidden" name="overlay" id="overlay">

            <div>
                <p>Or upload image:</p>
                <input type="file" name="uploaded_image" id="uploaded_image" accept="image/png,image/jpeg">
            </div>

            <div>
                <p>Choose overlay:</p>
                <?php foreach ($overlays as $overlay): ?>
                    <img src="/overlays/<?= e($overlay) ?>"
                         width="80"
                         class="overlay transition transform hover:scale-105 cursor-pointer"
                         data-overlay="<?= e($overlay) ?>"
                    >
                <?php endforeach; ?>
            </div>

            <button type="button" id="capture" class="disabled:opacity-50 disabled:cursor-not-allowed" disabled>Take photo</button>
            <button type="submit" id="save" class="disabled:opacity-50 disabled:cursor-not-allowed" disabled>Save</button>
        </form>
    </div>

    <div>
        <h3>Your photos</h3>

        <?php foreach ($images as $img): ?>
            <div style="margin-bottom:10px;">
                <img src="/uploads/<?= e($img['filename']) ?>" width="100" class="transition transform hover:scale-105">
                <form method="POST" action="/delete-image.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                    <button type="submit" class="disabled:opacity-50 disabled:cursor-not-allowed" onclick="return confirm('Delete this image?')">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const preview = document.getElementById('preview');
const captureBtn = document.getElementById('capture');
const saveBtn = document.getElementById('save');
const cameraInput = document.getElementById('camera_image');
const overlayInput = document.getElementById('overlay');
const uploadInput = document.getElementById('uploaded_image');
const previewCtx = preview.getContext('2d');

let hasImage = false;
let hasOverlay = false;
let overlayImage = null;

navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
        video.addEventListener('loadedmetadata', drawPreview);
    });

document.querySelectorAll('.overlay').forEach(img => {
    img.addEventListener('click', () => {
        overlayInput.value = img.dataset.overlay;
        overlayImage = new Image();
        overlayImage.src = img.src;
        hasOverlay = true;
        captureBtn.disabled = false;
        updateButtons();
    });
});

captureBtn.addEventListener('click', () => {
    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video, 0, 0);

    cameraInput.value = canvas.toDataURL('image/png');
    uploadInput.value = '';
    hasImage = true;
    updateButtons();
});

uploadInput.addEventListener('change', () => {
    cameraInput.value = '';
    hasImage = uploadInput.files.length > 0;
    updateButtons();
});

function updateButtons() {
    captureBtn.disabled = !hasOverlay;
    saveBtn.disabled = !(hasOverlay && hasImage);
}

function drawPreview() {
    if (video.videoWidth > 0 && video.videoHeight > 0) {
        preview.width = video.videoWidth;
        preview.height = video.videoHeight;
        previewCtx.drawImage(video, 0, 0, preview.width, preview.height);

        if (overlayImage && overlayImage.complete) {
            previewCtx.drawImage(overlayImage, 0, 0, preview.width, preview.height);
        }
    }

    requestAnimationFrame(drawPreview);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
