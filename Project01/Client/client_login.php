<?php
session_start();
include "../db.php";

// Auto login using cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_client'])) {
    $user_id = mysqli_real_escape_string($conn, $_COOKIE['remember_client']);
    $sql = "SELECT * FROM users WHERE user_id='$user_id' AND role='client'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        header("Location: client_dashboard.php");
        exit();
    }
}

// Login submit
$error = "";
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE email='$email' AND role='client'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Auto login cookie
            if ($remember) {
                setcookie("remember_client", $user['user_id'], time() + (86400 * 7), "/");
            }

            // Remember email for UX
            setcookie("remember_email", $email, time() + (86400 * 30), "/");

            header("Location: client_dashboard.php");
            exit();
        }
    }

    $error = "Invalid email or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Login</title>
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f3f3f3;
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }
        .box{
            width:360px;
            background:white;
            border:1px solid #ccc;
            padding:20px;
        }
        h2{
            text-align:center;
            margin-top:0;
        }
        label{
            display:block;
            margin-top:10px;
            margin-bottom:5px;
            font-size:14px;
        }
        input[type="email"], input[type="password"]{
            width:100%;
            padding:10px;
            border:1px solid #aaa;
            box-sizing:border-box;
        }
        .remember{
            margin-top:10px;
            font-size:14px;
        }
        button{
            width:100%;
            padding:10px;
            margin-top:15px;
            background:#333;
            color:white;
            border:none;
            cursor:pointer;
        }
        button:hover{
            background:#111;
        }
        .error{
            background:#ffd6d6;
            border:1px solid #ff9f9f;
            color:#b30000;
            padding:10px;
            margin-bottom:10px;
            text-align:center;
            font-size:14px;
        }
        .link{
            text-align:center;
            margin-top:12px;
            font-size:14px;
        }
        .link a{
            color:blue;
            text-decoration:none;
        }
        .link a:hover{
            text-decoration:underline;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Client Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email</label>
        <input type="email" name="email"
               value="<?php echo $_COOKIE['remember_email'] ?? ''; ?>" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <div class="remember">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember" style="display:inline;">Remember Me</label>
        </div>

        <button type="submit" name="login">Login</button>

        <div class="link">
            Don't have an account? <a href="client_register.php">Register here</a>
        </div>
    </form>
</div>

</body>
</html>
