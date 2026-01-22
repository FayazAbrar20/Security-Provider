<?php
include "../db.php";

$nameErr = $emailErr = $phoneErr = $passwordErr = $confirmErr = "";
$name = $email = $phone = $password = $confirm = $address = $org_type = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    // Validate Full Name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
        $valid = false;
    } else {
        $name = mysqli_real_escape_string($conn, trim($_POST["name"]));
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $nameErr = "Only letters and white space allowed";
            $valid = false;
        }
    }

    // Validate Email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
        $valid = false;
    } else {
        $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
            $valid = false;
        } else {
            $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $emailErr = "Email already registered";
                $valid = false;
            }
        }
    }

    // Validate Phone
    if (empty($_POST["phone"])) {
        $phoneErr = "Phone is required";
        $valid = false;
    } else {
        $phone = mysqli_real_escape_string($conn, trim($_POST["phone"]));
        if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
            $phoneErr = "Invalid phone number (10-15 digits)";
            $valid = false;
        }
    }

    // Validate Password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
        $valid = false;
    } else {
        $password = $_POST["password"];
        if (strlen($password) < 6) {
            $passwordErr = "Password must be at least 6 characters";
            $valid = false;
        }
    }

    // Validate Confirm Password
    if (empty($_POST["confirm"])) {
        $confirmErr = "Please confirm password";
        $valid = false;
    } else {
        $confirm = $_POST["confirm"];
        if ($password !== $confirm) {
            $confirmErr = "Passwords do not match";
            $valid = false;
        }
    }

    $address = mysqli_real_escape_string($conn, trim($_POST["address"]));
    $org_type = mysqli_real_escape_string($conn, $_POST["org_type"]);

    // If all validations pass
    if ($valid) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql1 = "INSERT INTO users (email, password, role, created_at)
                 VALUES ('$email', '$hashed_password', 'client', NOW())";

        if (mysqli_query($conn, $sql1)) {
            $user_id = mysqli_insert_id($conn);

            $sql2 = "INSERT INTO clients (user_id, full_name, phone, address, organization_type, created_at)
                     VALUES ('$user_id', '$name', '$phone', '$address', '$org_type', NOW())";

            if (mysqli_query($conn, $sql2)) {
                $success = "Registration successful! You can now login.";
                $name = $email = $phone = $address = "";
                $org_type = "";
            }
        } else {
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
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="name" value="<?php echo $name; ?>">
        <span class="error"><?php echo $nameErr; ?></span>

        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" value="<?php echo $email; ?>">
        <span class="error"><?php echo $emailErr; ?></span>

        <label>Phone <span class="required">*</span></label>
        <input type="text" name="phone" value="<?php echo $phone; ?>">
        <span class="error"><?php echo $phoneErr; ?></span>

        <label>Address</label>
        <textarea name="address" rows="3"><?php echo $address; ?></textarea>

        <label>Organization Type</label>
        <select name="org_type">
            <option value="">Select</option>
            <option value="home" <?php if($org_type=='home') echo 'selected'; ?>>Home</option>
            <option value="industry" <?php if($org_type=='industry') echo 'selected'; ?>>Industry</option>
            <option value="individual" <?php if($org_type=='individual') echo 'selected'; ?>>Individual</option>
        </select>

        <label>Password <span class="required">*</span></label>
        <input type="password" name="password">
        <span class="error"><?php echo $passwordErr; ?></span>

        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm">
        <span class="error"><?php echo $confirmErr; ?></span>

        <button type="submit">Register</button>

        <div class="link">
            Already have an account? <a href="client_login.php">Login here</a>
        </div>
    </form>
</div>

</body>
</html>
