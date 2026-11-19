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
<?php
    $hour = (int)date('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $initial = strtoupper(mb_substr(trim($client['full_name']), 0, 1));
    $bookingRows = [];
    if ($bookings_result && $bookings_result !== true) {
        while ($row = mysqli_fetch_assoc($bookings_result)) {
            $bookingRows[] = $row;
        }
    }
    page_head('Client Dashboard', '../');
?>
<body>
    <div id="profile-view"></div>
    <div id="profile-edit"></div>
    <div id="password-change"></div>
    <div id="my-bookings-modal"></div>

    <aside class="sidebar">
        <a class="side-brand" href="../index.php">
            <img src="../uploads/index/Image (12).jpg" alt="">
            <span><strong>SECURITY PROVIDER</strong><small>Client</small></span>
        </a>
        <a href="#profile-view" class="sidebar-link"><img src="../uploads/clients/circle-user_9821720.png" alt=""> My Profile</a>
        <a href="#profile-edit" class="sidebar-link"><img src="../uploads/clients/pen-circle_18844818.png" alt=""> Edit Profile</a>
        <a href="#password-change" class="sidebar-link"><img src="../uploads/clients/password-lock_17525250.png" alt=""> Change Password</a>
        <a href="#my-bookings-modal" class="sidebar-link"><img src="../uploads/clients/booking_14703155.png" alt=""> My Bookings</a>
        <div class="logout-btn"><a href="client_logout.php">Log out</a></div>
    </aside>

    <main class="main-content">
        <div class="topline">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Search guards by name, location or specialty..." value="<?php echo e($search); ?>">
                <button type="submit" class="btn">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="client_dashboard.php" class="btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <section class="header">
            <div class="avatar"><?php echo e($initial); ?></div>
            <div>
                <div class="greet"><?php echo e($greeting); ?></div>
                <h2>Welcome, <?php echo e($client['full_name']); ?>!</h2>
                <p><?php echo e($client['email']); ?> &middot; <?php echo e(ucfirst($client['organization_type'])); ?> client</p>
            </div>
        </section>

        <section class="stats-container">
            <div class="stat-card accent-gold">
                <div class="stat-icon"><?php echo icon('calendar'); ?></div>
                <h3><?php echo e($total_bookings); ?></h3>
                <p>Total bookings</p>
            </div>
            <div class="stat-card accent-amber">
                <div class="stat-icon"><?php echo icon('clock'); ?></div>
                <h3><?php echo e($pending_bookings); ?></h3>
                <p>Waiting for the guard</p>
            </div>
            <div class="stat-card accent-green">
                <div class="stat-icon"><?php echo icon('check'); ?></div>
                <h3><?php echo e($confirmed_bookings); ?></h3>
                <p>Confirmed</p>
            </div>
        </section>

        <section class="guards-section">
            <div class="section-header">
                <h3><?php echo $search !== '' ? 'Search results' : 'Available security guards'; ?></h3>
                <span class="count"><?php echo count($guards); ?> verified guard<?php echo count($guards) === 1 ? '' : 's'; ?></span>
            </div>

            <?php if (!$guards): ?>
                <div class="empty">
                    <?php echo icon('search'); ?>
                    <p><?php echo $search !== '' ? 'No guards match "' . e($search) . '". Try another name, area or skill.' : 'No verified guards yet. Check back soon.'; ?></p>
                </div>
            <?php else: ?>
                <div class="guards-grid">
                    <?php foreach ($guards as $i => $guard): ?>
                        <?php
                            $specialties = array_filter(array_map('trim', explode(',', $guard['specialties'] ?? '')));
                            $img_src = "";
                            if (!empty($guard['profile_image']) && file_exists(dirname(__DIR__) . '/' . $guard['profile_image'])) {
                                $img_src = "../" . implode('/', array_map('rawurlencode', explode('/', $guard['profile_image'])));
                            }
                            $availability = $guard['availability_status'] ?? 'available';
                            $rating = isset($guard['rating']) ? (float)$guard['rating'] : 0.0;
                            $reviews = isset($guard['reviews_count']) ? (int)$guard['reviews_count'] : 0;
                            $stars = str_repeat('★', (int)round($rating)) . str_repeat('☆', 5 - (int)round($rating));
                        ?>
                        <article class="guard-card" style="animation-delay: <?php echo min($i, 8) * 0.05; ?>s">
                            <div class="guard-image-container">
                                <?php if ($img_src): ?>
                                    <img src="<?php echo e($img_src); ?>" alt="<?php echo e($guard['full_name']); ?>" class="guard-image">
                                <?php endif; ?>
                            </div>
                            <span class="availability-badge"><?php echo e($availability); ?></span>
                            <div class="guard-info">
                                <div class="guard-name"><?php echo e($guard['full_name']); ?></div>
                                <div class="guard-location"><?php echo e($guard['location']); ?> &middot; <?php echo (int)$guard['experience']; ?> yrs experience</div>
                                <div class="guard-rating">
                                    <span class="stars"><?php echo $stars; ?></span>
                                    <?php echo $reviews ? e(number_format($rating, 1)) . ' (' . $reviews . ')' : 'New guard'; ?>
                                </div>
                                <div class="guard-specialties">
                                    <?php foreach (array_slice($specialties, 0, 4) as $specialty): ?>
                                        <span class="specialty-tag"><?php echo e($specialty); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="guard-footer">
                                    <div class="guard-price">$<?php echo number_format((float)$guard['hourly_rate'], 0); ?><span class="price-unit"> /hr</span></div>
                                    <a href="#guard-modal-<?php echo (int)$guard['id']; ?>" class="view-profile-btn">View profile</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- My Profile -->
    <div class="modal-card profile-card">
        <a href="#" class="close-btn" aria-label="Close">&times;</a>
        <h3>My Profile</h3>
        <table class="info-table">
            <tr><th>Full name</th><td><?php echo e($client['full_name']); ?></td></tr>
            <tr><th>Email</th><td><?php echo e($client['email']); ?></td></tr>
            <tr><th>Phone</th><td><?php echo e($client['phone']); ?></td></tr>
            <tr><th>Address</th><td><?php echo e($client['address'] ?: 'Not provided'); ?></td></tr>
            <tr><th>Organization type</th><td><?php echo e(ucfirst($client['organization_type'])); ?></td></tr>
            <tr><th>Member since</th><td><?php echo e(date('F d, Y', strtotime($client['created_at']))); ?></td></tr>
        </table>
    </div>

    <!-- Edit Profile -->
    <div class="modal-card edit-card">
        <a href="#" class="close-btn" aria-label="Close">&times;</a>
        <h3>Edit Profile</h3>
        <?php if ($success): ?>
            <div class="success"><?php echo e($success); ?></div>
        <?php endif; ?>
        <form method="post" action="#profile-edit">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="ep-name">Full name</label>
                <input type="text" id="ep-name" name="name" value="<?php echo e($client['full_name']); ?>">
                <span class="error"><?php echo e($nameErr); ?></span>
            </div>
            <div class="field">
                <label for="ep-phone">Phone</label>
                <input type="text" id="ep-phone" name="phone" value="<?php echo e($client['phone']); ?>">
                <span class="error"><?php echo e($phoneErr); ?></span>
            </div>
            <div class="field">
                <label for="ep-address">Address</label>
                <textarea id="ep-address" name="address" rows="2"><?php echo e($client['address']); ?></textarea>
            </div>
            <div class="field">
                <label for="ep-org">Organization type</label>
                <select id="ep-org" name="org_type">
                    <?php foreach (['individual' => 'Individual', 'home' => 'Home', 'industry' => 'Industry'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php if ($client['organization_type'] === $value) echo 'selected'; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="update_profile" class="btn-gold">Save changes</button>
        </form>
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

    <!-- My Bookings -->
    <div class="bookings-card">
        <a href="#" class="close-btn" aria-label="Close">&times;</a>
        <h3>My Bookings</h3>
        <?php if ($bookingRows): ?>
            <table class="data-table">
                <thead>
                    <tr><th>Guard</th><th>Location</th><th>Rate</th><th>Status</th><th>Booked on</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($bookingRows as $booking): ?>
                        <tr>
                            <td><strong><?php echo e($booking['guard_name']); ?></strong></td>
                            <td class="muted"><?php echo e($booking['location']); ?></td>
                            <td>$<?php echo number_format((float)$booking['hourly_rate'], 2); ?>/hr</td>
                            <td><span class="chip chip-<?php echo e($booking['status']); ?>"><?php echo e($booking['status']); ?></span></td>
                            <td class="muted"><?php echo e(date('M d, Y H:i', strtotime($booking['created_at']))); ?></td>
                            <td>
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Cancel this booking?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                        <button type="submit" name="cancel_booking" class="btn-danger btn-sm">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty"><?php echo icon('calendar'); ?><p>No bookings yet. Pick a guard and book in one click.</p></div>
        <?php endif; ?>
    </div>

    <!-- Guard profiles -->
    <?php foreach ($guards as $guard): ?>
        <div id="guard-modal-<?php echo (int)$guard['id']; ?>" class="modal-card guard-profile-card">
            <a href="#" class="close-btn" aria-label="Close">&times;</a>
            <h3><?php echo e($guard['full_name']); ?></h3>
            <?php if (!empty($guard['profile_image']) && file_exists(dirname(__DIR__) . '/' . $guard['profile_image'])): ?>
                <img class="profile-photo" src="../<?php echo e($guard['profile_image']); ?>" alt="">
            <?php endif; ?>
            <table class="info-table">
                <tr><th>Location</th><td><?php echo e($guard['location']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo e($guard['phone']); ?></td></tr>
                <tr><th>Experience</th><td><?php echo (int)$guard['experience']; ?> years</td></tr>
                <tr><th>Hourly rate</th><td>$<?php echo number_format((float)$guard['hourly_rate'], 2); ?>/hr</td></tr>
                <tr><th>Skills</th><td><?php echo nl2br(e($guard['skills'])); ?></td></tr>
                <tr><th>Specialties</th><td><?php echo e($guard['specialties']); ?></td></tr>
            </table>
            <form method="post" action="client_book_guards.php" style="margin-top:18px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="guard_id" value="<?php echo (int)$guard['id']; ?>">
                <button type="submit" class="btn-gold">Book this guard</button>
            </form>
        </div>
    <?php endforeach; ?>

    <a href="#" class="modal-overlay" aria-label="Close"></a>
</body>
</html>
