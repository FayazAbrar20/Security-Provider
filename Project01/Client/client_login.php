<?php
include "../db.php";

// Already logged in, or a valid remember-me cookie: go to the dashboard.
if ((isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client')
    || remember_login($conn, 'remember_client', 'client')) {
    header("Location: client_dashboard.php");
    exit();
}

$error = "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $user = db_one($conn,
        "SELECT user_id, email, password, role FROM users WHERE email = ? AND role = 'client' LIMIT 1",
        [$email]);

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);

        if ($remember) {
            remember_issue($conn, (int)$user['user_id'], 'remember_client');
        }

        // Remember the email (not the password) to pre-fill the form next time.
        setcookie("remember_email", $email, ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

        header("Location: client_dashboard.php");
        exit();
    }

    $error = "Invalid email or password!";
}
?>
<?php auth_start('Client Login', 'Book verified security professionals in minutes.', '../', 'user'); ?>

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
                           value="<?php echo e($_COOKIE['remember_email'] ?? ''); ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Your password" required>
                </div>
                <div class="row-between">
                    <label class="check"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="../forgot_password.php?role=client">Forgot password?</a>
                </div>
                <button type="submit" name="login" class="btn-gold">Log in</button>
            </form>

            <p class="auth-foot">New here? <a href="client_register.php">Create a client account</a></p>
<?php auth_end(); ?>
