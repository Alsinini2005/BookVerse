<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/db.php";

$error   = "";
$success = "";

if (isset($_POST['register'])) {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($name) || strlen($name) < 2) {
        $error = "Full name must be at least 2 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $chk  = $conn->prepare("SELECT user_id FROM dbproj_users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "An account with that email already exists.";
        } else {
            $role = "visitor";

            $stmt = $conn->prepare(
                "INSERT INTO dbproj_users (full_name, email, password, role) VALUES (?, ?, AES_ENCRYPT(?, 'bv_key'), ?)"
            );
            $stmt->bind_param("ssss", $name, $email, $password, $role);

            if ($stmt->execute()) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

include "includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Register</h3>
            </div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlentities($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlentities($success) ?>
                        <a href="<?= BASE_URL ?>/login.php">Login now</a>
                    </div>
                <?php endif; ?>

                <form name="registerForm" method="POST" onsubmit="return validateRegister()">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= htmlentities($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlentities($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(min 8 characters)</small></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="register" class="btn btn-dark w-100">Create Account</button>
                </form>

                <p class="mt-3 text-center">
                    Already have an account?
                    <a href="<?= BASE_URL ?>/login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
