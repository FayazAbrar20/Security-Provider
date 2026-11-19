<?php
include "../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: client_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$client = db_one($conn, "SELECT c.* FROM clients c WHERE c.user_id = ?", [$user_id]);

$nameErr = $phoneErr = "";
$success = "";
$allowedOrgTypes = ['home', 'industry', 'individual'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $org_type = $_POST["org_type"] ?? "";
    if (!in_array($org_type, $allowedOrgTypes, true)) {
        $org_type = $client['organization_type'] ?? 'individual';
    }

    if (!preg_match("/^[a-zA-Z-' ]{2,100}$/", $name)) {
        $nameErr = "Name is required (letters, spaces, - and ' only)";
        $valid = false;
    }

    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $phoneErr = "Invalid phone number";
        $valid = false;
    }

    if ($valid) {
        $ok = db_query($conn,
            "UPDATE clients SET full_name = ?, phone = ?, address = ?, organization_type = ? WHERE user_id = ?",
            [$name, $phone, $address, $org_type, $user_id]);

        if ($ok) {
            $success = "Profile updated successfully!";
            $client['full_name'] = $name;
            $client['phone'] = $phone;
            $client['address'] = $address;
            $client['organization_type'] = $org_type;
        }
    }
}
?>
<?php auth_start('Edit Profile', 'Keep your contact details up to date.', '../', 'user'); ?>

            <?php if ($success): ?>
                <div class="alert alert-ok"><?php echo e($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($client['full_name'] ?? ''); ?>">
                    <span class="error"><?php echo e($nameErr); ?></span>
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo e($client['phone'] ?? ''); ?>">
                    <span class="error"><?php echo e($phoneErr); ?></span>
                </div>
                <div class="field">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="2"><?php echo e($client['address'] ?? ''); ?></textarea>
                </div>
                <div class="field">
                    <label for="org_type">Organization type</label>
                    <select id="org_type" name="org_type">
                        <?php foreach (['individual' => 'Individual', 'home' => 'Home', 'industry' => 'Industry'] as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if (($client['organization_type'] ?? '') === $value) echo 'selected'; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-gold">Save changes</button>
            </form>

            <p class="auth-foot"><a href="client_dashboard.php">&larr; Back to dashboard</a></p>
<?php auth_end(); ?>
