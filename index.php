<?php
require_once "config/db.php";
include "includes/header.php";

$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search        = trim($_GET['search']    ?? '');
$category      = trim($_GET['category']  ?? '');
$date_from     = trim($_GET['date_from'] ?? '');
$date_to       = trim($_GET['date_to']   ?? '');
$sort          = $_GET['sort']           ?? 'newest';
$author_filter = trim($_GET['author']    ?? '');

$where  = ["b.status = 'published'"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "b.title LIKE ?";
    $params[] = '%' . $search . '%';
    $types   .= 's';
}
if ($category !== '') {
    $where[]  = "b.category = ?";
    $params[] = $category;
    $types   .= 's';
}
// Author filter
if ($author_filter !== '') {
    $where[]  = "b.author = ?";
    $params[] = $author_filter;
    $types   .= 's';
}
if ($date_from !== '') {
    $where[]  = "DATE(b.created_at) >= ?";
    $params[] = $date_from;
    $types   .= 's';
}
if ($date_to !== '') {
    $where[]  = "DATE(b.created_at) <= ?";
    $params[] = $date_to;
    $types   .= 's';
}

$whereSql = implode(' AND ', $where);
$orderSql = match($sort) {
    'views'   => 'b.views DESC',
    'rating'  => 'avg_r DESC',
    'oldest'  => 'b.created_at ASC',
    default   => 'b.created_at DESC',
};

$cstmt = $conn->prepare("SELECT COUNT(DISTINCT b.book_id) AS total FROM dbproj_books b WHERE $whereSql");
if (!empty($params)) $cstmt->bind_param($types, ...$params);
$cstmt->execute();
$totalRows  = $cstmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$sql = "SELECT b.*,
               ROUND(AVG(r.rating_value),1) AS avg_r,
               COUNT(r.rating_id) AS total_ratings
        FROM dbproj_books b
        LEFT JOIN dbproj_ratings r ON b.book_id = r.book_id
        WHERE $whereSql
        GROUP BY b.book_id
        ORDER BY $orderSql
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$allParams = array_merge($params, [$limit, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$result = $stmt->get_result();
// Authors dropdown
$authorsRes = $conn->query(
    "SELECT DISTINCT author FROM dbproj_books WHERE status='published' ORDER BY author"
);
?>

<div class="hero-banner mb-4">
    <div class="hero-content">
        <h1>📚 BookVerse</h1>
        <p>Discover, review, and share knowledge — one book at a time.</p>
    </div>
</div>

<h4 class="mb-3">
    <?= $category ? htmlentities($category) . ' Books' : 'All Books' ?>
    <small class="text-muted fw-normal">(<?= htmlentities($totalRows) ?> found)</small>
</h4>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search by Title</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Book title…"
                       value="<?= htmlentities($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Author</label>
                <select name="author" class="form-select">
                    <option value="">All Authors</option>
                    <?php while ($a = $authorsRes->fetch_assoc()): ?>
                        <option value="<?= htmlentities($a['author']) ?>"
                                <?= $author_filter === $a['author'] ? 'selected' : '' ?>>
                            <?= htmlentities($a['author']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach (['Programming','Database','Design','Technology','Security','Business'] as $cat): ?>
                        <option value="<?= htmlentities($cat) ?>" <?= $category === $cat ? 'selected':'' ?>><?= htmlentities($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?= htmlentities($date_from) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?= htmlentities($date_to) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Sort by</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
                    <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
                    <option value="views"  <?= $sort==='views' ?'selected':'' ?>>Most Viewed</option>
                    <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Highest Rated</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-dark w-100">Search</button>
            </div>
        </form>
        <?php if ($search || $category || $author_filter || $date_from || $date_to): ?>
            <div class="mt-2">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-sm btn-outline-secondary">✕ Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($result->num_rows === 0): ?>
    <div class="alert alert-info">No books found matching your criteria.</div>
<?php endif; ?>

<?php while ($row = $result->fetch_assoc()): ?>
<div class="card mb-4 shadow-sm book-card">
    <div class="row g-0">
        <div class="col-md-3">
            <?php $placeholder = 'https://placehold.co/300x220/dee2e6/6c757d?text=No+Cover'; ?>
            <img src="<?= BASE_URL ?>/uploads/books/images/<?= $row['cover_image'] ?? '' ?>"
                 class="book-list-img w-100"
                 alt="<?= htmlentities($row['title']) ?>"
                 onerror="this.src='<?= htmlentities($placeholder) ?>'">
        </div>
        <div class="col-md-9">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <h5 class="card-title mb-1"><?= htmlentities($row['title']) ?></h5>
                    <span class="badge bg-dark"><?= htmlentities($row['category']) ?></span>
                </div>
                <p class="text-muted small mb-2">
                    By <strong><?= htmlentities($row['author']) ?></strong>
                    &bull; <?= date('M j, Y', strtotime($row['created_at'])) ?>
                </p>
                <p class="card-text"><?= htmlentities(substr($row['description'], 0, 160)) ?>…</p>

                <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                    <span class="text-muted small">👁 <?= (int)$row['views'] ?> views</span>
                    <?php if ($row['avg_r']): ?>
                        <span class="text-muted small">⭐ <?= htmlentities($row['avg_r']) ?>/5 (<?= (int)$row['total_ratings'] ?> ratings)</span>
                    <?php else: ?>
                        <span class="text-muted small">⭐ No ratings yet</span>
                    <?php endif; ?>

                </div>

                <a href="<?= BASE_URL ?>/book-details.php?id=<?= (int)$row['book_id'] ?>"
                   class="btn btn-dark btn-sm">View More →</a>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?page=<?= htmlentities($i) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&author=<?= urlencode($author_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= urlencode($sort) ?>">
                    <?= htmlentities($i) ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
