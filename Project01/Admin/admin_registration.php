<?php
include "../db.php";

// Who may create an admin account:
//  - anyone, but only while no admin exists yet (first-time setup), or
//  - an admin who is already logged in.
$adminCount = (int)(db_one($conn, "SELECT COUNT(*) AS n FROM users WHERE role = 'admin'")['n'] ?? 0);
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

if ($adminCount > 0 && !$isAdmin) {
    header("Location: admin_login.php");
    exit();
}

$error = "";
$success = "";
$recoveryKey = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($email === "" || $password === "" || $confirm === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (($problem = password_problem($password)) !== "") {
        $error = $problem;
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (db_one($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1", [$email])) {
        $error = "This email is already registered.";
    } else {
        // Admins have no phone or name on file, so a one-time recovery key is how they prove
        // who they are on the "forgot password" page. Only its hash is stored.
        $recoveryKey = new_recovery_key();
        $ok = db_query($conn,
            "INSERT INTO users (email, password, role, recovery_hash, created_at) VALUES (?, ?, 'admin', ?, NOW())",
            [$email, password_hash($password, PASSWORD_DEFAULT), password_hash($recoveryKey, PASSWORD_DEFAULT)]);

        if ($ok) {
            $success = "Admin registration successful! You can login now.";
        } else {
            $recoveryKey = "";
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<?php auth_start($isAdmin ? 'Add an Admin' : 'Create the First Admin', $isAdmin ? 'Give another person admin access.' : 'This page closes once the first admin exists.', '../', 'shield'); ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-ok"><?php echo e($success); ?></div>
                <?php if ($recoveryKey): ?>
                    <div class="alert alert-gold">
                        <strong>Save this recovery key now.</strong> It is the only way to reset this admin's password
                        on the "Forgot password" page, and it will not be shown again.
                        <div class="key-box"><?php echo e($recoveryKey); ?></div>
                    </div>
                <?php endif; ?>
                <a class="btn-gold" href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'admin_login.php'; ?>">
                    <?php echo $isAdmin ? 'Back to dashboard' : 'Go to admin login'; ?>
                </a>
            <?php else: ?>
                <form method="POST" action="admin_registration.php">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="admin@example.com" required>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <span class="hint">8+ characters, letters and numbers.</span>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn-gold">Create admin</button>
                </form>
                <p class="auth-foot"><a href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'admin_login.php'; ?>">&larr; Back</a></p>
            <?php endif; ?>
<?php auth_end(); ?>
