<?php
include "../db.php";

// Ends the session and deletes this device's remember-me token.
logout_user($conn, "remember_client");

header("Location: client_login.php");
exit();
