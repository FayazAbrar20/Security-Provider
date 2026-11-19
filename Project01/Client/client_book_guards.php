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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Guard</title>
<style>
    body { 
        font-family: Arial, sans-serif; 
        background: #f5f5f5; 
        padding: 50px; 
        text-align: center; 
    }
    .container { 
        background: white; 
        padding: 30px; 
        border-radius: 10px; 
        display: inline-block; 
    }
    .btn { 
        padding: 10px 20px; 
        background: #3498db; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        margin-top: 20px; 
        display: inline-block; 
    }
    .btn:hover { background: #2980b9; }
</style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <h2>Guard "<?php echo htmlspecialchars($guard['full_name']); ?>" booked successfully!</h2>
            <p>Your booking status is <strong>Pending</strong>.</p>
        <?php else: ?>
            <h2>Could not book this guard.</h2>
            <p><?php echo e($message); ?></p>
        <?php endif; ?>

        <a href="client_dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>
