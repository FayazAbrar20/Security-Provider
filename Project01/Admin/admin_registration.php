<?php
session_start();
include "../db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = mysqli_real_escape_string($conn, trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($email === "" || $password === "" || $confirm === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $checkSql = "SELECT user_id FROM users WHERE email='$email' LIMIT 1";
        $checkRes = mysqli_query($conn, $checkSql);

        if ($checkRes && mysqli_num_rows($checkRes) > 0) {
            $error = "This email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT); // store hashed password [web:256]

            $sql = "INSERT INTO users (email, password, role, created_at)
                    VALUES ('$email', '$hashed', 'admin', NOW())";

            if (mysqli_query($conn, $sql)) {
                $success = "Admin registration successful! You can login now.";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Provider - Admin Registration</title>
    <link rel="stylesheet" href=".//registration.css">
    <style>
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 40px;
            position: sticky;
            top: 0;
            background: rgba(16, 15, 15, 0.3);
            backdrop-filter: blur(10px);
            z-index: 1000;
            color: white;
        }

        body {
            background-image: url("https://bc-user-uploads.brandcrowd.com/public/media-Production/84cb8f0c-efa9-43e9-9f06-bd99bde7b0f2/27cb0a83-1706-4400-8ec0-a8aa65293eb2.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: white;
            margin-bottom: 0;
            font-family: 'Claus Eggers Sørensen', sans-serif;
        }

        .form1 {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 85vh;
            text-align: center;
            font-size: 22px;
        }

        .card {
            width: 520px;
            max-width: 92%;
            background: rgba(0,0,0,0.45);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 18px;
            padding: 25px 25px 18px;
            backdrop-filter: blur(6px);
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            align-items: center;
            gap: 12px;
        }

        label { width: 140px; text-align: left; }

        input[type="email"], input[type="password"] {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            outline: none;
        }

        .sub {
            font-size: 18px;
            padding: 10px 18px;
            align-items: center;
            color: black;
            background-color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .sub:hover { background: black; color: white; }

        a {
            display: inline-block;
            margin: 2px;
            color: black;
            text-decoration: none;
            background-color: #f1cc75;
            padding: 10px 14px;
            border-radius: 60px;
        }

        a:hover { transform: scale(1.05); }

        .msg-error {
            background: rgba(220, 53, 69, 0.95);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .msg-success {
            background: rgba(40, 167, 69, 0.95);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 12px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <img class="img1" src=".//images/logo.png" height="80px" width="80px">
        <h1 class="nav1">SECURITY PROVIDER</h1>
        <a href=".//index.php">Back</a>
    </nav>

    <section class="form1">
        <div class="card">
            <h2 style="margin-top:0;">Admin Registration</h2>

            <?php if ($error): ?>
                <div class="msg-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="msg-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_registration.php">
                <div class="row">
                    <label for="email"><b>Email</b></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="row">
                    <label for="password"><b>Password</b></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="row">
                    <label for="confirm_password"><b>Confirm</b></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="actions">
                    <a href="admin_login.php">Back to Login</a>
                    <input type="submit" value="Register" class="sub">
                </div>
            </form>
        </div>
    </section>
</body>
</html>
