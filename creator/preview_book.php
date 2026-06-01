<?php

require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'creator') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$id         = (int)($_GET['id'] ?? 0);
$creator_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    include "../includes/header.php";
    echo '<div class="alert alert-danger">Invalid book ID.</div>';
    include "../includes/footer.php";
    exit();
}

$stmt = $conn->prepare(
    "SELECT b.*, u.full_name AS creator_name
     FROM dbproj_books b
     LEFT JOIN dbproj_users u ON b.creator_id = u.user_id
     WHERE b.book_id = ? AND b.creator_id = ?"
);
$stmt->bind_param("ii", $id, $creator_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    include "../includes/header.php";
    echo '<div class="alert alert-danger">Book not found or you do not have permission to preview it.</div>';
    echo '<a href="' . BASE_URL . '/creator/dashboard.php" class="btn btn-dark mt-2">← Back</a>';
    include "../includes/footer.php";
    exit();
}

$avgStmt = $conn->prepare(
    "SELECT ROUND(AVG(rating_value),1) AS avg_r, COUNT(*) AS total FROM dbproj_ratings WHERE book_id = ?"
);
$avgStmt->bind_param("i", $id);
$avgStmt->execute();
$ratingRow = $avgStmt->get_result()->fetch_assoc();

$cstmt = $conn->prepare(
    "SELECT c.*, u.full_name
     FROM dbproj_comments c
     JOIN dbproj_users u ON c.user_id = u.user_id
     WHERE c.book_id = ?
     ORDER BY c.created_at DESC"
);
$cstmt->bind_param("i", $id);
$cstmt->execute();
$comments = $cstmt->get_result();

include "../includes/header.php";
?>

<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
    <span style="font-size:1.3rem;">🔍</span>
    <div>
        <strong>Draft Preview</strong> — This is how your book will look once published.
        This page is only visible to you.
        <?php if ($book['status'] === 'draft'): ?>
            &nbsp;<a href="<?= BASE_URL ?>/creator/edit_book.php?id=<?= htmlentities($id) ?>"
                     class="btn btn-sm btn-warning ms-2">✏️ Continue Editing</a>
        <?php else: ?>
            &nbsp;<span class="badge bg-success ms-1">Published</span>
            <a href="<?= BASE_URL ?>/book-details.php?id=<?= htmlentities($id) ?>"
               class="btn btn-sm btn-outline-dark ms-2">View Live Page</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <?php
        $imgPath     = BASE_URL . '/uploads/books/images/' . $book['cover_image'] ?? '';
        $placeholder = 'https://placehold.co/300x400/dee2e6/6c757d?text=No+Cover';
        ?>
        <img src="<?= htmlentities($imgPath) ?>"
             class="img-fluid rounded shadow"
             alt="<?= htmlentities($book['title']) ?>"
             onerror="this.src='<?= htmlentities($placeholder) ?>'">
    </div>

    <div class="col-md-8">
        <h2><?= htmlentities($book['title']) ?></h2>
        <p class="text-muted">
            By <strong><?= htmlentities($book['author']) ?></strong>
            &bull; Category: <span class="badge bg-secondary"><?= htmlentities($book['category']) ?></span>
            &bull; <span class="badge <?= $book['status'] === 'published' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= htmlentities(ucfirst($book['status'])) ?>
                   </span>
        </p>
        <p class="text-muted small">
            Added by <?= htmlentities($book['creator_name'] ?? 'Unknown') ?>
            on <?= date('F j, Y', strtotime($book['created_at'])) ?>
            &bull; 👁 <?= (int)$book['views'] ?> views
        </p>
        <hr>
        <p><?= nl2br(htmlentities($book['description'])) ?></p>

        <?php if ($book['media_file']): ?>
            <a href="<?= BASE_URL ?>/uploads/books/media/<?= htmlentities($book['media_file']) ?>"
               class="btn btn-outline-dark mb-3" download>
                ⬇ Download Book PDF
            </a>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Rating <small class="text-muted fw-normal" style="font-size:.8rem;">(preview — rating disabled)</small></h5>
                <?php if ($ratingRow['total'] > 0): ?>
                    <p class="mb-0">⭐ <strong><?= htmlentities($ratingRow['avg_r']) ?></strong> / 5
                        (<?= (int)$ratingRow['total'] ?> ratings)</p>
                <?php else: ?>
                    <p class="mb-0 text-muted">No ratings yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row">
    <div class="col-md-8">
        <h4>Comments <small class="text-muted fw-normal" style="font-size:.9rem;">(preview — submission disabled)</small></h4>
        <div id="commentsList" class="mt-3">
            <?php if ($comments->num_rows === 0): ?>
                <p class="text-muted">No comments yet.</p>
            <?php else: ?>
                <?php while ($row = $comments->fetch_assoc()): ?>
                    <div class="card mb-2">
                        <div class="card-body py-2">
                            <strong><?= htmlentities($row['full_name']) ?></strong>
                            <small class="text-muted ms-2">
                                <?= date('M j, Y g:i a', strtotime($row['created_at'])) ?>
                            </small>
                            <p class="mb-0 mt-1"><?= nl2br(htmlentities($row['comment_text'])) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
