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
<?php
    $v = function ($key) { return e($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST[$key] ?? '') : ''); };
    auth_start('Apply as a Guard', 'Join our verified security team. An admin reviews every application.', '../', 'badge', true);
?>

            <?php if ($showPopup): ?>
                <div class="alert alert-ok">
                    <strong>Application received.</strong> An admin will review it soon; you can log in to check your status.
                    <a href="guard_login.php">Go to login &rarr;</a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="full_name">Full name <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $v('full_name'); ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo $v('email'); ?>" required>
                    </div>
                    <div class="field">
                        <label for="phone">Phone <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $v('phone'); ?>" placeholder="01XXXXXXXXX" required>
                    </div>
                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <span class="hint">8+ characters, letters and numbers.</span>
                    </div>
                    <div class="field">
                        <label for="location">Location <span class="req">*</span></label>
                        <input type="text" id="location" name="location" value="<?php echo $v('location'); ?>" placeholder="e.g. Gulshan, Dhaka" required>
                    </div>
                    <div class="field">
                        <label for="experience">Experience (years) <span class="req">*</span></label>
                        <input type="number" id="experience" name="experience" value="<?php echo $v('experience'); ?>" min="0" max="60" required>
                    </div>
                    <div class="field">
                        <label for="hourly_rate">Hourly rate ($) <span class="req">*</span></label>
                        <input type="number" id="hourly_rate" name="hourly_rate" value="<?php echo $v('hourly_rate'); ?>" step="0.01" min="1" required>
                    </div>
                    <div class="field full">
                        <label for="skills">Skills <span class="req">*</span></label>
                        <textarea id="skills" name="skills" rows="2" placeholder="e.g. CCTV monitoring, first aid, crowd control" required><?php echo $v('skills'); ?></textarea>
                    </div>
                    <div class="field full">
                        <label for="specialties">Specialties <span class="req">*</span></label>
                        <input type="text" id="specialties" name="specialties" value="<?php echo $v('specialties'); ?>" placeholder="Comma separated, e.g. Night shift, Event security" required>
                    </div>
                    <div class="field full">
                        <label for="profile_image">Profile photo <span class="req">*</span></label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" required>
                        <span class="hint">JPG, PNG or GIF, up to 2 MB.</span>
                    </div>
                </div>
                <button type="submit" class="btn-gold">Submit application</button>
            </form>

            <p class="auth-foot">Already applied? <a href="guard_login.php">Log in</a></p>
<?php auth_end(); ?>
