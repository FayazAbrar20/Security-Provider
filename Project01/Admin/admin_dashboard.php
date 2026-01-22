<?php
session_start();
include "../db.php";

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$msg = "";

// Approve / Reject action (POST, no JS)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["guard_id"])) {
    $guard_id = (int)$_POST["guard_id"];
    $action = $_POST["action"];

    if ($action === "approve") {
        $status = "approved";
    } elseif ($action === "reject") {
        $status = "rejected";
    } else {
        $status = "";
    }

    if ($status !== "") {
        $sql = "UPDATE guards SET verification_status='$status' WHERE id='$guard_id' LIMIT 1";
        if (mysqli_query($conn, $sql)) {
            $msg = "Guard status updated to: " . htmlspecialchars($status);
        } else {
            $msg = "Database error: " . mysqli_error($conn);
        }
    }
}

// Load pending guards
$pending_sql = "SELECT g.*, u.email
                FROM guards g
                JOIN users u ON u.user_id = g.user_id
                WHERE g.verification_status='pending'
                ORDER BY g.created_at DESC";
$pending_result = mysqli_query($conn, $pending_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Guard Approvals</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .topbar {
            background: #2c3e50;
            color: #fff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            background: #e74c3c;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 16px;
        }

        .msg {
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            background: #d4edda;
            border: 1px solid #9fd3a9;
            color: #155724;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f0f2f5;
            font-weight: bold;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f1c40f;
            color: #222;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            border: none;
            padding: 8px 10px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-approve { background: #27ae60; color: #fff; }
        .btn-reject  { background: #c0392b; color: #fff; }

        .muted { color: #666; font-size: 13px; }

        img.guard-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>

    <div class="topbar">
        <div>
            <strong>Admin Dashboard</strong>
            <div class="muted">Approve or reject guard registrations</div>
        </div>
        <a href="admin_logout.php">Logout</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>Pending Guard Requests</h2>

            <?php if ($msg): ?>
                <div class="msg"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if (!$pending_result || mysqli_num_rows($pending_result) === 0): ?>
                <p class="muted" style="margin-top:10px;">No pending guards right now.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Guard Info</th>
                            <th>Contact</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($g = mysqli_fetch_assoc($pending_result)): ?>
                            <?php
                                // Build image path (admin_dashboard.php is in root)
                                $img_src = "";
                                if (!empty($g['profile_image'])) {
                                    $fs = __DIR__ . "/" . $g['profile_image']; // filesystem for file_exists() [web:204]
                                    if (file_exists($fs)) {
                                        $img_src = $g['profile_image']; // browser path
                                    }
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php if ($img_src): ?>
                                        <img class="guard-thumb" src="<?php echo $img_src; ?>" alt="Guard">
                                    <?php else: ?>
                                        <div class="muted">No image</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div><strong><?php echo htmlspecialchars($g['full_name']); ?></strong></div>
                                    <div class="muted"><?php echo htmlspecialchars($g['location']); ?></div>
                                </td>

                                <td>
                                    <div><?php echo htmlspecialchars($g['email']); ?></div>
                                    <div class="muted"><?php echo htmlspecialchars($g['phone']); ?></div>
                                </td>

                                <td>
                                    <div class="muted">Experience: <?php echo (int)$g['experience']; ?> years</div>
                                    <div class="muted">Rate: $<?php echo number_format((float)$g['hourly_rate'], 2); ?>/hr</div>
                                    <div class="muted">Specialties: <?php echo htmlspecialchars($g['specialties']); ?></div>
                                </td>

                                <td>
                                    <span class="status-pill"><?php echo htmlspecialchars($g['verification_status']); ?></span>
                                </td>

                                <td>
                                    <div class="actions">
                                        <form method="post">
                                            <input type="hidden" name="guard_id" value="<?php echo (int)$g['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve">Approve</button>
                                        </form>

                                        <form method="post">
                                            <input type="hidden" name="guard_id" value="<?php echo (int)$g['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-reject">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
