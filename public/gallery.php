<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

$title = 'Gallery';
$userId = $_SESSION['user_id'] ?? null;

const PER_PAGE = 5;

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM uploads')->fetchColumn();

$totalPages = $total > 0 ? (int)ceil($total / PER_PAGE) : 1;

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * PER_PAGE;

$stmt = $pdo->prepare('
    SELECT u.id, u.filename, u.created_at, usr.username,
           COUNT(l.user_id) AS likes_count
    FROM uploads u
    JOIN users usr ON usr.id = u.user_id
    LEFT JOIN likes l ON l.image_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
');
$stmt->bindValue(':limit',  PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll();

$commentsByUpload = [];

if (!empty($images)) {
    $ids = array_column($images, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT
            c.upload_id,
            c.content,
            c.created_at,
            u.username
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.upload_id IN ($placeholders)
        ORDER BY c.created_at ASC
    ");

    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $commentsByUpload[$row['upload_id']][] = $row;
    }
}

$likedIds = [];
if ($userId && !empty($images)) {
    $ids = implode(',', array_map('intval', array_column($images, 'id')));
    $rows = $pdo->query(
        "SELECT image_id FROM likes WHERE user_id = $userId AND image_id IN ($ids)"
    )->fetchAll(PDO::FETCH_COLUMN);
    $likedIds = array_flip($rows);
}

ob_start();
?>
<h1 class="section-title" style="margin-bottom:28px;">Gallery</h1>

<?php if (empty($images)): ?>
    <p style="color:var(--muted);">No images yet.</p>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
            <?php $filename = basename($img['filename']); ?>
            <div class="gallery-card">
                <img src="/uploads/<?= e($filename) ?>"
                     alt="Photo by <?= e($img['username']) ?>"
                     class="gallery-img">
                <div class="p-3 flex justify-between text-sm text-gray-600">
                    <span><?= e($img['username']) ?></span>
                    <span><?= e($img['likes_count']) ?> ❤️</span>
                </div>

                <div class="px-3 pb-3 text-sm space-y-1">
                    <?php if (!empty($commentsByUpload[$img['id']])): ?>
                        <?php foreach ($commentsByUpload[$img['id']] as $comment): ?>
                            <div>
                                <span class="font-semibold"><?= e($comment['username']) ?>:</span>
                                <span><?= e($comment['content']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-400">No comments</p>
                    <?php endif; ?>
                </div>

                <?php if ($userId): ?>
                    <form method="POST" action="/comment.php" class="p-3">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <input
                            type="text"
                            name="content"
                            placeholder="Add comment..."
                            required
                            class="border px-2 py-1 w-full text-sm mb-2 rounded"
                        >
                        <button class="bg-gray-800 text-white px-3 py-1 rounded text-sm">
                            Comment
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="/like.php" class="p-3">
                        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                        <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            Like
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="button-secondary">Prev</a>
            <?php endif; ?>
            <span class="muted">Page <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="button-secondary">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .gallery-card {
        border-radius: 18px;
        overflow: hidden;
        background: var(--panel);
        border: 1px solid var(--line);
    }
    .gallery-img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        display: block;
    }
    .gallery-meta {
        display: flex;
        justify-content: space-between;
        padding: 12px 16px;
        font-size: 0.9rem;
    }
    .gallery-actions {
        padding: 0 16px 14px;
    }
    .gallery-actions form {
        margin: 0;
    }
    .like-btn {
        background: none;
        border: 1px solid var(--line);
        color: var(--muted);
        border-radius: 999px;
        padding: 6px 14px;
        cursor: pointer;
        font: inherit;
        transition: border-color 180ms, color 180ms;
    }
    .like-btn:hover {
        border-color: rgba(63,177,182,0.5);
        color: var(--ink);
    }
    .like-btn.liked {
        border-color: rgba(63,177,182,0.6);
        color: var(--gold);
    }
    .like-count {
        color: var(--muted);
        font-size: 0.9rem;
    }
    .pagination {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 32px;
    }
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../src/layout.php';
