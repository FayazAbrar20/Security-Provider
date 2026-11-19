<?php
include "../db.php";

// Already logged in, or a valid remember-me cookie: go to the dashboard.
if ((isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client')
    || remember_login($conn, 'remember_client', 'client')) {
    header("Location: client_dashboard.php");
    exit();
}

$error = "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $user = db_one($conn,
        "SELECT user_id, email, password, role FROM users WHERE email = ? AND role = 'client' LIMIT 1",
        [$email]);

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);

        if ($remember) {
            remember_issue($conn, (int)$user['user_id'], 'remember_client');
        }

        // Remember the email (not the password) to pre-fill the form next time.
        setcookie("remember_email", $email, ['expires' => time() + 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

        header("Location: client_dashboard.php");
        exit();
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
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post">
<?php echo csrf_field(); ?>
        <label>Email</label>
        <input type="email" name="email"
               value="<?php echo e($_COOKIE['remember_email'] ?? ''); ?>" required>

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
