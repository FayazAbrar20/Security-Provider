<?php
session_start();

session_unset();
session_destroy();

// Remove auto-login cookie
setcookie("remember_guard", "", time() - 3600, "/");

header("Location: guard_login.php");
exit();
?>
