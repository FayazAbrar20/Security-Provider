<?php
session_start();

session_unset();
session_destroy();

// Remove auto-login cookie
setcookie("remember_client", "", time() - 3600, "/");

header("Location: client_login.php");
exit();
?>
