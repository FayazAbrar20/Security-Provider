<?php
include "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: client_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$currentErr = $newErr = $confirmErr = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    $current = $_POST["current_password"] ?? "";
    $new = $_POST["new_password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    $user = db_one($conn, "SELECT password FROM users WHERE user_id = ?", [$user_id]);

    if (!$user || !password_verify($current, $user['password'])) {
        $currentErr = "Current password is incorrect";
        $valid = false;
    }

    if (($problem = password_problem($new)) !== "") {
        $newErr = $problem;
        $valid = false;
    }

    if ($new !== $confirm) {
        $confirmErr = "Passwords do not match";
        $valid = false;
    }

    if ($valid) {
        if (db_query($conn, "UPDATE users SET password = ? WHERE user_id = ?",
                     [password_hash($new, PASSWORD_DEFAULT), $user_id])) {
            // Sign out every remembered device after a password change.
            db_query($conn, "DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
            $success = "Password changed successfully!";
        }
    }
}
?>
<?php auth_start('Change Password', 'Use at least 8 characters with letters and numbers.', '../', 'lock'); ?>

            <?php if ($success): ?>
                <div class="alert alert-ok"><?php echo e($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <span class="error"><?php echo e($currentErr); ?></span>
                </div>
                <div class="field">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <span class="error"><?php echo e($newErr); ?></span>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="error"><?php echo e($confirmErr); ?></span>
                </div>
                <button type="submit" class="btn-gold">Update password</button>
            </form>

            <p class="auth-foot"><a href="client_dashboard.php">&larr; Back to dashboard</a></p>
<?php auth_end(); ?>
