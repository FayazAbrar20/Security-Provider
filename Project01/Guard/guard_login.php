<?php
include "../db.php";

// Already logged in, or a valid remember-me cookie: go to the dashboard.
if ((isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'guard')
    || remember_login($conn, 'remember_guard', 'guard')) {
    header("Location: guard_dashboard.php");
    exit();
}

$error = "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $user = db_one($conn,
        "SELECT user_id, email, password, role FROM users WHERE email = ? AND role = 'guard' LIMIT 1",
        [$email]);

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);

        if ($remember) {
            remember_issue($conn, (int)$user['user_id'], 'remember_guard');
        }

        // Remember the email (not the password) to pre-fill the form next time.
        setcookie("remember_email_guard", $email, ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

        header("Location: guard_dashboard.php");
        exit();
    }

    $error = "Invalid email or password!";
}
?>
<?php auth_start('Guard Login', 'Access your security guard account.', '../', 'badge'); ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['reset'])): ?>
                <div class="alert alert-ok">Password updated. You can log in now.</div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com"
                           value="<?php echo e($_COOKIE['remember_email_guard'] ?? ''); ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Your password" required>
                </div>
                <div class="row-between">
                    <label class="check"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="../forgot_password.php?role=guard">Forgot password?</a>
                </div>
                <button type="submit" name="login" class="btn-gold">Log in</button>
            </form>

            <p class="auth-foot">Want to work with us? <a href="guard_register.php">Apply as a guard</a></p>
<?php auth_end(); ?>
