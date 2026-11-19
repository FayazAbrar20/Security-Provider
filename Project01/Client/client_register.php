<?php
include "../db.php";

$nameErr = $emailErr = $phoneErr = $passwordErr = $confirmErr = "";
$name = $email = $phone = $password = $confirm = $address = $org_type = "";
$success = "";
$allowedOrgTypes = ['home', 'industry', 'individual'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    // Full name
    $name = trim($_POST["name"] ?? "");
    if ($name === "") {
        $nameErr = "Name is required";
        $valid = false;
    } elseif (!preg_match("/^[a-zA-Z-' ]{2,100}$/", $name)) {
        $nameErr = "Only letters, spaces, - and ' allowed (2-100 characters)";
        $valid = false;
    }

    // Email
    $email = trim($_POST["email"] ?? "");
    if ($email === "") {
        $emailErr = "Email is required";
        $valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
        $valid = false;
    } elseif (db_one($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1", [$email])) {
        $emailErr = "Email already registered";
        $valid = false;
    }

    // Phone
    $phone = trim($_POST["phone"] ?? "");
    if ($phone === "") {
        $phoneErr = "Phone is required";
        $valid = false;
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $phoneErr = "Invalid phone number (10-15 digits)";
        $valid = false;
    }

    // Password
    $password = $_POST["password"] ?? "";
    if ($password === "") {
        $passwordErr = "Password is required";
        $valid = false;
    } elseif (($problem = password_problem($password)) !== "") {
        $passwordErr = $problem;
        $valid = false;
    }

    // Confirm password
    $confirm = $_POST["confirm"] ?? "";
    if ($confirm === "") {
        $confirmErr = "Please confirm password";
        $valid = false;
    } elseif ($password !== $confirm) {
        $confirmErr = "Passwords do not match";
        $valid = false;
    }

    $address = trim($_POST["address"] ?? "");
    $org_type = $_POST["org_type"] ?? "";
    if (!in_array($org_type, $allowedOrgTypes, true)) {
        $org_type = "individual";
    }

    if ($valid) {
        // Both rows are written together, or neither is.
        mysqli_begin_transaction($conn);
        $ok = db_query($conn,
            "INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'client', NOW())",
            [$email, password_hash($password, PASSWORD_DEFAULT)]);

        if ($ok) {
            $user_id = mysqli_insert_id($conn);
            $ok = db_query($conn,
                "INSERT INTO clients (user_id, full_name, phone, address, organization_type, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$user_id, $name, $phone, $address, $org_type]);
        }

        if ($ok) {
            mysqli_commit($conn);
            $success = "Registration successful! You can now login.";
            $name = $email = $phone = $address = $org_type = "";
        } else {
            mysqli_rollback($conn);
            $emailErr = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Registration</title>
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f3f3f3;
            padding:40px 10px;
        }
        .box{
            max-width:450px;
            margin:0 auto;
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
        input[type="text"], input[type="email"], input[type="password"], textarea, select{
            width:100%;
            padding:10px;
            border:1px solid #aaa;
            box-sizing:border-box;
            font-size:14px;
        }
        textarea{
            resize:vertical;
        }
        .error{
            color:#b30000;
            font-size:13px;
            margin-top:3px;
            display:block;
        }
        .success{
            background:#d4edda;
            border:1px solid #9fd3a9;
            color:#155724;
            padding:10px;
            margin-bottom:10px;
            text-align:center;
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
        .required{
            color:#b30000;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Client Registration</h2>

    <?php if ($success): ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form method="post">
<?php echo csrf_field(); ?>
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="name" value="<?php echo e($name); ?>">
        <span class="error"><?php echo e($nameErr); ?></span>

        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" value="<?php echo e($email); ?>">
        <span class="error"><?php echo e($emailErr); ?></span>

        <label>Phone <span class="required">*</span></label>
        <input type="text" name="phone" value="<?php echo e($phone); ?>">
        <span class="error"><?php echo e($phoneErr); ?></span>

        <label>Address</label>
        <textarea name="address" rows="3"><?php echo e($address); ?></textarea>

        <label>Organization Type</label>
        <select name="org_type">
            <option value="">Select</option>
            <option value="home" <?php if($org_type=='home') echo 'selected'; ?>>Home</option>
            <option value="industry" <?php if($org_type=='industry') echo 'selected'; ?>>Industry</option>
            <option value="individual" <?php if($org_type=='individual') echo 'selected'; ?>>Individual</option>
        </select>

        <label>Password <span class="required">*</span></label>
        <input type="password" name="password">
        <span class="error"><?php echo e($passwordErr); ?></span>

        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm">
        <span class="error"><?php echo e($confirmErr); ?></span>

        <button type="submit">Register</button>

        <div class="link">
            Already have an account? <a href="client_login.php">Login here</a>
        </div>
    </form>
</div>

</body>
</html>
