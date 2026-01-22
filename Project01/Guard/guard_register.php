<?php
include "../db.php";

$error = "";

// Show popup after redirect
$showPopup = isset($_GET['registered']) && $_GET['registered'] === '1';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ===============================
      BASIC USER DATA
    =============================== */
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    /* ===============================
      IMAGE UPLOAD (FIXED & CLEAN)
      Uploads to: Project01/uploads/guards/
      DB stores: uploads/guards/filename.jpg
    =============================== */
    if (empty($_FILES['profile_image']['name'])) {
        $error = "Profile image is required.";
    } else {

        // Project root (one level up from Guard/)
        $project_root = realpath(__DIR__ . "/..");

        // Filesystem path
        $upload_dir_fs = $project_root . "/uploads/guards/";

        // DB / web path
        $upload_dir_db = "uploads/guards/";

        if (!is_dir($upload_dir_fs)) {
            mkdir($upload_dir_fs, 0775, true);
        }

        $original_name = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG, GIF images are allowed.";
        } else {

            // IMPORTANT: sanitize filename ONCE
            $safe_name = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $original_name);
            $image_name = time() . "_" . $safe_name;

            $target_fs = $upload_dir_fs . $image_name;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_fs)) {
                $error = "Image upload failed. Check folder permission.";
            } else {
                // THIS is what goes into DB
                $image_path = $upload_dir_db . $image_name;
            }
        }
    }

    /* ===============================
      DATABASE INSERT
    =============================== */
    if ($error === "") {

        // Insert into users
        $sql1 = "INSERT INTO users (email, password, role, created_at)
                 VALUES ('$email', '$password', 'guard', NOW())";

        if (!mysqli_query($conn, $sql1)) {
            $error = "User registration failed: " . mysqli_error($conn);
        } else {

            $user_id = mysqli_insert_id($conn);

            // Guard details
            $full_name   = mysqli_real_escape_string($conn, $_POST['full_name']);
            $phone       = mysqli_real_escape_string($conn, $_POST['phone']);
            $location    = mysqli_real_escape_string($conn, $_POST['location']);
            $experience  = (int)$_POST['experience'];
            $skills      = mysqli_real_escape_string($conn, $_POST['skills']);
            $hourly_rate = (float)$_POST['hourly_rate'];
            $specialties = mysqli_real_escape_string($conn, $_POST['specialties']);

            $sql2 = "INSERT INTO guards
                    (user_id, full_name, phone, location, experience, skills, hourly_rate, specialties, profile_image, verification_status, created_at)
                    VALUES
                    ('$user_id', '$full_name', '$phone', '$location', '$experience', '$skills', '$hourly_rate', '$specialties', '$image_path', 'pending', NOW())";

            if (!mysqli_query($conn, $sql2)) {
                // rollback user
                mysqli_query($conn, "DELETE FROM users WHERE user_id='$user_id'");
                $error = "Guard registration failed: " . mysqli_error($conn);
            } else {
                header("Location: guard_register.php?registered=1");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Guard Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f2f2f2 0%, #ffffff 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .register-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #fdfdfd 0%, #ffffff 100%);
            color: black;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .register-form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4ebeee 0%, #3099c0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }
        
        .error {
            background: #f8d7da;
            color: #dc3545;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Success popup styles */
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .popup-overlay.show { display: flex; }
        
        .popup-box {
            max-width: 420px;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .popup-box h4 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        .popup-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-success, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            transition: 0.3s;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .register-form { padding: 20px; }
            .popup-buttons { flex-direction: column; }
        }
    </style>
</head>

<body>

<!-- Success popup (CSS-only) -->
<div class="popup-overlay <?php echo $showPopup ? 'show' : ''; ?>">
    <div class="popup-box">
        <h4>Registration successful</h4>
        <p>Wait for admin approval.</p>
        <div class="popup-buttons">
            <a href="guard_login.php" class="btn-success">Login</a>
            <a href="guard_register.php" class="btn-secondary">Close</a>
        </div>
    </div>
</div>

<div class="register-container">
    <div class="register-header">
        <h2>Guard Registration</h2>
        <p>Join our security platform</p>
    </div>
    
    <div class="register-form">
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Experience (years)</label>
                <input type="number" name="experience" class="form-control" min="0" max="50" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Skills</label>
                <textarea name="skills" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Hourly Rate ($)</label>
                <input type="number" step="0.01" name="hourly_rate" class="form-control" min="5" max="100" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Specialties</label>
                <input type="text" name="specialties" class="form-control" placeholder="e.g., Night shift, Event security" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Profile Image</label>
                <input type="file" name="profile_image" class="form-control" accept="image/*" required>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
    </div>
</div>

</body>
</html>
