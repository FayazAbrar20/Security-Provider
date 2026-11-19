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
<?php auth_start('Create Client Account', 'Book trusted security for your home, business or event.', '../', 'user', true); ?>

            <?php if ($success): ?>
                <div class="alert alert-ok"><?php echo e($success); ?> <a href="client_login.php">Log in &rarr;</a></div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="name">Full name <span class="req">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo e($name); ?>" placeholder="As on your ID">
                        <span class="error"><?php echo e($nameErr); ?></span>
                    </div>
                    <div class="field">
                        <label for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" placeholder="you@example.com">
                        <span class="error"><?php echo e($emailErr); ?></span>
                    </div>
                    <div class="field">
                        <label for="phone">Phone <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo e($phone); ?>" placeholder="01XXXXXXXXX">
                        <span class="error"><?php echo e($phoneErr); ?></span>
                    </div>
                    <div class="field full">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="2" placeholder="Area, city"><?php echo e($address); ?></textarea>
                    </div>
                    <div class="field full">
                        <label for="org_type">I need security for</label>
                        <select id="org_type" name="org_type">
                            <option value="individual" <?php if ($org_type == 'individual') echo 'selected'; ?>>Myself (individual)</option>
                            <option value="home" <?php if ($org_type == 'home') echo 'selected'; ?>>My home</option>
                            <option value="industry" <?php if ($org_type == 'industry') echo 'selected'; ?>>A business or industry</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password">
                        <span class="error"><?php echo e($passwordErr); ?></span>
                        <span class="hint">8+ characters, letters and numbers.</span>
                    </div>
                    <div class="field">
                        <label for="confirm">Confirm password <span class="req">*</span></label>
                        <input type="password" id="confirm" name="confirm">
                        <span class="error"><?php echo e($confirmErr); ?></span>
                    </div>
                </div>
                <button type="submit" class="btn-gold">Create account</button>
            </form>

            <p class="auth-foot">Already have an account? <a href="client_login.php">Log in</a></p>
<?php auth_end(); ?>
