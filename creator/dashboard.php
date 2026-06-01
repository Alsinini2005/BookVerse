<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'creator') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$creator_id = (int)$_SESSION['user_id'];
$msg        = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $del_id = (int)($_POST['book_id'] ?? 0);
    $stmt   = $conn->prepare("DELETE FROM dbproj_books WHERE book_id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $del_id, $creator_id);
    $stmt->execute();
    $msg = 'deleted';
}

// Stats
$statsQ = $conn->prepare(
    "SELECT COUNT(*) as total,
            SUM(status='published') as published,
            SUM(status='draft') as drafts,
            SUM(views) as total_views
     FROM dbproj_books WHERE creator_id = ?"
);
$statsQ->bind_param("i", $creator_id);
$statsQ->execute();
$stats = $statsQ->get_result()->fetch_assoc();

// Books list
$booksQ = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.category, b.status, b.views, b.created_at,
            ROUND(AVG(r.rating_value),1) AS avg_r
     FROM dbproj_books b
     LEFT JOIN dbproj_ratings r ON b.book_id = r.book_id
     WHERE b.creator_id = ?
     GROUP BY b.book_id
     ORDER BY b.created_at DESC"
);
$booksQ->bind_param("i", $creator_id);
$booksQ->execute();
$books = $booksQ->get_result();

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Creator Dashboard</h2>
    <a href="<?= BASE_URL ?>/creator/add_book.php" class="btn btn-dark">+ Add New Book</a>
</div>

<?php if ($msg === 'added'):   ?><div class="alert alert-success">Book saved successfully!</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="alert alert-success">Book updated successfully!</div><?php endif; ?>
<?php if ($msg === 'deleted'): ?><div class="alert alert-warning">Book deleted.</div><?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$stats['total'] ?></h3>
                <p class="text-muted mb-0">Total Books</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$stats['published'] ?></h3>
                <p class="text-muted mb-0">Published</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$stats['drafts'] ?></h3>
                <p class="text-muted mb-0">Drafts</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)($stats['total_views'] ?? 0) ?></h3>
                <p class="text-muted mb-0">Total Views</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">
        My Books
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Avg Rating</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($books->num_rows === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">
                        No books yet.
                        <a href="<?= BASE_URL ?>/creator/add_book.php">Add your first!</a>
                    </td></tr>
                <?php endif; ?>
                <?php while ($row = $books->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlentities($row['title']) ?></td>
                    <td><?= htmlentities($row['author']) ?></td>
                    <td><?= htmlentities($row['category']) ?></td>
                    <td>
                        <span class="badge <?= $row['status']==='published'?'bg-success':'bg-warning text-dark' ?>">
                            <?= htmlentities($row['status']) ?>
                        </span>
                    </td>
                    <td><?= (int)$row['views'] ?></td>
                    <td><?= $row['avg_r'] ? $row['avg_r'] . ' ⭐' : '—' ?></td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="d-flex gap-1 flex-wrap">
                        <a href="<?= BASE_URL ?>/creator/edit_book.php?id=<?= (int)$row['book_id'] ?>"
                           class="btn btn-sm btn-outline-dark">Edit</a>
                        <?php if ($row['status'] === 'published'): ?>
                            <a href="<?= BASE_URL ?>/book-details.php?id=<?= (int)$row['book_id'] ?>"
                               class="btn btn-sm btn-outline-secondary" target="_blank">View Live</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/creator/preview_book.php?id=<?= (int)$row['book_id'] ?>"
                               class="btn btn-sm btn-outline-secondary" target="_blank">Preview Draft</a>
                        <?php endif; ?>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this book? This cannot be undone.')">
                            <input type="hidden" name="book_id" value="<?= (int)$row['book_id'] ?>">
                            <button type="submit" name="delete_book"
                                    class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
