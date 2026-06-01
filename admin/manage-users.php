<?php
require_once "../config/db.php";
include "../includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $del_id = (int)($_POST['user_id'] ?? 0);
    if ($del_id === (int)$_SESSION['user_id']) {
        $errorMsg = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM dbproj_users WHERE user_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $successMsg = "User deleted successfully.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $rid     = (int)($_POST['user_id'] ?? 0);
    $newRole = in_array($_POST['role'] ?? '', ['admin','creator','visitor']) ? $_POST['role'] : 'visitor';
    if ($rid === (int)$_SESSION['user_id'] && $newRole !== 'admin') {
        $errorMsg = "You cannot remove your own admin role.";
    } else {
        $stmt = $conn->prepare("UPDATE dbproj_users SET role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $newRole, $rid);
        $stmt->execute();
        $successMsg = "Role updated to '$newRole'.";
    }
}

$result = $conn->query("SELECT * FROM dbproj_users ORDER BY created_at DESC");

include "../includes/header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>👥 Manage Users</h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<?php if ($successMsg): ?><div class="alert alert-success"><?= htmlentities($successMsg) ?></div><?php endif; ?>
<?php if ($errorMsg):   ?><div class="alert alert-danger"><?= htmlentities($errorMsg) ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-dark">
                <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Registered</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['user_id'] ?></td>
                    <td><?= htmlentities($row['full_name']) ?></td>
                    <td><?= htmlentities($row['email']) ?></td>
                    <td>
                        <span class="badge <?= $row['role']==='admin'?'bg-danger':($row['role']==='creator'?'bg-primary':'bg-secondary') ?>">
                            <?= htmlentities($row['role']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="d-flex gap-1 flex-wrap">
                        <form method="POST" class="d-inline-flex gap-1">
                            <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                            <select name="role" class="form-select form-select-sm" style="width:110px">
                                <option value="visitor" <?= $row['role']==='visitor'?'selected':'' ?>>Visitor</option>
                                <option value="creator" <?= $row['role']==='creator'?'selected':'' ?>>Creator</option>
                                <option value="admin"   <?= $row['role']==='admin'  ?'selected':'' ?>>Admin</option>
                            </select>
                            <button type="submit" name="change_role"
                                    class="btn btn-sm btn-outline-primary">Save</button>
                        </form>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Delete <?= addslashes(htmlentities($row['full_name'])) ?>?')">
                            <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                            <button type="submit" name="delete_user"
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
