<?php
session_start();
include "../db.php";

// Auto login using cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_guard'])) {
    $user_id = mysqli_real_escape_string($conn, $_COOKIE['remember_guard']);
    $sql = "SELECT * FROM users WHERE user_id='$user_id' AND role='guard'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        header("Location: guard_dashboard.php");
        exit();
    }
}

// Login submit
$error = "";
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE email='$email' AND role='guard'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Auto login cookie
            if ($remember) {
                setcookie("remember_guard", $user['user_id'], time() + (86400 * 7), "/");
            }

            // Remember email for UX
            setcookie("remember_email_guard", $email, time() + (86400 * 30), "/");

            header("Location: guard_dashboard.php");
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
    <title>Guard Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        .login-box {
            background: #fff;
            padding: 40px;
            width: 420px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .login-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        .error {
            color: #dc3545;
            text-align: center;
            padding: 12px;
            background: #f8d7da;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <h2>Guard Login</h2>
        <p>Access your security guard account</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" 
                placeholder="Enter your email"
                value="<?php echo $_COOKIE['remember_email_guard'] ?? ''; ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" 
                placeholder="Enter your password" required>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>

        <button type="submit" name="login" class="btn btn-login">Login</button>

        <div class="register-link">
            Don't have an account? <a href="guard_register.php">Register as Guard</a>
        </div>
    </form>
</div>

</body>
</html>
