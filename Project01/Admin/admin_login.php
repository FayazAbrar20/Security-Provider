<?php
session_start();
include "../db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email and Password are required.";
    } else {
        // Only allow admin login
        $sql = "SELECT user_id, email, password, role
                FROM users
                WHERE email='$email' AND role='admin'
                LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $user = $result ? mysqli_fetch_assoc($result) : null;

        // Verify hashed password
        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["role"] = $user["role"]; // 'admin'

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid admin email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Provider - Login</title>
    <link rel="stylesheet" href=".//login.css">
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
            color:white;
        }

        body {
            background-image: url("https://bc-user-uploads.brandcrowd.com/public/media-Production/84cb8f0c-efa9-43e9-9f06-bd99bde7b0f2/27cb0a83-1706-4400-8ec0-a8aa65293eb2.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color:white;
            margin-bottom: 0;
            font-family: 'Claus Eggers Sørensen', sans-serif;
        }

        .form {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 15px;
            align-items: center;
            gap: 12px;
        }

        label { width: 100px; }

        input[type="email"],
        input[type="password"] {
            flex: 1;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.35);
            outline: none;
        }

        .sub {
            font-size: 18px;
            padding: 8px 14px;
            align-items: center;
            color: black;
            background-color: white;
            border: none;
            cursor: pointer;
            border-radius: 10px;
        }

        input[type="submit"]:hover {
            background: black;
            color: white;
        }

        a {
            display: inline-block;
            margin: 2px;
            color: black;
            text-decoration: none;
            background-color: #f1cc75;
            padding: 10px 12px;
            border-radius: 60px;
        }

        a:hover {
            transform: scale(1.05);
        }

        .error-box{
            width: 520px;
            max-width: 92%;
            background: rgba(220, 53, 69, 0.92);
            padding: 10px;
            border-radius: 12px;
            margin: 15px auto 0;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <img class="img1" src=".//images/logo.png" height="80px" width="80px">
        <h1 class="nav1">SECURITY PROVIDER</h1>
        <a href=".//index.php">Back</a>
    </nav>

    <?php if ($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>

    <section class="form">
        <!-- IMPORTANT: POST + name attributes -->
        <form method="POST" action="admin_login.php">
            <h3>
                <div class="row">
                    <label for="email"><b>Email</b></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="row">
                    <label for="password"><b>Password</b></label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="row">
                    <a href=".//admin_registration.php">registration</a>
                    <input type="submit" value="Login" class="sub">
                </div>
            </h3>
        </form>
    </section>
</body>
</html>
