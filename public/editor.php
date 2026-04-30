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
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

ob_start();
?>

<h1 class="text-2xl font-semibold mb-6 text-white">Editor</h1>

<?php if ($error): ?>
    <div class="mb-4 bg-indigo-500/10 text-indigo-100 border border-indigo-500/30 p-3 rounded-md">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row gap-4">
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
        <video id="video" class="w-full max-w-md rounded-md bg-slate-900" autoplay></video>
        <canvas id="preview" class="w-full max-w-md block mt-3 border border-slate-700 rounded-md bg-slate-900"></canvas>
        <canvas id="canvas" class="hidden"></canvas>

        <form method="POST" action="/create-image.php" enctype="multipart/form-data" class="mt-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="camera_image" id="camera_image">
            <input type="hidden" name="overlay" id="overlay">

            <div>
                <p class="mb-2 text-sm text-slate-300">Or upload image:</p>
                <input type="file" name="uploaded_image" id="fileInput" accept="image/png,image/jpeg" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-white hover:file:bg-indigo-500">
            </div>

            <div>
                <p class="mb-2 text-sm text-slate-300">Choose overlay:</p>
                <?php foreach ($overlays as $overlay): ?>
                    <img src="/overlays/<?= e($overlay) ?>"
                         width="80"
                         class="overlay inline-block m-1 rounded-md border border-slate-700 bg-slate-900 transition transform hover:scale-105 cursor-pointer"
                         data-overlay="<?= e($overlay) ?>"
                    >
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" id="capture" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed" disabled>Take photo</button>
                <button type="button" id="stopCamera" class="bg-slate-800 hover:bg-slate-700 text-white px-3 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed" disabled>Stop camera</button>
                <button type="submit" id="save" class="bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed" disabled>Save</button>
            </div>
        </form>
    </div>

    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
        <h3 class="text-lg font-semibold mb-4 text-white">Your photos</h3>

        <?php foreach ($images as $img): ?>
            <div class="mb-3">
                <img src="/uploads/<?= e($img['filename']) ?>" width="100" class="rounded-md transition transform hover:scale-105">
                <form method="POST" action="/delete-image.php" class="mt-2">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                    <button type="submit" class="text-sm text-slate-300 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed" onclick="return confirm('Delete this image?')">Delete</button>
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
const stopCameraBtn = document.getElementById('stopCamera');
const saveBtn = document.getElementById('save');
const cameraInput = document.getElementById('camera_image');
const overlayInput = document.getElementById('overlay');
const uploadInput = document.getElementById('fileInput');
const previewCtx = preview.getContext('2d');

let hasImage = false;
let hasOverlay = false;
let hasWebcam = false;
let hasUpload = false;
let overlayImage = null;
let uploadedImage = null;
let stream = null;

if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(cameraStream => {
            stream = cameraStream;
            hasWebcam = true;
            video.srcObject = stream;
            video.addEventListener('loadedmetadata', drawPreview);
            updateButtons();
        })
        .catch(() => {
            hasWebcam = false;
            video.style.display = 'none';
            updateButtons();
        });
} else {
    video.style.display = 'none';
}

document.querySelectorAll('.overlay').forEach(img => {
    img.addEventListener('click', () => {
        overlayInput.value = img.dataset.overlay;
        overlayImage = new Image();
        overlayImage.src = img.src;
        hasOverlay = true;
        if (hasUpload && uploadedImage) {
            drawUploadPreview();
        }
        updateButtons();
    });
});

captureBtn.addEventListener('click', () => {
    if (!hasWebcam) {
        return;
    }

    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video, 0, 0);

    cameraInput.value = canvas.toDataURL('image/png');
    uploadInput.value = '';
    hasImage = true;
    updateButtons();
});

stopCameraBtn.addEventListener('click', () => {
    if (!stream) {
        return;
    }

    stream.getTracks().forEach(track => track.stop());
    video.srcObject = null;
    stream = null;
    hasWebcam = false;
    updateButtons();
});

uploadInput.addEventListener('change', () => {
    cameraInput.value = '';
    hasUpload = uploadInput.files.length > 0;
    hasImage = hasUpload;

    if (hasUpload) {
        const reader = new FileReader();

        reader.onload = function (e) {
            uploadedImage = new Image();
            uploadedImage.onload = () => drawUploadPreview();
            uploadedImage.src = e.target.result;
        };

        reader.readAsDataURL(uploadInput.files[0]);
    }

    updateButtons();
});

function updateButtons() {
    captureBtn.disabled = !(hasOverlay && hasWebcam);
    stopCameraBtn.disabled = !hasWebcam;
    saveBtn.disabled = !(hasOverlay && hasImage);
}

function drawUploadPreview() {
    if (!uploadedImage) return;

    preview.width = uploadedImage.width;
    preview.height = uploadedImage.height;

    previewCtx.drawImage(uploadedImage, 0, 0);

    if (overlayImage && overlayImage.complete) {
        previewCtx.drawImage(overlayImage, 0, 0, preview.width, preview.height);
    }
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
