    <?php
    include "../db.php";

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: client_login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $allowedOrgTypes = ['home', 'industry', 'individual'];

    function load_client(mysqli $conn, $user_id): ?array
    {
        return db_one($conn,
            "SELECT u.email, c.* FROM users u JOIN clients c ON u.user_id = c.user_id WHERE u.user_id = ?",
            [$user_id]);
    }

    $client = load_client($conn, $user_id);
    if (!$client) {
        header("Location: client_logout.php");
        exit();
    }

    // Cancel a pending booking (only the client's own).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
        db_query($conn,
            "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND client_id = ? AND status = 'pending'",
            [(int)($_POST['booking_id'] ?? 0), $client['id']]);

        header("Location: client_dashboard.php#my-bookings");
        exit();
    }

    $counts = db_one($conn,
        "SELECT COUNT(*) AS total,
                SUM(status = 'pending') AS pending,
                SUM(status = 'confirmed') AS confirmed
         FROM bookings WHERE client_id = ?",
        [$client['id']]);
    $total_bookings     = (int)($counts['total'] ?? 0);
    $pending_bookings   = (int)($counts['pending'] ?? 0);
    $confirmed_bookings = (int)($counts['confirmed'] ?? 0);

    // Search approved guards by name, location or specialty.
    $search = trim($_GET['search'] ?? "");
    $guards_sql = "SELECT g.*, u.email AS guard_email
                   FROM guards g
                   JOIN users u ON u.user_id = g.user_id
                   WHERE g.verification_status = 'approved'";
    $guards_params = [];
    if ($search !== "") {
        $guards_sql .= " AND (g.full_name LIKE ? OR g.location LIKE ? OR g.specialties LIKE ?)";
        $like = "%" . $search . "%";
        $guards_params = [$like, $like, $like];
    }
    $guards_sql .= " ORDER BY g.created_at DESC";

    $guards = [];
    $guards_result = db_query($conn, $guards_sql, $guards_params);
    if ($guards_result && $guards_result !== true) {
        while ($guard = mysqli_fetch_assoc($guards_result)) {
            $guards[] = $guard;
        }
    }

    // Update profile
    $success = "";
    $nameErr = $phoneErr = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
        $valid = true;

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $org_type = $_POST["org_type"] ?? "";
        if (!in_array($org_type, $allowedOrgTypes, true)) {
            $org_type = $client['organization_type'];
        }

        if (!preg_match("/^[a-zA-Z-' ]{2,100}$/", $name)) {
            $nameErr = "Name is required (letters, spaces, - and ' only)";
            $valid = false;
        }

        if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
            $phoneErr = "Invalid phone number";
            $valid = false;
        }

        if ($valid && db_query($conn,
                "UPDATE clients SET full_name = ?, phone = ?, address = ?, organization_type = ? WHERE user_id = ?",
                [$name, $phone, $address, $org_type, $user_id])) {
            $success = "Profile updated successfully!";
            $client = load_client($conn, $user_id);
        }
    }

    // Change password
    $currentErr = $newErr = $confirmErr = "";
    $passwordSuccess = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
        $valid = true;

        $current = $_POST["current_password"] ?? "";
        $new = $_POST["new_password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";

        $user = db_one($conn, "SELECT password FROM users WHERE user_id = ?", [$user_id]);

        if (!$user || !password_verify($current, $user['password'])) {
            $currentErr = "Current password is incorrect";
            $valid = false;
        }

        if (($problem = password_problem($new)) !== "") {
            $newErr = $problem;
            $valid = false;
        }

        if ($new !== $confirm) {
            $confirmErr = "Passwords do not match";
            $valid = false;
        }

        if ($valid && db_query($conn, "UPDATE users SET password = ? WHERE user_id = ?",
                               [password_hash($new, PASSWORD_DEFAULT), $user_id])) {
            db_query($conn, "DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
            $passwordSuccess = "Password changed successfully!";
        }
    }

    // The client's bookings (shown in the "My Bookings" panel).
    $bookings_result = db_query($conn,
        "SELECT b.*, g.full_name AS guard_name, g.location, g.hourly_rate
         FROM bookings b
         JOIN guards g ON b.guard_id = g.id
         WHERE b.client_id = ?
         ORDER BY b.created_at DESC",
        [$client['id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>

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
            border-bottom: 1px solid #e1e9f1;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            border-bottom: 1px solid #e3e9ef;
        }

        .sidebar a:hover {
            background: #66b0e0;
        }

        .sidebar-link {
            display: flex !important;
            align-items: center;
            gap: 12px;
        }

        .sidebar-link img {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            vertical-align: middle;
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

        /* Main Content */
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

        /* Guards Section */
        .guards-section { margin-top: 10px; }

        .section-header h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .guards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .guard-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #ddd;
            position: relative;
            width: 320px;
            height: 420px;
    
        }

        .guard-image-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #999;
            position: relative;
        }

        .guard-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .availability-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #27ae60;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .guard-info { padding: 15px; }

        .guard-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        .guard-location {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .guard-rating {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .guard-specialties { margin-bottom: 12px; }

        .specialty-tag {
            display: inline-block;
            background: #ecf0f1;
            color: #34495e;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin: 0 6px 6px 0;
        }

        .guard-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .guard-price {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }

        .price-unit {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: normal;
        }

        .view-profile-btn {
            display: inline-block;
            text-decoration: none;
            border: 1px solid #3498db;
            color: #3498db;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
        }

        .view-profile-btn:hover {
            background: #3498db;
            color: white;
        }

        .guard-profile-card {
            opacity: 0;
            pointer-events: none;
        }

        .guard-profile-card:target {
            opacity: 1;
            pointer-events: auto;
        }

        .guard-profile-card:target ~ .modal-overlay {
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
        #password-change:target ~ .modal-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        #profile-view:target ~ .profile-card,
        #profile-edit:target ~ .edit-card,
        #password-change:target ~ .password-card {
            opacity: 1;
            pointer-events: auto;
        }

        #my-bookings-modal:target ~ .modal-overlay,
        #my-bookings-modal:target ~ .bookings-card {
            opacity: 1;
            pointer-events: auto;
        }

        .bookings-card {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 3000;
            opacity: 0;
            pointer-events: none;
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

        .error { 
            color: #b30000;
            font-size: 13px; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #9fd3a9; 
        }

        .info-table { 
            width: 100%; 
            margin-top: 15px; 
            border-collapse: collapse; 
        }
        .info-table th, .info-table td { 
            padding: 10px; 
            border-bottom: 1px solid #eee; 
            text-align: left;
        }

        .field { margin-bottom: 12px; }
        .field label { 
            display: block; 
            margin-bottom: 6px; 
            font-size: 14px; 
        }

        .field input[type="text"],
        .field input[type="password"],
        .field textarea,
        .field select {
            width: 100%;
            padding: 10px;
            border: 1px solid #aaa;
            font-size: 14px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            background: #333;
            color: white;
            cursor: pointer;
        }
        .btn:hover { background: #111; }
    </style>
</head>
<body>

    <div id="profile-view"></div>
    <div id="profile-edit"></div>
    <div id="password-change"></div>
    <div id="my-bookings-modal"></div>

    <div class="sidebar">
        <h3>Client Portal</h3>
        <a href="#profile-view" class="sidebar-link"><img src="../uploads/clients/circle-user_9821720.png"> My Profile</a>
        <a href="#profile-edit" class="sidebar-link"><img src="../uploads/clients/pen-circle_18844818.png"> Edit Profile</a>
        <a href="#password-change" class="sidebar-link"><img src="../uploads/clients/password-lock_17525250.png"> Change Password</a>
        <!--<a href="#browse-guards" class="sidebar-link"><img src="../uploads/clients/safe-browsing_19029169.png"> Browse Guards</a> -->
        <a href="#my-bookings-modal" class="sidebar-link"><img src="../uploads/clients/booking_14703155.png"> My Bookings</a>

        <div class="logout-btn">
            <a href="client_logout.php">Logout</a>
        </div>
    </div>


    <form method="get" style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <div style="display:flex; gap:10px; max-width:500px;">
        <input
            type="text"
            name="search"
            placeholder="Search guards by name, location or skill..."
            value="<?php echo htmlspecialchars($search); ?>"
            style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;"
        >
        <button
            type="submit"
            style="padding:10px 20px; background:#3498db; color:white; border:none; border-radius:6px; cursor:pointer;">
            Search
        </button>

        <?php if ($search): ?>
            <a href="client_dashboard.php"
            style="padding:10px 15px; background:#e74c3c; color:white; border-radius:6px; text-decoration:none;">
            Clear
            </a>
        <?php endif; ?>
    </div>
</form>



    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Welcome, <?php echo e($client['full_name']); ?>!</h2>
            <p style="color:#666;">Email: <?php echo e($client['email']); ?></p>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo e($total_bookings); ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card">
                <h3><?php echo e($pending_bookings); ?></h3>
                <p>Pending Bookings</p>
            </div>
            <div class="stat-card">
                <h3><?php echo e($confirmed_bookings); ?></h3>
                <p>Confirmed Bookings</p>
            </div>
        </div>

        <!-- Guards -->
        <div class="guards-section">
            <div class="section-header">
                <h3>Available Security Guards</h3>
            </div>

            <div class="guards-grid">
                <?php foreach ($guards as $guard): ?>
                    <?php
                        $specialties = explode(',', $guard['specialties'] ?? '');
                        $img_src = "../" . $guard['profile_image'];

                        if (!empty($guard['profile_image'])) {

                            $project_root = realpath(__DIR__ . "/..");

                            $fs_path = $project_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $guard['profile_image']);

                            $parts = explode('/', $guard['profile_image']);
                            $web_path = "../" . implode('/', array_map('rawurlencode', $parts));

                            if (file_exists($fs_path)) {
                                $img_src = $web_path;
                            }
                        }

                        $availability = $guard['availability_status'] ?? 'available';
                        $rating = isset($guard['rating']) ? number_format((float)$guard['rating'], 1) : '0.0';
                        $reviews = isset($guard['reviews_count']) ? (int)$guard['reviews_count'] : 0;
                    ?>

                    <div class="guard-card">
                        <div class="guard-image-container">
                            <?php if ($img_src): ?>
                                <img src="<?php echo e($img_src); ?>" alt="<?php echo e($guard['full_name']); ?>" class="guard-image">
                            <?php endif; ?>
                        </div>

                        <span class="availability-badge"><?php echo e($availability); ?></span>

                        <div class="guard-info">
                            <div class="guard-name"><?php echo e($guard['full_name']); ?></div>
                            <div class="guard-location"><?php echo e($guard['location']); ?></div>

                            <div class="guard-rating">
                                Rating <?php echo e($rating); ?> (<?php echo e($reviews); ?>)
                            </div>

                            <div class="guard-specialties">
                                <?php foreach ($specialties as $specialty): ?>
                                    <?php if (trim($specialty) !== ""): ?>
                                        <span class="specialty-tag"><?php echo e(trim($specialty)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="guard-footer">
                                <div class="guard-price">
                                    $<?php echo number_format((float)$guard['hourly_rate'], 0); ?>
                                    <span class="price-unit">/hr</span>
                                </div>
                                <a href="#guard-modal-<?php echo e($guard['id']); ?>" class="view-profile-btn">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- Blur Overlay -->
    <div class="modal-overlay"></div>

    <!-- My Profile -->
    <div class="modal-card profile-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>My Profile</h3>

        <table class="info-table">
            <tr><th>Full Name:</th><td><?php echo e($client['full_name']); ?></td></tr>
            <tr><th>Email:</th><td><?php echo e($client['email']); ?></td></tr>
            <tr><th>Phone:</th><td><?php echo e($client['phone']); ?></td></tr>
            <tr><th>Address:</th><td><?php echo e($client['address'] ?: 'Not provided'); ?></td></tr>
            <tr><th>Organization Type:</th><td><?php echo e(ucfirst($client['organization_type'])); ?></td></tr>
            <tr><th>Member Since:</th><td><?php echo e(date('F d, Y', strtotime($client['created_at']))); ?></td></tr>
        </table>
    </div>

    <!-- Edit Profile -->
    <div class="modal-card edit-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>Edit Profile</h3>

        <?php if ($success): ?>
            <div class="success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post" action="#profile-edit">
<?php echo csrf_field(); ?>
            <div class="field">
                <label>Full Name:</label>
                <input type="text" name="name" value="<?php echo e($client['full_name']); ?>">
                <span class="error"><?php echo e($nameErr); ?></span>
            </div>

            <div class="field">
                <label>Phone:</label>
                <input type="text" name="phone" value="<?php echo e($client['phone']); ?>">
                <span class="error"><?php echo e($phoneErr); ?></span>
            </div>

            <div class="field">
                <label>Address:</label>
                <textarea name="address" rows="3"><?php echo e($client['address']); ?></textarea>
            </div>

            <div class="field">
                <label>Organization Type:</label>
                <select name="org_type">
                    <option value="home" <?php if($client['organization_type']=='home') echo 'selected'; ?>>Home</option>
                    <option value="industry" <?php if($client['organization_type']=='industry') echo 'selected'; ?>>Industry</option>
                    <option value="individual" <?php if($client['organization_type']=='individual') echo 'selected'; ?>>Individual</option>
                </select>
            </div>

            <button type="submit" name="update_profile" class="btn">Update Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="modal-card password-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>Change Password</h3>

        <?php if ($passwordSuccess): ?>
            <div class="success"><?php echo e($passwordSuccess); ?></div>
        <?php endif; ?>

        <form method="post" action="#password-change">
<?php echo csrf_field(); ?>
            <div class="field">
                <label>Current Password:</label>
                <input type="password" name="current_password">
                <span class="error"><?php echo e($currentErr); ?></span>
            </div>

            <div class="field">
                <label>New Password:</label>
                <input type="password" name="new_password">
                <span class="error"><?php echo e($newErr); ?></span>
            </div>

            <div class="field">
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password">
                <span class="error"><?php echo e($confirmErr); ?></span>
            </div>

            <button type="submit" name="change_password" class="btn">Change Password</button>
        </form>
    </div>


    <!-- My Bookings Modal -->
    <div class="modal-card bookings-card">
        <a href="#" class="close-btn">&times;</a>
        <h3>My Bookings</h3>

        <?php if ($bookings_result && $bookings_result !== true && mysqli_num_rows($bookings_result) > 0): ?>
            <table class="info-table">
                <tr>
                    <th>Guard</th>
                    <th>Location</th>
                    <th>Hourly Rate</th>
                    <th>Status</th>
                    <th>Booked On</th>
                    <th></th>
                </tr>
                <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <tr>
                    <td><?php echo e($booking['guard_name']); ?></td>
                    <td><?php echo e($booking['location']); ?></td>
                    <td>$<?php echo number_format((float)$booking['hourly_rate'], 2); ?>/hr</td>
                    <td><?php echo e(ucfirst($booking['status'])); ?></td>
                    <td><?php echo e(date('F d, Y H:i', strtotime($booking['created_at']))); ?></td>
                    <td>
                        <?php if ($booking['status'] === 'pending'): ?>
                            <form method="post" onsubmit="return confirm('Cancel this booking?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                <button type="submit" name="cancel_booking" class="btn">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You have no bookings yet.</p>
        <?php endif; ?>
    </div>

    <!-- Guard Profile Modals -->
    <?php foreach ($guards as $guard): ?>
        

        <!-- Guard Modal -->
        <div id="guard-modal-<?php echo e($guard['id']); ?>" class="modal-card guard-profile-card">
            <a href="#" class="close-btn">&times;</a>

            <h3><?php echo e($guard['full_name']); ?></h3>

            
        <?php if (!empty($guard['profile_image'])): ?>
            <img src="../<?php echo htmlspecialchars($guard['profile_image']); ?>"
                    style="width:100%;border-radius:8px;margin-bottom:10px;">
        <?php endif; ?>

            <table class="info-table">
                <tr><th>Location:</th><td><?php echo e($guard['location']); ?></td></tr>
                <tr><th>Phone:</th><td><?php echo e($guard['phone']); ?></td></tr>
                <tr><th>Experience:</th><td><?php echo e($guard['experience']); ?> years</td></tr>
                <tr><th>Hourly Rate:</th><td>$<?php echo number_format($guard['hourly_rate'], 2); ?>/hr</td></tr>
                <tr><th>Skills:</th><td><?php echo nl2br(e($guard['skills'])); ?></td></tr>
                <tr><th>Specialties:</th><td><?php echo e($guard['specialties']); ?></td></tr>
            </table>

            <!-- Book This Guard Button (POST + CSRF token, so a link cannot book on someone's behalf) -->
            <form method="post" action="client_book_guards.php" style="margin-top:15px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="guard_id" value="<?php echo (int)$guard['id']; ?>">
                <button type="submit" class="btn">Book This Guard</button>
            </form>
        </div>
    <?php endforeach; ?>

</body>
</html>