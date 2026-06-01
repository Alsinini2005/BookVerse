<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    require_once "../config/db.php";
    include "../includes/header.php";
    echo '<div class="alert alert-danger">The file you uploaded is too large. Maximum allowed size is 1MB. Please choose a smaller file.</div>';
    echo '<a href="' . (defined("BASE_URL") ? BASE_URL : "/~u202301457/BookVerse") . '/creator/add_book.php" class="btn btn-dark">← Try Again</a>';
    include "../includes/footer.php";
    exit();
}
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'creator') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$error = '';

if (isset($_POST['add'])) {
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $creator_id  = (int)$_SESSION['user_id'];

    // Server-side validation
    if (empty($title))                 { $error = "Title is required."; }
    elseif (empty($author))            { $error = "Author is required."; }
    elseif (empty($category))          { $error = "Category is required."; }
    elseif (strlen($description) < 10) { $error = "Description must be at least 10 characters."; }

    $image = '';
    if (empty($error) && isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $fileName    = $_FILES['cover_image']['name'];
        $fileTmpName = $_FILES['cover_image']['tmp_name'];
        $fileSize    = $_FILES['cover_image']['size'];
        $fileError   = $_FILES['cover_image']['error'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt  = ['jpg', 'jpeg', 'png', 'gif'];

        if ($fileError !== 0) {
            $error = "Error uploading file. Code: " . $fileError;
        } elseif (!in_array($fileExt, $allowedExt)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
        } elseif ($fileSize > 1000000) {
            $error = "File is too large. Maximum size is 1MB.";
        } else {
            $image  = $fileName;
            $dest   = "/home/u202301457/public_html/BookVerse/uploads/books/images/" . $image;
            if (move_uploaded_file($fileTmpName, $dest)) {
                // uploaded successfully
            } else {
                $error = "Failed to move uploaded file. Check folder permissions.";
                $image = '';
            }
        }
    }

    $media = '';
    if (empty($error) && !empty($_FILES['media_file']['name'])) {
        $mFileName    = $_FILES['media_file']['name'];
        $mFileTmpName = $_FILES['media_file']['tmp_name'];
        $mFileSize    = $_FILES['media_file']['size'];
        $allowedMed   = ['pdf', 'mp3', 'mp4', 'ogg', 'wav'];
        $medExt       = strtolower(pathinfo($mFileName, PATHINFO_EXTENSION));
        if (!in_array($medExt, $allowedMed)) {
            $error = "Media file must be PDF, MP3, MP4, OGG, or WAV.";
        } elseif ($mFileSize > 1000000) {
            $error = "Media file must be under 1MB.";
        } else {
            $media = uniqid('med_') . '.' . $medExt;
            $dest  = "/home/u202301457/public_html/BookVerse/uploads/books/media/" . $media;
            if (move_uploaded_file($mFileTmpName, $dest)) {
                // uploaded successfully
            } else {
                $error = "Failed to move uploaded file. Check folder permissions.";
                $media = '';
            }
        }
    }

    if (empty($error)) {
        $sql  = "INSERT INTO dbproj_books
                    (title, author, category, description, cover_image, media_file, creator_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssis",
            $title, $author, $category, $description,
            $image, $media, $creator_id, $status
        );
        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "/creator/dashboard.php?msg=added");
            exit();
        } else {
            $error = "Failed to save book. Please try again.";
        }
    }
}

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Book</h2>
    <a href="<?= BASE_URL ?>/creator/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlentities($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data"
              name="addBookForm" onsubmit="return validateBookForm()">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Book Title *</label>
                    <input type="text" id="bookTitle" name="title" class="form-control"
                           value="<?= htmlentities($_POST['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Author *</label>
                    <input type="text" id="bookAuthor" name="author" class="form-control"
                           value="<?= htmlentities($_POST['author'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach (['Programming','Database','Design','Technology','Security','Business'] as $cat): ?>
                            <option value="<?= htmlentities($cat) ?>"
                                <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= htmlentities($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft"     <?= ($_POST['status'] ?? 'draft') !== 'published' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description * <small class="text-muted">(min 10 characters)</small></label>
                    <textarea id="bookDesc" name="description" class="form-control"
                              rows="4" required><?= htmlentities($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cover Image <small class="text-muted">(JPG/PNG/GIF/WEBP)</small></label>
                    <input type="file" name="cover_image" class="form-control"
                           accept=".jpg,.jpeg,.png,.gif,.webp">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Media File <small class="text-muted">(PDF/MP3/MP4/OGG/WAV · max 1MB)</small></label>
                    <input type="file" name="media_file" class="form-control"
                           accept=".pdf,.mp3,.mp4,.ogg,.wav">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" name="add" class="btn btn-dark">Save Book</button>
                <a href="<?= BASE_URL ?>/creator/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>



<?php include "../includes/footer.php"; ?>
