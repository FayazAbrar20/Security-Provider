<?php
include "../db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email and Password are required.";
    } else {
        $user = db_one($conn,
            "SELECT user_id, email, password, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1",
            [$email]);

        if ($user && password_verify($password, $user["password"])) {
            login_user($user);
            header("Location: admin_dashboard.php");
            exit();
        }
        $error = "Invalid admin email or password.";
    }
}
?>
<?php auth_start('Admin Login', 'Verify guards and keep the platform safe.', '../', 'shield'); ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['reset'])): ?>
                <div class="alert alert-ok">Password updated. You can log in now.</div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@example.com" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Your password" required>
                </div>
                <div class="row-between">
                    <span></span>
                    <a href="../forgot_password.php?role=admin">Forgot password?</a>
                </div>
                <button type="submit" class="btn-gold">Log in</button>
            </form>
<?php auth_end(); ?>
