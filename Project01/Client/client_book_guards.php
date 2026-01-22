<?php
    session_start();
    include "../db.php";

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: client_login.php");
        exit();
    }

    $user_id = $_SESSION['user_id']; 
    $guard_id = $_GET['guard_id'] ?? null;

    if (!$guard_id) {
        die("Guard not specified. <a href='client_dashboard.php'>Back to Dashboard</a>");
    }

    $client_res = mysqli_query($conn, "SELECT id FROM clients WHERE user_id='$user_id'");
    if (!$client_res || mysqli_num_rows($client_res) == 0) {
        die("Client not found.");
    }
    $client = mysqli_fetch_assoc($client_res);
    $client_id = $client['id'];

    $guard_res = mysqli_query($conn, "SELECT full_name FROM guards WHERE id='$guard_id' AND verification_status='approved'");
    if (!$guard_res || mysqli_num_rows($guard_res) == 0) {
        die("Guard not found or not approved. <a href='client_dashboard.php'>Back to Dashboard</a>");
    }
    $guard = mysqli_fetch_assoc($guard_res);

    $sql = "INSERT INTO bookings (client_id, guard_id, booking_date, hours, status) 
            VALUES ('$client_id', '$guard_id', NOW(), 1, 'pending')";

    $success = false;
    if (mysqli_query($conn, $sql)) {
        $success = true;
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
            <h2>Error booking guard.</h2>
            <p><?php echo mysqli_error($conn); ?></p>
        <?php endif; ?>

        <a href="client_dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>
