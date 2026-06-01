<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/~u202301457/BookVerse');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#1b4332;">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">BookVerse</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu">
                        <?php foreach (['Programming','Database','Design','Technology','Security','Business'] as $cat): ?>
                            <li>
                                <a class="dropdown-item"
                                   href="<?= BASE_URL ?>/index.php?category=<?= urlencode($cat) ?>">
                                    <?= htmlentities($cat) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php">All Books</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            Hello, <?= htmlentities($_SESSION['name'] ?? '') ?>
                        </span>
                    </li>
                    <?php if ($_SESSION['role'] === 'creator'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/creator/dashboard.php">
                                Creator Panel
                            </a>
                        </li>

                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">
                                Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
