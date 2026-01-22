<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: client_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT c.* FROM clients c WHERE c.user_id='$user_id'";
$result = mysqli_query($conn, $sql);
$client = mysqli_fetch_assoc($result);

$nameErr = $phoneErr = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    $name = mysqli_real_escape_string($conn, trim($_POST["name"]));
    $phone = mysqli_real_escape_string($conn, trim($_POST["phone"]));
    $address = mysqli_real_escape_string($conn, trim($_POST["address"]));
    $org_type = mysqli_real_escape_string($conn, $_POST["org_type"]);

    if (empty($name)) {
        $nameErr = "Name is required";
        $valid = false;
    }

    if (empty($phone) || !preg_match("/^[0-9]{10,15}$/", $phone)) {
        $phoneErr = "Invalid phone number";
        $valid = false;
    }

    if ($valid) {
        $sql = "UPDATE clients SET
                full_name='$name',
                phone='$phone',
                address='$address',
                organization_type='$org_type'
                WHERE user_id='$user_id'";

        if (mysqli_query($conn, $sql)) {
            $success = "Profile updated successfully!";
            $client['full_name'] = $name;
            $client['phone'] = $phone;
            $client['address'] = $address;
            $client['organization_type'] = $org_type;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f3f3f3;
            padding:40px 10px;
        }
        .box{
            max-width:600px;
            margin:0 auto;
            background:#fff;
            border:1px solid #ccc;
            padding:20px;
        }
        h2{
            margin-top:0;
            margin-bottom:15px;
        }
        .success{
            color:#155724;
            background:#d4edda;
            border:1px solid #9fd3a9;
            padding:10px;
            margin-bottom:15px;
            font-size:14px;
        }
        .error{
            color:#b30000;
            font-size:13px;
            display:block;
            margin-top:4px;
        }
        .field{
            margin-bottom:12px;
        }
        .field label{
            display:block;
            margin-bottom:6px;
            font-size:14px;
        }
        input[type="text"], textarea, select{
            width:100%;
            padding:10px;
            border:1px solid #aaa;
            box-sizing:border-box;
            font-size:14px;
        }
        textarea{
            resize:vertical;
        }
        .actions{
            margin-top:15px;
            display:flex;
            gap:10px;
        }
        .btn{
            padding:10px 14px;
            border:none;
            cursor:pointer;
            text-decoration:none;
            display:inline-block;
            font-size:14px;
            text-align:center;
        }
        .btn-primary{
            background:#333;
            color:#fff;
        }
        .btn-primary:hover{
            background:#111;
        }
        .btn-secondary{
            background:#777;
            color:#fff;
        }
        .btn-secondary:hover{
            background:#555;
        }
    </style>
</head>

<body>

<div class="box">
    <h2>Edit Profile</h2>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo $client['full_name']; ?>">
            <span class="error"><?php echo $nameErr; ?></span>
        </div>

        <div class="field">
            <label>Phone:</label>
            <input type="text" name="phone" value="<?php echo $client['phone']; ?>">
            <span class="error"><?php echo $phoneErr; ?></span>
        </div>

        <div class="field">
            <label>Address:</label>
            <textarea name="address" rows="3"><?php echo $client['address']; ?></textarea>
        </div>

        <div class="field">
            <label>Organization Type:</label>
            <select name="org_type">
                <option value="home" <?php if($client['organization_type']=='home') echo 'selected'; ?>>Home</option>
                <option value="industry" <?php if($client['organization_type']=='industry') echo 'selected'; ?>>Industry</option>
                <option value="individual" <?php if($client['organization_type']=='individual') echo 'selected'; ?>>Individual</option>
            </select>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Update Profile</button>
            <a href="client_profile.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
