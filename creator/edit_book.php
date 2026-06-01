<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    require_once "../config/db.php";
    include "../includes/header.php";
    echo '<div class="alert alert-danger">The file you uploaded is too large. Maximum allowed size is 1MB.</div>';
    echo '<a href="javascript:history.back()" class="btn btn-dark">← Try Again</a>';
    include "../includes/footer.php";
    exit();
}
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'creator') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$id         = (int)($_GET['id'] ?? 0);
$creator_id = (int)$_SESSION['user_id'];

function fetchBook($conn, $book_id, $creator_id) {
    $stmt = $conn->prepare("SELECT * FROM dbproj_books WHERE book_id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $book_id, $creator_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$book = fetchBook($conn, $id, $creator_id);

if (!$book) {
    include "../includes/header.php";
    echo '<div class="alert alert-danger">Book not found or you do not have permission to edit it.</div>';
    echo '<a href="' . BASE_URL . '/creator/dashboard.php" class="btn btn-dark">← Back</a>';
    include "../includes/footer.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unpublish'])) {
    $ustmt = $conn->prepare("UPDATE dbproj_books SET status = 'draft' WHERE book_id = ? AND creator_id = ?");
    $ustmt->bind_param("ii", $id, $creator_id);
    $ustmt->execute();
    header("Location: " . BASE_URL . "/creator/edit_book.php?id=$id&msg=unpublished");
    exit();
}

if ($book['status'] === 'published') {
    include "../includes/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Book</h2>
    <a href="<?= BASE_URL ?>/creator/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>
<div class="alert alert-warning d-flex align-items-start gap-3">
    <span style="font-size:1.6rem;">🔒</span>
    <div>
        <h5 class="alert-heading mb-1">This book is published and cannot be edited directly.</h5>
        <p class="mb-2">To make changes, unpublish the book first — it will be hidden from public view while you edit.</p>
        <form method="POST" class="d-inline"
              onsubmit="return confirm('Unpublish this book?')">
            <button type="submit" name="unpublish" class="btn btn-warning btn-sm fw-semibold">
                ↩ Unpublish &amp; Edit
            </button>
        </form>
        <a href="<?= BASE_URL ?>/book-details.php?id=<?= htmlentities($id) ?>" target="_blank"
           class="btn btn-outline-dark btn-sm ms-2">👁 View Live</a>
    </div>
</div>
<?php
    include "../includes/footer.php";
    exit();
}

$error   = '';
$success = '';
$urlMsg  = $_GET['msg'] ?? '';

if (isset($_POST['update'])) {
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';

    if (empty($title))                 { $error = "Title is required."; }
    elseif (empty($author))            { $error = "Author is required."; }
    elseif (empty($category))          { $error = "Category is required."; }
    elseif (strlen($description) < 10) { $error = "Description must be at least 10 characters."; }

    // Optional new cover image
    $newImage = $book['cover_image'];
    if (empty($error) && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $imgName = $_FILES['cover_image']['name'];
        $imgTmp  = $_FILES['cover_image']['tmp_name'];
        $imgSize = $_FILES['cover_image']['size'];
        $imgExt  = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
        $allowedImg = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imgExt, $allowedImg)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
        } elseif ($imgSize > 1000000) {
            $error = "File is too large. Maximum size is 1MB.";
        } else {
            $newImage = uniqid('img_') . '.' . $imgExt;
            if (!move_uploaded_file($imgTmp, "/home/u202301457/public_html/BookVerse/uploads/books/images/" . $newImage)) {
                $error    = "Failed to upload cover image.";
                $newImage = $book['cover_image'];
            }
        }
    }

    $newMedia = $book['media_file'];
    if (empty($error) && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
        $medName = $_FILES['media_file']['name'];
        $medTmp  = $_FILES['media_file']['tmp_name'];
        $medSize = $_FILES['media_file']['size'];
        $medExt  = strtolower(pathinfo($medName, PATHINFO_EXTENSION));
        $allowedMed = ['pdf', 'mp3', 'mp4', 'ogg', 'wav'];

        if (!in_array($medExt, $allowedMed)) {
            $error = "Invalid file type. Only PDF, MP3, MP4, OGG, WAV allowed.";
        } elseif ($medSize > 1000000) {
            $error = "File is too large. Maximum size is 1MB.";
        } else {
            $newMedia = uniqid('med_') . '.' . $medExt;
            if (!move_uploaded_file($medTmp, "/home/u202301457/public_html/BookVerse/uploads/books/media/" . $newMedia)) {
                $error    = "Failed to upload media file.";
                $newMedia = $book['media_file'];
            }
        }
    }

    if (empty($error)) {
        $usql  = "UPDATE dbproj_books
                  SET title=?, author=?, category=?, description=?, cover_image=?, media_file=?, status=?
                  WHERE book_id=? AND creator_id=?";
        $ustmt = $conn->prepare($usql);
        $ustmt->bind_param("sssssssii",
            $title, $author, $category, $description,
            $newImage, $newMedia, $status, $id, $creator_id
        );

        if ($ustmt->execute()) {
            $success = "Book updated successfully!";
            $book    = fetchBook($conn, $id, $creator_id);

            if ($status === 'published') {
                header("Location: " . BASE_URL . "/creator/dashboard.php?msg=updated");
                exit();
            }
        } else {
            $error = "Update failed. Please try again.";
        }
    }
}

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Book <span class="badge bg-warning text-dark ms-2" style="font-size:.75rem;vertical-align:middle;">Draft</span></h2>
    <a href="<?= BASE_URL ?>/creator/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if ($urlMsg === 'unpublished'): ?>
    <div class="alert alert-info">
        📝 Book moved back to <strong>Draft</strong>. Make your changes, then re-publish.
    </div>
<?php endif; ?>

<?php if ($error):   ?><div class="alert alert-danger"><?= htmlentities($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlentities($success) ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data"
              name="addBookForm" onsubmit="return validateBookForm()">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Book Title *</label>
                    <input type="text" id="bookTitle" name="title" class="form-control"
                           value="<?= htmlentities($book['title']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Author *</label>
                    <input type="text" id="bookAuthor" name="author" class="form-control"
                           value="<?= htmlentities($book['author']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <?php foreach (['Programming','Database','Design','Technology','Security','Business'] as $cat): ?>
                            <option value="<?= htmlentities($cat) ?>"
                                <?= $book['category'] === $cat ? 'selected' : '' ?>>
                                <?= htmlentities($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft"     <?= $book['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $book['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea id="bookDesc" name="description" class="form-control"
                              rows="4" required><?= htmlentities($book['description']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Replace Cover Image
                        <small class="text-muted">(JPG/PNG/GIF · max 1MB · leave blank to keep current)</small>
                    </label>
                    <?php if ($book['cover_image']): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL ?>/uploads/books/images/<?= htmlentities($book['cover_image']) ?>"
                                 height="80" class="rounded shadow-sm" alt="Current cover"
                                 onerror="this.style.display='none'">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control"
                           accept=".jpg,.jpeg,.png,.gif">
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Replace Media File
                        <small class="text-muted">(PDF/MP3/MP4/OGG/WAV · max 1MB · leave blank to keep current)</small>
                    </label>
                    <?php if ($book['media_file']): ?>
                        <p class="mb-1 small text-muted">
                            Current: <strong><?= htmlentities($book['media_file']) ?></strong>
                        </p>
                    <?php endif; ?>
                    <input type="file" name="media_file" class="form-control"
                           accept=".pdf,.mp3,.mp4,.ogg,.wav">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" name="update" class="btn btn-dark">Update Book</button>
                <a href="<?= BASE_URL ?>/creator/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                <a href="<?= BASE_URL ?>/creator/preview_book.php?id=<?= htmlentities($id) ?>"
                   target="_blank" class="btn btn-outline-secondary ms-auto">
                    👁 Preview Draft
                </a>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
