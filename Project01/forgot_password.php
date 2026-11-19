<?php
include "db.php";

/*
 * Forgot password, for every role. Step 1 proves who you are; step 2 sets a new password.
 *  - Client / guard: email + phone number + full name, all checked against the database.
 *  - Admin: email + the recovery key shown at registration (admins have no phone or name on file).
 * Wrong answers are counted; after 5 failures for an email (or 20 from one visitor) in
 * 15 minutes, resets are blocked for a while. Every failure shows the same message, so the
 * page never reveals which emails exist.
 */

$roles = [
    'client' => ['title' => 'Client', 'login' => 'Client/client_login.php', 'table' => 'clients', 'icon' => 'user'],
    'guard'  => ['title' => 'Guard',  'login' => 'Guard/guard_login.php',   'table' => 'guards',  'icon' => 'badge'],
    'admin'  => ['title' => 'Admin',  'login' => 'Admin/admin_login.php',   'table' => null,      'icon' => 'shield'],
];
$role = $_GET['role'] ?? '';
if (!isset($roles[$role])) {
    header("Location: index.php");
    exit();
}
$cfg = $roles[$role];
$error = "";
$email = $phone = $name = "";

// A verified reset is valid for 10 minutes and only for this role.
$verified = ($_SESSION['reset']['role'] ?? '') === $role && ($_SESSION['reset']['until'] ?? 0) > time();
if (!$verified) {
    unset($_SESSION['reset']);
}

$genericFail = "Those details don't match our records. Please check them and try again.";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $email = trim($_POST['email'] ?? '');
    $phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    $name  = trim($_POST['full_name'] ?? '');
    $key   = $_POST['recovery_key'] ?? '';

    if ($email === '' || ($role === 'admin' ? trim($key) === '' : ($phone === '' || $name === ''))) {
        $error = "Please fill in every field.";
    } elseif (reset_blocked($conn, $email)) {
        $error = "Too many attempts. Please wait " . RESET_WINDOW_MINUTES . " minutes and try again.";
    } else {
        $user = null;
        if ($role === 'admin') {
            $row = db_one($conn, "SELECT user_id, recovery_hash FROM users WHERE email = ? AND role = 'admin' LIMIT 1", [$email]);
            if ($row && $row['recovery_hash'] && password_verify(normalize_recovery_key($key), $row['recovery_hash'])) {
                $user = $row;
            }
        } else {
            $row = db_one($conn,
                "SELECT u.user_id, p.phone, p.full_name FROM users u JOIN {$cfg['table']} p ON p.user_id = u.user_id
                 WHERE u.email = ? AND u.role = ? LIMIT 1",
                [$email, $role]);
            if ($row && hash_equals(preg_replace('/\D/', '', $row['phone']), $phone)
                && mb_strtolower(preg_replace('/\s+/', ' ', trim($row['full_name']))) === mb_strtolower(preg_replace('/\s+/', ' ', $name))) {
                $user = $row;
            }
        }

        if ($user) {
            reset_clear_failures($conn, $email);
            session_regenerate_id(true);
            $_SESSION['reset'] = ['role' => $role, 'user_id' => (int)$user['user_id'], 'until' => time() + 600];
            $verified = true;
        } else {
            reset_record_failure($conn, $email);
            $error = $genericFail;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
    if (!$verified) {
        $error = "Your verification expired. Please confirm your details again.";
    } else {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (($problem = password_problem($new)) !== '') {
            $error = $problem;
        } elseif ($new !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $user_id = $_SESSION['reset']['user_id'];
            db_query($conn, "UPDATE users SET password = ? WHERE user_id = ?", [password_hash($new, PASSWORD_DEFAULT), $user_id]);
            // Sign out every remembered device for this account.
            db_query($conn, "DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
            unset($_SESSION['reset']);
            header("Location: " . $cfg['login'] . "?reset=1");
            exit();
        }
    }
}
?>
<?php auth_start('Reset ' . $cfg['title'] . ' Password', $verified ? 'Choose a new password for your account.' : 'Confirm it is you, then choose a new password.', '', $verified ? 'lock' : 'key'); ?>

            <div class="steps"><span class="on"></span><span class="<?php echo $verified ? 'on' : ''; ?>"></span></div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if (!$verified): ?>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="email">Email on your account</label>
                        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" placeholder="you@example.com" required>
                    </div>
                    <?php if ($role === 'admin'): ?>
                        <div class="field">
                            <label for="recovery_key">Recovery key</label>
                            <input type="text" id="recovery_key" name="recovery_key" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" required>
                            <span class="hint">Shown once when the admin account was created, or generated from the admin dashboard.</span>
                        </div>
                    <?php else: ?>
                        <div class="field">
                            <label for="phone">Phone number on your account</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo e($phone); ?>" placeholder="01XXXXXXXXX" required>
                        </div>
                        <div class="field">
                            <label for="full_name">Full name, as registered</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo e($name); ?>" placeholder="Your full name" required>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="verify" class="btn-gold">Verify my identity</button>
                </form>
            <?php else: ?>
                <div class="alert alert-ok">Identity confirmed. This step stays open for 10 minutes.</div>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <span class="hint">At least 8 characters, with letters and numbers.</span>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="set_password" class="btn-gold">Save new password</button>
                </form>
            <?php endif; ?>

            <p class="auth-foot"><a href="<?php echo e($cfg['login']); ?>">&larr; Back to <?php echo e(strtolower($cfg['title'])); ?> login</a></p>
<?php auth_end(); ?>
