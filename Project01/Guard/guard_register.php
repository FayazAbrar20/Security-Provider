<?php
include "../db.php";

$error = "";

// Show popup after redirect
$showPopup = isset($_GET['registered']) && $_GET['registered'] === '1';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ===============================
      VALIDATE FORM FIELDS
    =============================== */
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $full_name   = trim($_POST['full_name'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $experience  = (int)($_POST['experience'] ?? 0);
    $skills      = trim($_POST['skills'] ?? '');
    $hourly_rate = (float)($_POST['hourly_rate'] ?? 0);
    $specialties = trim($_POST['specialties'] ?? '');

    if ($email === '' || $password === '' || $full_name === '' || $phone === '' || $location === '') {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (($problem = password_problem($password)) !== '') {
        $error = $problem;
    } elseif (!preg_match("/^[a-zA-Z-' ]{2,100}$/", $full_name)) {
        $error = "Full name: only letters, spaces, - and ' allowed.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Invalid phone number (10-15 digits).";
    } elseif ($experience < 0 || $experience > 60) {
        $error = "Experience must be between 0 and 60 years.";
    } elseif ($hourly_rate <= 0) {
        $error = "Hourly rate must be greater than 0.";
    } elseif (db_one($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1", [$email])) {
        $error = "This email is already registered.";
    }

    /* ===============================
      IMAGE UPLOAD
      Uploads to: Project01/uploads/guards/
      DB stores: uploads/guards/<random>.<ext>
    =============================== */
    $image_path = "";
    $target_fs = "";
    if ($error === "") {
        $file = $_FILES['profile_image'] ?? null;
        $allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif'];

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $error = "Profile image is required.";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Image upload failed. Please try again.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $error = "Image must be 2 MB or smaller.";
        } else {
            // Check the file's actual content, not just its name.
            $info = @getimagesize($file['tmp_name']);
            $type = $info[2] ?? null;

            if (!isset($allowedTypes[$type])) {
                $error = "Only JPG, PNG and GIF images are allowed.";
            } else {
                $upload_dir_fs = realpath(__DIR__ . "/..") . "/uploads/guards/";
                if (!is_dir($upload_dir_fs)) {
                    mkdir($upload_dir_fs, 0775, true);
                }

                // Random file name: the original name is never used on disk.
                $image_name = bin2hex(random_bytes(16)) . "." . $allowedTypes[$type];
                $target_fs = $upload_dir_fs . $image_name;

                if (!move_uploaded_file($file['tmp_name'], $target_fs)) {
                    $error = "Image upload failed. Please try again.";
                } else {
                    $image_path = "uploads/guards/" . $image_name;
                }
            }
        }
    }

    /* ===============================
      DATABASE INSERT (both rows or neither)
    =============================== */
    if ($error === "") {
        mysqli_begin_transaction($conn);

        $ok = db_query($conn,
            "INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'guard', NOW())",
            [$email, password_hash($password, PASSWORD_DEFAULT)]);

        if ($ok) {
            $user_id = mysqli_insert_id($conn);
            $ok = db_query($conn,
                "INSERT INTO guards
                    (user_id, full_name, phone, location, experience, skills, hourly_rate, specialties, profile_image, verification_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [$user_id, $full_name, $phone, $location, $experience, $skills, $hourly_rate, $specialties, $image_path]);
        }

        if ($ok) {
            mysqli_commit($conn);
            header("Location: guard_register.php?registered=1");
            exit();
        }

        mysqli_rollback($conn);
        if ($target_fs !== "" && is_file($target_fs)) {
            unlink($target_fs);
        }
        $error = "Registration failed. Please try again.";
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
            <div class="error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
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
