<?php
include "../db.php";

// Ends the admin session (the old "Logout" link only went to the home page
// and left the admin logged in).
$_SESSION = [];
session_destroy();

header("Location: admin_login.php");
exit();
