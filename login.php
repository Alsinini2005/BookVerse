<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/db.php";

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: " . BASE_URL . ($role === 'admin' ? "/admin/dashboard.php" : ($role === 'creator' ? "/creator/dashboard.php" : "/index.php")));
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM dbproj_users WHERE email = ? AND AES_DECRYPT(password, 'bv_key') = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['full_name'];
            $_SESSION['role']    = $user['role'];
            $role = $user['role'];
            header("Location: " . BASE_URL . ($role === 'admin' ? "/admin/dashboard.php" : ($role === 'creator' ? "/creator/dashboard.php" : "/index.php")));
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}

include "includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow auth-card">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">🔐 Login</h3>
            </div>
            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlentities($error) ?></div>
                <?php endif; ?>

                <form name="loginForm" method="POST" onsubmit="return validateLogin()">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlentities($_POST['email'] ?? '') ?>"
                               placeholder="your@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Min. 8 characters" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2">Login</button>
                </form>

                <hr>
                <p class="text-center mb-0 text-muted small">
                    Don't have an account?
                    <a href="<?= BASE_URL ?>/register.php" class="text-dark fw-semibold">Register here</a>
                </p>


            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
