<?php
    include "../db.php";

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: client_login.php");
        exit();
    }

    // Booking changes data, so it only happens through the POST form on the dashboard
    // (which also carries the CSRF token). Opening this page by link does nothing.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: client_dashboard.php");
        exit();
    }

    $user_id  = $_SESSION['user_id'];
    $guard_id = (int)($_POST['guard_id'] ?? 0);

    if ($guard_id <= 0) {
        die("Guard not specified. <a href='client_dashboard.php'>Back to Dashboard</a>");
    }

    $client = db_one($conn, "SELECT id FROM clients WHERE user_id = ?", [$user_id]);
    if (!$client) {
        die("Client not found.");
    }
    $client_id = $client['id'];

    $guard = db_one($conn,
        "SELECT full_name FROM guards WHERE id = ? AND verification_status = 'approved'",
        [$guard_id]);
    if (!$guard) {
        die("Guard not found or not approved. <a href='client_dashboard.php'>Back to Dashboard</a>");
    }

    // Do not create a second pending booking for the same guard.
    $existing = db_one($conn,
        "SELECT id FROM bookings WHERE client_id = ? AND guard_id = ? AND status = 'pending' LIMIT 1",
        [$client_id, $guard_id]);

    $success = false;
    $message = "";
    if ($existing) {
        $message = "You already have a pending booking with this guard.";
    } elseif (db_query($conn,
        "INSERT INTO bookings (client_id, guard_id, booking_date, hours, status) VALUES (?, ?, NOW(), 1, 'pending')",
        [$client_id, $guard_id])) {
        $success = true;
    } else {
        $message = "Booking failed. Please try again.";
    }
?>
<?php auth_start($success ? 'Booking Requested' : 'Could Not Book', $success ? 'The guard will confirm or decline your request.' : 'Nothing was booked.', '../', $success ? 'check' : 'calendar'); ?>

            <?php if ($success): ?>
                <div class="alert alert-ok">
                    Guard "<?php echo e($guard['full_name']); ?>" booked successfully! Your booking status is <strong>Pending</strong>.
                </div>
            <?php else: ?>
                <div class="alert alert-error"><?php echo e($message); ?></div>
            <?php endif; ?>

            <a href="client_dashboard.php<?php echo $success ? '#my-bookings-modal' : ''; ?>" class="btn-gold">
                <?php echo $success ? 'See my bookings' : 'Back to dashboard'; ?>
            </a>
            <p class="auth-foot"><a href="client_dashboard.php">&larr; Back to dashboard</a></p>
<?php auth_end(); ?>
