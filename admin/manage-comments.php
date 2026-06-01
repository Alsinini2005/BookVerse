<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $del_id = (int)($_POST['comment_id'] ?? 0);
    $stmt   = $conn->prepare("DELETE FROM dbproj_comments WHERE comment_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $successMsg = "Comment has been removed by the administrator.";
}

$result = $conn->query(
    "SELECT c.comment_id, c.comment_text, c.created_at,
            u.full_name AS commenter,
            b.title AS book_title, b.book_id
     FROM dbproj_comments c
     JOIN dbproj_users u ON c.user_id = u.user_id
     JOIN dbproj_books b ON c.book_id = b.book_id
     ORDER BY c.created_at DESC"
);

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>💬 Manage Comments</h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if ($successMsg): ?><div class="alert alert-warning">⚠️ <?= htmlentities($successMsg) ?></div><?php endif; ?>
<?php if ($errorMsg):   ?><div class="alert alert-danger"><?= htmlentities($errorMsg) ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-dark">
                <tr><th>#</th><th>Comment</th><th>Posted by</th><th>Book</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['comment_id'] ?></td>
                    <td><?= htmlentities(substr($row['comment_text'], 0, 100)) ?>…</td>
                    <td><?= htmlentities($row['commenter']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/book-details.php?id=<?= (int)$row['book_id'] ?>">
                            <?= htmlentities($row['book_title']) ?>
                        </a>
                    </td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Remove this comment?')">
                            <input type="hidden" name="comment_id" value="<?= (int)$row['comment_id'] ?>">
                            <button type="submit" name="delete_comment"
                                    class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
