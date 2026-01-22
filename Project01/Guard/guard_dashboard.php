<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guard') {
    header("Location: guard_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get guard info
$sql = "SELECT u.email, g.* FROM users u
        JOIN guards g ON u.user_id = g.user_id
        WHERE u.user_id='$user_id'";
$result = mysqli_query($conn, $sql);
$guard = mysqli_fetch_assoc($result);

// Get booking statistics
$total_bookings = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as count FROM bookings WHERE guard_id='{$guard['id']}'"
))['count'];

$pending_bookings = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as count FROM bookings WHERE guard_id='{$guard['id']}' AND status='pending'"
))['count'];

$confirmed_bookings = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) as count FROM bookings WHERE guard_id='{$guard['id']}' AND status='confirmed'"
))['count'];

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $guard_id = $guard['id'];
    
    $sql = "UPDATE bookings 
            SET status='confirmed'
            WHERE id='$booking_id' AND guard_id='$guard_id' AND status='pending'";
    mysqli_query($conn, $sql);
    
    header("Location: guard_dashboard.php#my-bookings");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $guard_id = $guard['id'];
    
    $sql = "UPDATE bookings 
            SET status='rejected'
            WHERE id='$booking_id' AND guard_id='$guard_id' AND status='pending'";
    mysqli_query($conn, $sql);
    
    header("Location: guard_dashboard.php#my-bookings");
    exit();
}

// Get guard's bookings
$bookings_sql = "SELECT b.*, c.full_name AS client_name, c.phone AS client_phone, c.organization_type
                FROM bookings b
                JOIN clients c ON b.client_id = c.id
                WHERE b.guard_id = '{$guard['id']}'
                ORDER BY b.created_at DESC";
$bookings_result = mysqli_query($conn, $bookings_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #3498db;
            color: white;
            padding: 20px 0;
            z-index: 1000;
        }

        .sidebar h3 {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #fbfdff;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            border-bottom: 1px solid #fbfdff;
        }

        .sidebar a:hover {
            background: #6ab1d0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-link img {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar .logout-btn {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 25px;
        }

        .sidebar .logout-btn a {
            background: #e74c3c;
            text-align: center;
            border-radius: 5px;
        }

        .sidebar .logout-btn a:hover {
            background: #c0392b;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .stat-card h3 {
            font-size: 36px;
            color: #3498db;
            margin-bottom: 10px;
        }

        .section-header h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .bookings-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 30px;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .bookings-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-confirm {
            background: #27ae60;
            color: white;
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .no-bookings {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
        }

        #profile-view:target ~ .modal-overlay,
        #profile-edit:target ~ .modal-overlay,
        #password-change:target ~ .modal-overlay,
        #my-bookings:target ~ .modal-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 3000;
            opacity: 0;
            pointer-events: none;
        }

        #profile-view:target ~ .profile-card,
        #profile-edit:target ~ .edit-card,
        #password-change:target ~ .password-card,
        #my-bookings:target ~ .bookings-card {
            opacity: 1;
            pointer-events: auto;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 12px;
            width: 30px;
            height: 30px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #9fd3a9;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <!-- Hidden anchors for modals -->
    <div id="profile-view"></div>
    <div id="profile-edit"></div>
    <div id="password-change"></div>
    <div id="my-bookings"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Guard Portal</h3>
        <a href="#profile-view" class="sidebar-link">
            <img src="../uploads/clients/circle-user_9821720.png" alt="Profile"> My Profile
        </a>
        <!--
        <a href="#profile-edit" class="sidebar-link">
            <img src="../uploads/clients/pen-circle_18844818.png" alt="Edit"> Edit Profile
        </a>
        <a href="#password-change" class="sidebar-link">
            <img src="../uploads/clients/password-lock_17525250.png" alt="Password"> Change Password
        </a>
        -->
        <a href="#my-bookings" class="sidebar-link">
            <img src="../uploads/clients/booking_14703155.png" alt="Bookings"> My Bookings
        </a>

        <div class="logout-btn">
            <a href="guard_logout.php">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Welcome, <?php echo $guard['full_name']; ?>!</h2>
            <p style="color:#666;">Email: <?php echo $guard['email']; ?> | Status: <?php echo ucfirst($guard['verification_status']); ?></p>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $pending_bookings; ?></h3>
                <p>Pending Bookings</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $confirmed_bookings; ?></h3>
                <p>Confirmed Bookings</p>
            </div>
        </div>

        <!-- Bookings Section -->
        <div class="bookings-section">
            <div class="section-header">
                <h3>My Bookings</h3>
            </div>

            <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Phone</th>
                            <th>Organization</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['client_phone']); ?></td>
                            <td><?php echo ucfirst($booking['organization_type']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            <td>
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="confirm_booking" class="btn btn-confirm">Confirm</button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="reject_booking" class="btn btn-reject" onclick="return confirm('Reject this booking?')">Reject</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-bookings">
                    No bookings yet. Wait for clients to book you.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Blur Overlay -->
    <div class="modal-overlay"></div>

    <!-- Profile Modal -->
    <div class="modal-card profile-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>My Profile</h3>
        <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
            <tr><th style="padding: 10px; border-bottom: 1px solid #eee;">Full Name:</th><td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $guard['full_name']; ?></td></tr>
            <tr><th style="padding: 10px; border-bottom: 1px solid #eee;">Email:</th><td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $guard['email']; ?></td></tr>
            <tr><th style="padding: 10px; border-bottom: 1px solid #eee;">Phone:</th><td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $guard['phone']; ?></td></tr>
            <tr><th style="padding: 10px; border-bottom: 1px solid #eee;">Location:</th><td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $guard['location']; ?></td></tr>
            <tr><th style="padding: 10px; border-bottom: 1px solid #eee;">Experience:</th><td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $guard['experience']; ?> years</td></tr>
            <tr><th style="padding: 10px;">Status:</th><td style="padding: 10px;"><?php echo ucfirst($guard['verification_status']); ?></td></tr>
        </table>
    </div>

    <!-- Other modals (Edit Profile, Change Password) - simplified for brevity -->
    <div class="modal-card edit-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>Edit Profile (Coming Soon)</h3>
        <p>Profile editing functionality will be added later.</p>
    </div>

    <div class="modal-card password-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>Change Password (Coming Soon)</h3>
        <p>Password change functionality will be added later.</p>
    </div>

    <div class="modal-card bookings-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>My Bookings</h3>
        <p>Bookings are shown above.</p>
    </div>

</body>
</html>
