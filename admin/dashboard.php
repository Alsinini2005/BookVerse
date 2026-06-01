<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$users    = $conn->query("SELECT COUNT(*) AS total FROM dbproj_users")->fetch_assoc();
$books    = $conn->query("SELECT COUNT(*) AS total FROM dbproj_books")->fetch_assoc();
$comments = $conn->query("SELECT COUNT(*) AS total FROM dbproj_comments")->fetch_assoc();
$published = $conn->query("SELECT COUNT(*) AS total FROM dbproj_books WHERE status='published'")->fetch_assoc();

include "../includes/header.php";
?>

<h2 class="mb-4">Admin Dashboard</h2>

<!-- Stats -->
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-dark text-white">
            <div class="card-body">
                <h3><?= (int)$users['total'] ?></h3>
                <p class="mb-0">Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$books['total'] ?></h3>
                <p class="text-muted mb-0">Total Books</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$published['total'] ?></h3>
                <p class="text-muted mb-0">Published</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h3><?= (int)$comments['total'] ?></h3>
                <p class="text-muted mb-0">Comments</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/admin/manage-users.php" class="btn btn-outline-dark w-100 py-3">
            👥 Manage Users
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/admin/manage-books.php" class="btn btn-outline-dark w-100 py-3">
            📚 Manage Books
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/admin/manage-comments.php" class="btn btn-outline-dark w-100 py-3">
            💬 Manage Comments
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-dark w-100 py-3">
            📊 Reports
        </a>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
