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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .popup-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .popup-overlay.show{ display: flex; }
        .popup-box{
            max-width: 420px;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
        }
    </style>
</head>

<body class="p-5">

<div class="popup-overlay <?php echo $showPopup ? 'show' : ''; ?>">
    <div class="popup-box">
        <h4>Registration successful</h4>
        <p>Wait for admin approval.</p>
        <div class="d-flex gap-2 justify-content-end">
            <a href="guard_login.php" class="btn btn-success">Login</a>
            <a href="guard_register.php" class="btn btn-secondary">Close</a>
        </div>
    </div>
</div>

<div class="container" style="max-width:600px">
    <h2>Guard Registration</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control" required></div>
        <div class="mb-3"><label>Location</label><input type="text" name="location" class="form-control" required></div>
        <div class="mb-3"><label>Experience (years)</label><input type="number" name="experience" class="form-control" required></div>
        <div class="mb-3"><label>Skills</label><textarea name="skills" class="form-control" required></textarea></div>
        <div class="mb-3"><label>Hourly Rate ($)</label><input type="number" step="0.01" name="hourly_rate" class="form-control" required></div>
        <div class="mb-3"><label>Specialties</label><input type="text" name="specialties" class="form-control" required></div>
        <div class="mb-3"><label>Profile Image</label><input type="file" name="profile_image" class="form-control" accept="image/*" required></div>
        <button class="btn btn-success">Register</button>
    </form>
</div>

</body>
</html>
