<?php
include "../db.php";

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$msg = "";
$newKey = "";

// Approve / Reject a pending guard application
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["guard_id"])) {
    $statuses = ['approve' => 'approved', 'reject' => 'rejected'];
    $status = $statuses[$_POST["action"]] ?? "";

    if ($status !== "") {
        if (db_query($conn, "UPDATE guards SET verification_status = ? WHERE id = ? LIMIT 1",
                     [$status, (int)$_POST["guard_id"]])) {
            $msg = "Guard status updated to: " . $status;
        } else {
            $msg = "Could not update the guard. Please try again.";
        }
    }
}

// New recovery key for this admin (replaces any previous key).
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["new_recovery_key"])) {
    $newKey = new_recovery_key();
    if (!db_query($conn, "UPDATE users SET recovery_hash = ? WHERE user_id = ? AND role = 'admin'",
                  [password_hash($newKey, PASSWORD_DEFAULT), $_SESSION['user_id']])) {
        $newKey = "";
        $msg = "Could not create a recovery key. Please try again.";
    }
}

$me = db_one($conn, "SELECT email, recovery_hash FROM users WHERE user_id = ?", [$_SESSION['user_id']]);

$stats = db_one($conn,
    "SELECT
        (SELECT COUNT(*) FROM guards WHERE verification_status = 'pending')  AS pending_guards,
        (SELECT COUNT(*) FROM guards WHERE verification_status = 'approved') AS approved_guards,
        (SELECT COUNT(*) FROM clients)                                       AS clients,
        (SELECT COUNT(*) FROM bookings)                                      AS bookings");

// Load pending guards
$pending_result = db_query($conn,
    "SELECT g.*, u.email
     FROM guards g
     JOIN users u ON u.user_id = g.user_id
     WHERE g.verification_status = 'pending'
     ORDER BY g.created_at DESC");
?>
<?php
    $hour = (int)date('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $pendingRows = [];
    if ($pending_result && $pending_result !== true) {
        while ($row = mysqli_fetch_assoc($pending_result)) {
            $pendingRows[] = $row;
        }
    }
    page_head('Admin Dashboard', '../');
?>
<body>
    <nav class="topbar">
        <a class="brand" href="../index.php">
            <img src="../uploads/index/Image (12).jpg" alt="">
            <span>SECURITY PROVIDER</span>
        </a>
        <div class="actions">
            <a class="pill-link" href="admin_registration.php">+ Add admin</a>
            <a class="pill-link" href="admin_logout.php">Log out</a>
        </div>
    </nav>

    <main class="admin-main">
        <section class="header">
            <div class="avatar"><?php echo icon('shield'); ?></div>
            <div>
                <div class="greet"><?php echo e($greeting); ?></div>
                <h2>Admin Dashboard</h2>
                <p>Signed in as <?php echo e($me['email'] ?? ''); ?> &middot; review guard applications and keep the platform trusted.</p>
            </div>
        </section>

        <?php if ($msg): ?>
            <div class="alert alert-ok"><?php echo e($msg); ?></div>
        <?php endif; ?>

        <?php if ($newKey): ?>
            <div class="alert alert-gold">
                <strong>Your new recovery key.</strong> Save it somewhere safe: it is the only way to reset your
                password from the login page, and it will not be shown again. Any older key no longer works.
                <div class="key-box"><?php echo e($newKey); ?></div>
            </div>
        <?php elseif (empty($me['recovery_hash'])): ?>
            <div class="alert alert-gold" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                <span><strong>No recovery key yet.</strong> Without one, a forgotten admin password cannot be reset from the login page.</span>
                <form method="post" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="new_recovery_key" class="btn">Create recovery key</button>
                </form>
            </div>
        <?php endif; ?>

        <section class="stats-container">
            <div class="stat-card accent-amber">
                <div class="stat-icon"><?php echo icon('clock'); ?></div>
                <h3><?php echo (int)($stats['pending_guards'] ?? 0); ?></h3>
                <p>Applications waiting</p>
            </div>
            <div class="stat-card accent-green">
                <div class="stat-icon"><?php echo icon('badge'); ?></div>
                <h3><?php echo (int)($stats['approved_guards'] ?? 0); ?></h3>
                <p>Verified guards</p>
            </div>
            <div class="stat-card accent-blue">
                <div class="stat-icon"><?php echo icon('users'); ?></div>
                <h3><?php echo (int)($stats['clients'] ?? 0); ?></h3>
                <p>Clients</p>
            </div>
            <div class="stat-card accent-gold">
                <div class="stat-icon"><?php echo icon('calendar'); ?></div>
                <h3><?php echo (int)($stats['bookings'] ?? 0); ?></h3>
                <p>Bookings</p>
            </div>
        </section>

        <section class="panel">
            <div class="section-header">
                <h3>Pending guard requests</h3>
                <span class="count"><?php echo count($pendingRows); ?> waiting</span>
            </div>

            <?php if (!$pendingRows): ?>
                <div class="empty"><?php echo icon('check'); ?><p>All caught up. No applications are waiting.</p></div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>Guard</th><th>Contact</th><th>Details</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRows as $g): ?>
                            <?php
                                $img_src = (!empty($g['profile_image']) && file_exists(dirname(__DIR__) . '/' . $g['profile_image']))
                                    ? '../' . $g['profile_image'] : '';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <?php if ($img_src): ?>
                                            <img class="guard-thumb" src="<?php echo e($img_src); ?>" alt="">
                                        <?php else: ?>
                                            <div class="no-thumb"><?php echo e(strtoupper(mb_substr($g['full_name'], 0, 1))); ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo e($g['full_name']); ?></strong>
                                            <div class="muted"><?php echo e($g['location']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo e($g['email']); ?></div>
                                    <div class="muted"><?php echo e($g['phone']); ?></div>
                                </td>
                                <td>
                                    <div class="muted"><?php echo (int)$g['experience']; ?> yrs &middot; $<?php echo number_format((float)$g['hourly_rate'], 2); ?>/hr</div>
                                    <div class="muted"><?php echo e($g['specialties']); ?></div>
                                </td>
                                <td><span class="chip chip-<?php echo e($g['verification_status']); ?>"><?php echo e($g['verification_status']); ?></span></td>
                                <td>
                                    <div class="actions">
                                        <form method="post" class="inline-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="guard_id" value="<?php echo (int)$g['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-ok btn-sm">Approve</button>
                                        </form>
                                        <form method="post" class="inline-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="guard_id" value="<?php echo (int)$g['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Reject this application?')">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if (!empty($me['recovery_hash']) && !$newKey): ?>
            <div class="muted" style="margin-top:18px;">
                Lost your recovery key?
                <form method="post" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="new_recovery_key" class="btn-ghost btn-sm" onclick="return confirm('Create a new recovery key? The old one will stop working.')">Create a new one</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
