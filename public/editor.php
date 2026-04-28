<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

// overlays (жёстко заданные — безопасно)
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

// последние изображения пользователя (thumbnails)
$stmt = $pdo->prepare('
    SELECT filename
    FROM uploads
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
');
$stmt->execute([$_SESSION['user_id']]);
$images = $stmt->fetchAll();

ob_start();
?>

<h1>Editor</h1>

<div style="display:flex; gap:20px;">

    <!-- MAIN SECTION -->
    <div>

        <!-- webcam -->
        <video id="video" width="400" autoplay></video>
        <canvas id="canvas" style="display:none;"></canvas>

        <!-- upload -->
        <div>
            <p>Or upload image:</p>
            <input type="file" id="upload" accept="image/*">
        </div>

        <!-- overlays -->
        <div>
            <p>Choose overlay:</p>
            <?php foreach ($overlays as $overlay): ?>
                <img src="/overlays/<?= e($overlay) ?>"
                     width="80"
                     class="overlay"
                     data-overlay="<?= e($overlay) ?>"
                     style="cursor:pointer;">
            <?php endforeach; ?>
        </div>

        <!-- capture -->
        <button id="capture" disabled>Take photo</button>

        <!-- form -->
        <form method="POST" action="/create-image.php">
            <input type="hidden" name="image_data" id="image_data">
            <input type="hidden" name="overlay" id="overlay">
            <button type="submit" id="save" disabled>Save</button>
        </form>

    </div>

    <!-- SIDE SECTION (thumbnails) -->
    <div>
        <h3>Your photos</h3>

        <?php foreach ($images as $img): ?>
            <div style="margin-bottom:10px;">
                <img src="/uploads/<?= e($img['filename']) ?>" width="100">
            </div>
        <?php endforeach; ?>

    </div>

</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const captureBtn = document.getElementById('capture');
const saveBtn = document.getElementById('save');
const imageInput = document.getElementById('image_data');
const overlayInput = document.getElementById('overlay');
const uploadInput = document.getElementById('upload');

let hasImage = false;
let hasOverlay = false;

// webcam
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
    })
    .catch(() => {
        console.log('Webcam not available');
    });

// overlay select
document.querySelectorAll('.overlay').forEach(img => {
    img.addEventListener('click', () => {
        overlayInput.value = img.dataset.overlay;
        hasOverlay = true;
        updateButtons();
    });
});

// capture from webcam
captureBtn.addEventListener('click', () => {
    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video, 0, 0);

    imageInput.value = canvas.toDataURL('image/png');
    hasImage = true;
    updateButtons();
});

// upload image
uploadInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        imageInput.value = reader.result;
        hasImage = true;
        updateButtons();
    };
    reader.readAsDataURL(file);
});

// enable buttons logic
function updateButtons() {
    captureBtn.disabled = !hasOverlay;
    saveBtn.disabled = !(hasOverlay && hasImage);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
