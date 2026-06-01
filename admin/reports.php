<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$rep1_from = trim($_GET['rep1_from'] ?? '');
$rep1_to   = trim($_GET['rep1_to']   ?? '');

$rep2_creator = (int)($_GET['creator_id'] ?? 0);

$r1Where  = ["1=1"];
$r1Params = [];
$r1Types  = '';

if ($rep1_from !== '') {
    $r1Where[]  = "pb.book_id IN (SELECT book_id FROM dbproj_books WHERE created_at >= ?)";
    $r1Params[] = $rep1_from . ' 00:00:00';
    $r1Types   .= 's';
}
if ($rep1_to !== '') {
    $r1Where[]  = "pb.book_id IN (SELECT book_id FROM dbproj_books WHERE created_at <= ?)";
    $r1Params[] = $rep1_to . ' 23:59:59';
    $r1Types   .= 's';
}
$r1WhereSQL = implode(' AND ', $r1Where);

$r1SQL  = "SELECT pb.book_id, pb.title, pb.category, pb.views,
                  pb.creator_name,
                  pb.average_rating AS avg_rating,
                  pb.total_ratings,
                  b.author
           FROM popular_books pb
           JOIN dbproj_books b ON pb.book_id = b.book_id
           WHERE $r1WhereSQL
           ORDER BY pb.views DESC
           LIMIT 20";

$r1stmt = $conn->prepare($r1SQL);
if (!empty($r1Params)) {
    $r1stmt->bind_param($r1Types, ...$r1Params);
}
$r1stmt->execute();
$pop_books = $r1stmt->get_result();

$creators = $conn->query(
    "SELECT u.user_id, u.full_name
     FROM dbproj_users u
     WHERE u.role = 'creator'
     ORDER BY u.full_name"
);

$creator_books = null;
$selectedCreator = null;
if ($rep2_creator > 0) {
    $r2stmt = $conn->prepare("CALL GetBooksByCreator(?)");
    $r2stmt->bind_param("i", $rep2_creator);
    $r2stmt->execute();
    $creator_books = $r2stmt->get_result();

    $creator_books_data = $creator_books->fetch_all(MYSQLI_ASSOC);
    $r2stmt->close();
    while ($conn->more_results()) {
        $conn->next_result();
    }

    $cnStmt = $conn->prepare("SELECT full_name FROM dbproj_users WHERE user_id = ?");
    $cnStmt->bind_param("i", $rep2_creator);
    $cnStmt->execute();
    $selectedCreator = $cnStmt->get_result()->fetch_assoc()['full_name'] ?? '';
}

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Reports</h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-dark text-white">
        📊 Report 1: Most Popular Books
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label small mb-1">From date</label>
                <input type="date" name="rep1_from" class="form-control"
                       value="<?= htmlentities($rep1_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">To date</label>
                <input type="date" name="rep1_to" class="form-control"
                       value="<?= htmlentities($rep1_to) ?>">
            </div>
            <input type="hidden" name="creator_id" value="<?= htmlentities($rep2_creator) ?>">
            <div class="col-md-2">
                <button class="btn btn-dark w-100">Filter</button>
            </div>
            <?php if ($rep1_from || $rep1_to): ?>
                <div class="col-md-2">
                    <a href="?creator_id=<?= htmlentities($rep2_creator) ?>" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            <?php endif; ?>
        </form>

        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Creator</th>
                    <th>Views</th>
                    <th>Avg Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; while ($row = $pop_books->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/book-details.php?id=<?= (int)$row['book_id'] ?>">
                            <?= htmlentities($row['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlentities($row['author']) ?></td>
                    <td><?= htmlentities($row['category']) ?></td>
                    <td><?= htmlentities($row['creator_name']) ?></td>
                    <td><strong><?= (int)$row['views'] ?></strong></td>
                    <td><?= $row['avg_rating'] ? $row['avg_rating'] . ' ⭐' : '—' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white">
        👤 Report 2: Books by Specific Creator
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="rep1_from" value="<?= htmlentities($rep1_from) ?>">
            <input type="hidden" name="rep1_to"   value="<?= htmlentities($rep1_to) ?>">
            <div class="col-md-4">
                <label class="form-label small mb-1">Select Creator</label>
                <select name="creator_id" class="form-select">
                    <option value="0">-- All Creators --</option>
                    <?php $creators->data_seek(0); while ($c = $creators->fetch_assoc()): ?>
                        <option value="<?= (int)$c['user_id'] ?>"
                                <?= $rep2_creator === (int)$c['user_id'] ? 'selected' : '' ?>>
                            <?= htmlentities($c['full_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100">View</button>
            </div>
        </form>

        <?php if (isset($creator_books_data)): ?>
            <h5>Books by: <strong><?= htmlentities($selectedCreator) ?></strong></h5>
            <table class="table table-striped mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($creator_books_data)): ?>
                        <tr><td colspan="5" class="text-muted text-center">No books found for this creator.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($creator_books_data as $row): ?>
                    <tr>
                        <td><?= htmlentities($row['title']) ?></td>
                        <td><?= htmlentities($row['category']) ?></td>
                        <td>
                            <span class="badge <?= $row['status']==='published'?'bg-success':'bg-warning text-dark' ?>">
                                <?= htmlentities($row['status']) ?>
                            </span>
                        </td>
                        <td><?= (int)$row['views'] ?></td>
                        <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Select a creator above to view their books.</p>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
