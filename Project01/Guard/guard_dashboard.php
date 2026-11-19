<?php
include "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guard') {
    header("Location: guard_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get guard info
$guard = db_one($conn,
    "SELECT u.email, g.* FROM users u JOIN guards g ON u.user_id = g.user_id WHERE u.user_id = ?",
    [$user_id]);
if (!$guard) {
    header("Location: guard_logout.php");
    exit();
}

// Confirm or reject one of this guard's pending bookings.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['confirm_booking']) || isset($_POST['reject_booking']))) {
    $new_status = isset($_POST['confirm_booking']) ? 'confirmed' : 'rejected';
    db_query($conn,
        "UPDATE bookings SET status = ? WHERE id = ? AND guard_id = ? AND status = 'pending'",
        [$new_status, (int)($_POST['booking_id'] ?? 0), $guard['id']]);

    header("Location: guard_dashboard.php#bookings");
    exit();
}

// Change password (same rules as clients).
$currentErr = $newErr = $confirmErr = "";
$passwordSuccess = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $user = db_one($conn, "SELECT password FROM users WHERE user_id = ?", [$user_id]);

    if (!$user || !password_verify($current, $user['password'])) {
        $currentErr = "Current password is incorrect";
    } elseif (($problem = password_problem($new)) !== '') {
        $newErr = $problem;
    } elseif ($new !== $confirm) {
        $confirmErr = "Passwords do not match";
    } elseif (db_query($conn, "UPDATE users SET password = ? WHERE user_id = ?", [password_hash($new, PASSWORD_DEFAULT), $user_id])) {
        db_query($conn, "DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
        $passwordSuccess = "Password changed successfully!";
    }
}

// Booking statistics
$counts = db_one($conn,
    "SELECT COUNT(*) AS total,
            SUM(status = 'pending') AS pending,
            SUM(status = 'confirmed') AS confirmed
     FROM bookings WHERE guard_id = ?",
    [$guard['id']]);
$total_bookings     = (int)($counts['total'] ?? 0);
$pending_bookings   = (int)($counts['pending'] ?? 0);
$confirmed_bookings = (int)($counts['confirmed'] ?? 0);

// Get guard's bookings
$bookings_result = db_query($conn,
    "SELECT b.*, c.full_name AS client_name, c.phone AS client_phone, c.organization_type
     FROM bookings b
     JOIN clients c ON b.client_id = c.id
     WHERE b.guard_id = ?
     ORDER BY b.created_at DESC",
    [$guard['id']]);
?>
<?php
    $hour = (int)date('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $photo = (!empty($guard['profile_image']) && file_exists(dirname(__DIR__) . '/' . $guard['profile_image'])) ? '../' . $guard['profile_image'] : '';
    $bookingRows = [];
    if ($bookings_result && $bookings_result !== true) {
        while ($row = mysqli_fetch_assoc($bookings_result)) {
            $bookingRows[] = $row;
        }
    }
    $status = $guard['verification_status'];
    page_head('Guard Dashboard', '../');
?>
<body>
    <div id="profile-view"></div>
    <div id="password-change"></div>
    <div id="my-bookings"></div>

    <aside class="sidebar">
        <a class="side-brand" href="../index.php">
            <img src="../uploads/index/Image (12).jpg" alt="">
            <span><strong>SECURITY PROVIDER</strong><small>Guard</small></span>
        </a>
        <a href="#profile-view" class="sidebar-link"><img src="../uploads/clients/circle-user_9821720.png" alt=""> My Profile</a>
        <a href="#password-change" class="sidebar-link"><img src="../uploads/clients/password-lock_17525250.png" alt=""> Change Password</a>
        <a href="#bookings" class="sidebar-link"><img src="../uploads/clients/booking_14703155.png" alt=""> My Bookings</a>
        <div class="logout-btn"><a href="guard_logout.php">Log out</a></div>
    </aside>

    <main class="main-content">
        <section class="header">
            <?php if ($photo): ?>
                <img class="avatar" src="<?php echo e($photo); ?>" alt="">
            <?php else: ?>
                <div class="avatar"><?php echo e(strtoupper(mb_substr(trim($guard['full_name']), 0, 1))); ?></div>
            <?php endif; ?>
            <div>
                <div class="greet"><?php echo e($greeting); ?></div>
                <h2>Welcome, <?php echo e($guard['full_name']); ?>!</h2>
                <p><?php echo e($guard['location']); ?> &middot; $<?php echo number_format((float)$guard['hourly_rate'], 0); ?>/hr &middot;
                    <span class="chip chip-<?php echo e($status); ?>"><?php echo e($status); ?></span></p>
            </div>
        </section>

        <?php if ($status === 'pending'): ?>
            <div class="alert alert-gold">Your application is under review. Clients will see your profile as soon as an admin approves it.</div>
        <?php elseif ($status === 'rejected'): ?>
            <div class="alert alert-error">Your application was not approved. Please contact the Security Provider team for details.</div>
        <?php endif; ?>

        <section class="stats-container">
            <div class="stat-card accent-gold">
                <div class="stat-icon"><?php echo icon('calendar'); ?></div>
                <h3><?php echo e($total_bookings); ?></h3>
                <p>Total bookings</p>
            </div>
            <div class="stat-card accent-amber">
                <div class="stat-icon"><?php echo icon('clock'); ?></div>
                <h3><?php echo e($pending_bookings); ?></h3>
                <p>Need your answer</p>
            </div>
            <div class="stat-card accent-green">
                <div class="stat-icon"><?php echo icon('check'); ?></div>
                <h3><?php echo e($confirmed_bookings); ?></h3>
                <p>Confirmed jobs</p>
            </div>
        </section>

        <section class="panel" id="bookings">
            <div class="section-header">
                <h3>My bookings</h3>
                <span class="count"><?php echo count($bookingRows); ?> total</span>
            </div>
            <?php if ($bookingRows): ?>
                <table class="data-table">
                    <thead>
                        <tr><th>Client</th><th>Phone</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookingRows as $booking): ?>
                            <tr>
                                <td><strong><?php echo e($booking['client_name']); ?></strong></td>
                                <td class="muted"><?php echo e($booking['client_phone']); ?></td>
                                <td><?php echo e(ucfirst($booking['organization_type'])); ?></td>
                                <td><span class="chip chip-<?php echo e($booking['status']); ?>"><?php echo e($booking['status']); ?></span></td>
                                <td class="muted"><?php echo e(date('M d, Y', strtotime($booking['created_at']))); ?></td>
                                <td>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <div class="actions">
                                            <form method="post" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                                <button type="submit" name="confirm_booking" class="btn-ok btn-sm">Confirm</button>
                                            </form>
                                            <form method="post" class="inline-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                                <button type="submit" name="reject_booking" class="btn-danger btn-sm" onclick="return confirm('Reject this booking?')">Reject</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty"><?php echo icon('calendar'); ?><p>No bookings yet. Clients will find you once you are approved.</p></div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Profile -->
    <div class="modal-card profile-card">
        <a href="#" class="close-btn" aria-label="Close">&times;</a>
        <h3>My Profile</h3>
        <?php if ($photo): ?>
            <img class="profile-photo" src="<?php echo e($photo); ?>" alt="">
        <?php endif; ?>
        <table class="info-table">
            <tr><th>Full name</th><td><?php echo e($guard['full_name']); ?></td></tr>
            <tr><th>Email</th><td><?php echo e($guard['email']); ?></td></tr>
            <tr><th>Phone</th><td><?php echo e($guard['phone']); ?></td></tr>
            <tr><th>Location</th><td><?php echo e($guard['location']); ?></td></tr>
            <tr><th>Experience</th><td><?php echo (int)$guard['experience']; ?> years</td></tr>
            <tr><th>Hourly rate</th><td>$<?php echo number_format((float)$guard['hourly_rate'], 2); ?>/hr</td></tr>
            <tr><th>Skills</th><td><?php echo nl2br(e($guard['skills'])); ?></td></tr>
            <tr><th>Specialties</th><td><?php echo e($guard['specialties']); ?></td></tr>
            <tr><th>Status</th><td><span class="chip chip-<?php echo e($status); ?>"><?php echo e($status); ?></span></td></tr>
        </table>
    </div>

    <!-- Change Password -->
    <div class="modal-card password-card">
        <a href="#" class="close-btn" aria-label="Close">&times;</a>
        <h3>Change Password</h3>
        <?php if ($passwordSuccess): ?>
            <div class="success"><?php echo e($passwordSuccess); ?></div>
        <?php endif; ?>
        <form method="post" action="#password-change">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="cp-current">Current password</label>
                <input type="password" id="cp-current" name="current_password">
                <span class="error"><?php echo e($currentErr); ?></span>
            </div>
            <div class="field">
                <label for="cp-new">New password</label>
                <input type="password" id="cp-new" name="new_password">
                <span class="error"><?php echo e($newErr); ?></span>
                <span class="hint">8+ characters, letters and numbers.</span>
            </div>
            <div class="field">
                <label for="cp-confirm">Confirm new password</label>
                <input type="password" id="cp-confirm" name="confirm_password">
                <span class="error"><?php echo e($confirmErr); ?></span>
            </div>
            <button type="submit" name="change_password" class="btn-gold">Update password</button>
        </form>
    </div>

    <a href="#" class="modal-overlay" aria-label="Close"></a>
</body>
</html>
