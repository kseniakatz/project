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
    SELECT
        u.id,
        u.filename,
        u.created_at,
        usr.username,
        COUNT(DISTINCT l.user_id) AS likes_count
    FROM uploads u
    JOIN users usr ON usr.id = u.user_id
    LEFT JOIN likes l ON l.upload_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
');

$stmt->bindValue(':limit', PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$images = $stmt->fetchAll();

$likedIds = [];

if ($userId && !empty($images)) {
    $ids = array_column($images, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT upload_id
        FROM likes
        WHERE user_id = ?
        AND upload_id IN ($placeholders)
    ");

    $stmt->execute(array_merge([$userId], $ids));

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $likedIds = array_flip($rows);
}

$commentsStmt = $pdo->prepare('
    SELECT c.content, u.username
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.upload_id = ?
    ORDER BY c.created_at ASC
');

ob_start();
?>

<h1 class="text-2xl font-bold mb-6">Gallery</h1>

<?php if (empty($images)): ?>
    <p class="text-gray-500">No images yet.</p>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <?php foreach ($images as $img): ?>
            <?php
                $filename = basename($img['filename']);
                $liked = isset($likedIds[$img['id']]);

                $commentsStmt->execute([(int)$img['id']]);
                $comments = $commentsStmt->fetchAll();
            ?>

            <div class="bg-white rounded-xl shadow overflow-hidden">

                <img
                    src="/uploads/<?= e($filename) ?>"
                    alt="Photo by <?= e($img['username']) ?>"
                    class="w-full aspect-square object-cover"
                >

                <div class="p-3 flex justify-between text-sm text-gray-600">
                    <span><?= e($img['username']) ?></span>
                    <span><?= (int)$img['likes_count'] ?> ❤️</span>
                </div>

                <?php if ($userId): ?>
                    <form method="POST" action="/like.php" class="p-3">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <button class="px-3 py-1 rounded text-sm <?= $liked ? 'bg-red-500 text-white' : 'bg-blue-600 text-white' ?>">
                            <?= $liked ? 'Unlike' : 'Like' ?>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="p-3 border-t text-sm">
                    <?php if (empty($comments)): ?>
                        <p class="text-gray-500">No comments yet.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <p>
                                <strong><?= e($comment['username']) ?>:</strong>
                                <?= e($comment['content']) ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($userId): ?>
                    <form method="POST" action="/comment.php" class="p-3 border-t">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <input type="text" name="content" required>
                        <button>Comment</button>
                    </form>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="flex items-center gap-4 mt-8">

            <?php if ($page > 1): ?>
                <a href="/gallery.php?page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>
            <?php endif; ?>

            <span class="text-gray-500">
                Page <?= $page ?> / <?= $totalPages ?>
            </span>

            <?php if ($page < $totalPages): ?>
                <a href="/gallery.php?page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
            <?php endif; ?>

        </nav>
    <?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
