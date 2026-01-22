<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: client_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$currentErr = $newErr = $confirmErr = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    $current = $_POST["current_password"];
    $new = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];

    // Get current password from database
    $sql = "SELECT password FROM users WHERE user_id='$user_id'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if (!password_verify($current, $user['password'])) {
        $currentErr = "Current password is incorrect";
        $valid = false;
    }

    if (strlen($new) < 6) {
        $newErr = "Password must be at least 6 characters";
        $valid = false;
    }

    if ($new !== $confirm) {
        $confirmErr = "Passwords do not match";
        $valid = false;
    }

    if ($valid) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password='$hashed' WHERE user_id='$user_id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Password changed successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error { color: #dc3545; font-size: 14px; }
        .success { 
            color: #28a745; 
            padding: 10px; 
            background: #d4edda; 
            border-radius: 5px; 
            margin-bottom: 15px; 
        }
    </style>
</head>
<body style="padding:50px">

<div class="container" style="max-width:500px">
    <h2 class="mb-4">Change Password</h2>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Current Password:</label>
            <input type="password" name="current_password" class="form-control">
            <span class="error"><?php echo $currentErr; ?></span>
        </div>

        <div class="mb-3">
            <label>New Password:</label>
            <input type="password" name="new_password" class="form-control">
            <span class="error"><?php echo $newErr; ?></span>
        </div>

        <div class="mb-3">
            <label>Confirm New Password:</label>
            <input type="password" name="confirm_password" class="form-control">
            <span class="error"><?php echo $confirmErr; ?></span>
        </div>

        <button type="submit" class="btn btn-warning">Change Password</button>
        <a href="client_profile.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
