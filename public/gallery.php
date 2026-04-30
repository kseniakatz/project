<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$title = 'Gallery';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$sort = $_GET['sort'] ?? 'newest';

if (!in_array($sort, ['newest', 'likes'], true)) {
    $sort = 'newest';
}

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
$orderBy = $sort === 'likes' ? 'likes_count DESC, u.created_at DESC' : 'u.created_at DESC';

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.user_id,
        u.filename,
        u.created_at,
        usr.username,
        COUNT(DISTINCT l.user_id) AS likes_count
    FROM uploads u
    JOIN users usr ON usr.id = u.user_id
    LEFT JOIN likes l ON l.upload_id = u.id
    GROUP BY u.id
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
");

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

<form method="GET" class="mb-6">
    <label for="sort" class="mr-2">Sort</label>
    <select id="sort" name="sort" class="border p-2 rounded" onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="likes" <?= $sort === 'likes' ? 'selected' : '' ?>>Most liked</option>
    </select>
</form>

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
                    class="w-full aspect-square object-cover transition transform hover:scale-105"
                >

                <div class="p-3 flex justify-between text-sm text-gray-600">
                    <span><?= e($img['username']) ?></span>
                </div>

                <?php if ($userId): ?>
                    <form method="POST" action="/like.php" class="p-3 like-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <button class="like-button px-3 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed <?= $liked ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700' ?>">
                            <span class="like-label"><?= $liked ? '❤️ Unlike' : '♡ Like' ?></span>
                            <span class="like-count"><?= (int)$img['likes_count'] ?></span>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($userId !== null && $userId === (int)$img['user_id']): ?>
                    <form method="POST" action="/delete-image.php" class="p-3">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <button class="text-red-500 text-sm disabled:opacity-50 disabled:cursor-not-allowed" onclick="return confirm('Delete this image?')">Delete</button>
                    </form>
                <?php endif; ?>

                <div class="m-3 p-3 border rounded bg-gray-50 text-sm comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="text-gray-500 no-comments">No comments yet.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <p class="mb-2 last:mb-0">
                                <strong class="font-bold"><?= e($comment['username']) ?>:</strong>
                                <span><?= e($comment['content']) ?></span>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($userId): ?>
                    <form method="POST" action="/comment.php" class="p-3 border-t comment-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="upload_id" value="<?= (int)$img['id'] ?>">
                        <input
                            type="text"
                            name="content"
                            required
                            placeholder="Write a comment..."
                            class="border p-2 rounded w-full mb-2"
                        >
                        <button class="bg-blue-500 text-white px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                            Send
                        </button>
                    </form>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="flex items-center gap-4 mt-8">

            <?php if ($page > 1): ?>
                <a href="/gallery.php?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>
            <?php endif; ?>

            <span class="text-gray-500">
                Page <?= $page ?> / <?= $totalPages ?>
            </span>

            <?php if ($page < $totalPages): ?>
                <a href="/gallery.php?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
            <?php endif; ?>

        </nav>
    <?php endif; ?>

<?php endif; ?>

<script>
document.querySelectorAll('.like-form').forEach(form => {
    form.addEventListener('submit', event => {
        event.preventDefault();

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    return;
                }

                const button = form.querySelector('.like-button');
                const label = form.querySelector('.like-label');
                const count = form.querySelector('.like-count');

                count.textContent = data.likes;
                label.textContent = data.liked ? '❤️ Unlike' : '♡ Like';
                button.classList.toggle('bg-red-500', data.liked);
                button.classList.toggle('text-white', data.liked);
                button.classList.toggle('bg-gray-200', !data.liked);
                button.classList.toggle('text-gray-700', !data.liked);
            })
            .catch(() => {});
    });
});

document.querySelectorAll('.comment-form').forEach(form => {
    form.addEventListener('submit', event => {
        event.preventDefault();

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.username || !data.content) {
                    return;
                }

                const card = form.closest('.bg-white');
                const list = card.querySelector('.comments-list');
                const empty = list.querySelector('.no-comments');

                if (empty) {
                    empty.remove();
                }

                const row = document.createElement('p');
                row.className = 'mb-2 last:mb-0';

                const name = document.createElement('strong');
                name.className = 'font-bold';
                name.textContent = data.username + ':';

                const content = document.createElement('span');
                content.textContent = ' ' + data.content;

                row.appendChild(name);
                row.appendChild(content);
                list.appendChild(row);
                form.reset();
            })
            .catch(() => {});
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layout.php';
