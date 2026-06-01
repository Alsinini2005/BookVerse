<?php
require_once "config/db.php";
include "includes/header.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo '<div class="alert alert-danger">Invalid book ID.</div>';
    include "includes/footer.php";
    exit();
}

$vstmt = $conn->prepare("UPDATE dbproj_books SET views = views + 1 WHERE book_id = ?");
$vstmt->bind_param("i", $id);
$vstmt->execute();

$sql  = "SELECT b.*
         FROM dbproj_books b
         WHERE b.book_id = ? AND b.status = 'published'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    echo '
    <div class="text-center py-5">
        <div class="mb-4" style="font-size:4rem;">📭</div>
        <h2 class="text-muted">Content Unavailable</h2>
        <p class="text-muted">This content has been removed or is no longer available.<br>
        It may have been taken down by an administrator.</p>
        <a href="' . BASE_URL . '/index.php" class="btn btn-dark mt-3">← Back to Books</a>
    </div>';
    include "includes/footer.php";
    exit();
}

$commentError   = '';
$commentSuccess = '';
if (isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $commentError = "Please login to comment.";
    } else {
        $commentText = trim($_POST['comment_text'] ?? '');
        if (empty($commentText)) {
            $commentError = "Comment cannot be empty.";
        } elseif (strlen($commentText) > 1000) {
            $commentError = "Comment must be under 1000 characters.";
        } else {
            $uid   = (int)$_SESSION['user_id'];
            $csql  = "INSERT INTO dbproj_comments (book_id, user_id, comment_text) VALUES (?, ?, ?)";
            $cstmt = $conn->prepare($csql);
            $cstmt->bind_param("iis", $id, $uid, $commentText);
            $cstmt->execute();
            $commentSuccess = "Comment added!";
        }
    }
}

$ratingMsg = '';
if (isset($_POST['rate'])) {
    if (!isset($_SESSION['user_id'])) {
        $ratingMsg = "Please login to rate.";
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $uid    = (int)$_SESSION['user_id'];
        if ($rating >= 1 && $rating <= 5) {
            $rsql  = "INSERT INTO dbproj_ratings (book_id, user_id, rating_value)
                      VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE rating_value = ?";
            $rstmt = $conn->prepare($rsql);
            $rstmt->bind_param("iiii", $id, $uid, $rating, $rating);
            $rstmt->execute();
            $ratingMsg = "Rating saved!";
        }
    }
}

$avgSql  = "SELECT ROUND(AVG(rating_value),1) AS avg_r, COUNT(*) AS total FROM dbproj_ratings WHERE book_id = ?";
$avgStmt = $conn->prepare($avgSql);
$avgStmt->bind_param("i", $id);
$avgStmt->execute();
$ratingRow = $avgStmt->get_result()->fetch_assoc();

$userRating = 0;
if (isset($_SESSION['user_id'])) {
    $urSql  = "SELECT rating_value FROM dbproj_ratings WHERE book_id = ? AND user_id = ?";
    $urStmt = $conn->prepare($urSql);
    $uid    = (int)$_SESSION['user_id'];
    $urStmt->bind_param("ii", $id, $uid);
    $urStmt->execute();
    $urRow      = $urStmt->get_result()->fetch_assoc();
    $userRating = $urRow ? (int)$urRow['rating_value'] : 0;
}
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <?php
        $imgPath     = BASE_URL . '/uploads/books/images/' . ($book['cover_image'] ?? '');
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
        </p>
        <p class="text-muted small">
            <?= date('F j, Y', strtotime($book['created_at'])) ?>
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

        <!-- RATING -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Rating</h5>
                <?php if ($ratingRow['total'] > 0): ?>
                    <p class="mb-2">
                        ⭐ <strong><?= htmlentities($ratingRow['avg_r']) ?></strong> / 5
                        (<?= (int)$ratingRow['total'] ?> ratings)
                    </p>
                <?php else: ?>
                    <p class="mb-2 text-muted">No ratings yet.</p>
                <?php endif; ?>

                <?php if (!empty($ratingMsg)): ?>
                    <div class="alert alert-success py-1"><?= htmlentities($ratingMsg) ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <select name="rating" class="form-select form-select-sm w-auto">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <option value="<?= htmlentities($s) ?>" <?= $userRating === $s ? 'selected' : '' ?>>
                                    <?= htmlentities($s) ?> ⭐
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" name="rate" class="btn btn-sm btn-dark">
                            <?= $userRating ? 'Update Rating' : 'Rate' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted small"><a href="<?= BASE_URL ?>/login.php">Login</a> to rate this book.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row">
    <div class="col-md-8">
        <h4>Comments</h4>

        <?php if (!empty($commentError)): ?>
            <div class="alert alert-danger"><?= htmlentities($commentError) ?></div>
        <?php endif; ?>
        <?php if (!empty($commentSuccess)): ?>
            <div class="alert alert-success"><?= htmlentities($commentSuccess) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="mb-4">
                <textarea id="commentBox" class="form-control mb-2" rows="3"
                          placeholder="Write a comment… (max 1000 characters)"
                          maxlength="1000"></textarea>
                <button class="btn btn-dark"
                        onclick="submitComment(<?= htmlentities($id) ?>)">
                    Post Comment
                </button>
                <div id="commentMsg" class="mt-2"></div>
            </div>
        <?php else: ?>
            <p><a href="<?= BASE_URL ?>/login.php">Login</a> to leave a comment.</p>
        <?php endif; ?>

        <div id="commentsList">
            <?php
            $csql2  = "SELECT c.*, u.full_name
                       FROM dbproj_comments c
                       JOIN dbproj_users u ON c.user_id = u.user_id
                       WHERE c.book_id = ?
                       ORDER BY c.created_at DESC";
            $cstmt2 = $conn->prepare($csql2);
            $cstmt2->bind_param("i", $id);
            $cstmt2->execute();
            $comments = $cstmt2->get_result();

            if ($comments->num_rows === 0):
            ?>
                <p class="text-muted" id="noComments">No comments yet. Be the first!</p>
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

<script>
var BOOK_ID  = <?= htmlentities($id) ?>;
var BASE_URL = '<?= BASE_URL ?>';
</script>

<?php include "includes/footer.php"; ?>
