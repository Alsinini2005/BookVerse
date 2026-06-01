<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $del_id = (int)($_POST['book_id'] ?? 0);
    $stmt   = $conn->prepare("DELETE FROM dbproj_books WHERE book_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $successMsg = "Book has been removed from the system.";
}

// ── EDIT (admin bypasses creator_id check) ───────────────────────────────────
$editBook = null;
$editId   = 0;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $estmt  = $conn->prepare("SELECT * FROM dbproj_books WHERE book_id = ?");
    $estmt->bind_param("i", $editId);
    $estmt->execute();
    $editBook = $estmt->get_result()->fetch_assoc();
    if (!$editBook) { $errorMsg = "Book not found."; $editId = 0; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $editId      = (int)($_POST['book_id'] ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';

    // Re-fetch current book for fallback values
    $fstmt = $conn->prepare("SELECT * FROM dbproj_books WHERE book_id = ?");
    $fstmt->bind_param("i", $editId);
    $fstmt->execute();
    $editBook = $fstmt->get_result()->fetch_assoc();

    if (empty($title))                 { $errorMsg = "Title is required."; }
    elseif (empty($author))            { $errorMsg = "Author is required."; }
    elseif (empty($category))          { $errorMsg = "Category is required."; }
    elseif (strlen($description) < 10) { $errorMsg = "Description must be at least 10 characters."; }

    if (empty($errorMsg)) {
        $newImage = $editBook['cover_image'];
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $imgExt = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($imgExt, ['jpg','jpeg','png','gif']) && $_FILES['cover_image']['size'] <= 1000000) {
                $newImage = uniqid('img_') . '.' . $imgExt;
                if (!move_uploaded_file($_FILES['cover_image']['tmp_name'],
                    "/home/u202301457/public_html/BookVerse/uploads/books/images/" . $newImage)) {
                    $errorMsg = "Failed to upload cover image.";
                    $newImage = $editBook['cover_image'];
                }
            } else {
                $errorMsg = "Invalid image type or size (max 1MB, JPG/PNG/GIF).";
            }
        }

        $newMedia = $editBook['media_file'];
        if (empty($errorMsg) && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
            $medName = $_FILES['media_file']['name'];
            $medTmp  = $_FILES['media_file']['tmp_name'];
            $medSize = $_FILES['media_file']['size'];
            $medExt  = strtolower(pathinfo($medName, PATHINFO_EXTENSION));
            $allowedMed = ['pdf', 'mp3', 'mp4', 'ogg', 'wav'];

            if (!in_array($medExt, $allowedMed)) {
                $errorMsg = "Invalid file type. Only PDF, MP3, MP4, OGG, WAV allowed.";
            } elseif ($medSize > 1000000) {
                $errorMsg = "File is too large. Maximum size is 1MB.";
            } else {
                $newMedia = uniqid('med_') . '.' . $medExt;
                if (!move_uploaded_file($medTmp,
                    "/home/u202301457/public_html/BookVerse/uploads/books/media/" . $newMedia)) {
                    $errorMsg = "Failed to upload media file.";
                    $newMedia = $editBook['media_file'];
                }
            }
        }
    }

    if (empty($errorMsg)) {
        $usql  = "UPDATE dbproj_books
                  SET title=?, author=?, category=?, description=?, cover_image=?, media_file=?, status=?
                  WHERE book_id=?";
        $ustmt = $conn->prepare($usql);
        $ustmt->bind_param("sssssssi",
            $title, $author, $category, $description,
            $newImage, $newMedia, $status, $editId
        );
        if ($ustmt->execute()) {
            $successMsg = "Book updated successfully!";
            $editBook   = null;
            $editId     = 0;
        } else {
            $errorMsg = "Update failed. Please try again.";
        }
    }
}

// ── LIST ────────────────────────────────────────────────────────────────────
$result = $conn->query(
    "SELECT b.*, u.full_name AS creator_name
     FROM dbproj_books b
     LEFT JOIN dbproj_users u ON b.creator_id = u.user_id
     ORDER BY b.created_at DESC"
);

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📚 Manage Books</h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if ($successMsg): ?><div class="alert alert-warning">⚠️ <?= htmlentities($successMsg) ?></div><?php endif; ?>
<?php if ($errorMsg):   ?><div class="alert alert-danger"><?= htmlentities($errorMsg) ?></div><?php endif; ?>

<?php if ($editBook): ?>
<!-- ── EDIT FORM ──────────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">✏️ Edit Book: <?= htmlentities($editBook['title']) ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="book_id" value="<?= (int)$editBook['book_id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" class="form-control"
                           value="<?= htmlentities($editBook['title']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-control"
                           value="<?= htmlentities($editBook['author']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <?php foreach (['Programming','Database','Design','Technology','Security','Business'] as $cat): ?>
                            <option value="<?= htmlentities($cat) ?>"
                                <?= $editBook['category'] === $cat ? 'selected' : '' ?>>
                                <?= htmlentities($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft"     <?= $editBook['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $editBook['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="4" required><?= htmlentities($editBook['description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Replace Cover Image
                        <small class="text-muted">(JPG/PNG/GIF · max 1MB · leave blank to keep current)</small>
                    </label>
                    <?php if ($editBook['cover_image']): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL ?>/uploads/books/images/<?= htmlentities($editBook['cover_image']) ?>"
                                 height="80" class="rounded shadow-sm" alt="Current cover"
                                 onerror="this.style.display='none'">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Replace Media File
                        <small class="text-muted">(PDF/MP3/MP4/OGG/WAV · max 1MB · leave blank to keep current)</small>
                    </label>
                    <?php if ($editBook['media_file']): ?>
                        <p class="mb-1 small text-muted">Current: <strong><?= htmlentities($editBook['media_file']) ?></strong></p>
                    <?php endif; ?>
                    <input type="file" name="media_file" class="form-control" accept=".pdf,.mp3,.mp4,.ogg,.wav">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" name="update_book" class="btn btn-dark">Update Book</button>
                <a href="<?= BASE_URL ?>/admin/manage-books.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── BOOK TABLE ─────────────────────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th><th>Title</th><th>Author</th><th>Category</th>
                    <th>Creator</th><th>Status</th><th>Views</th><th>Added</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['book_id'] ?></td>
                    <td><?= htmlentities($row['title']) ?></td>
                    <td><?= htmlentities($row['author']) ?></td>
                    <td><?= htmlentities($row['category']) ?></td>
                    <td><?= htmlentities($row['creator_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $row['status']==='published'?'bg-success':'bg-warning text-dark' ?>">
                            <?= htmlentities($row['status']) ?>
                        </span>
                    </td>
                    <td><?= (int)$row['views'] ?></td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="d-flex gap-1 flex-wrap">
                        <a href="?edit=<?= (int)$row['book_id'] ?>"
                           class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Remove: <?= addslashes(htmlentities($row['title'])) ?>?')">
                            <input type="hidden" name="book_id" value="<?= (int)$row['book_id'] ?>">
                            <button type="submit" name="delete_book"
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
