<?php
// Database connection. Defaults match a local XAMPP install; override with environment
// variables (DB_HOST, DB_USER, DB_PASS, DB_NAME) instead of editing this file.
mysqli_report(MYSQLI_REPORT_OFF); // errors are checked and logged, never shown to visitors

$conn = mysqli_connect(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'security_guard_system'
);

if (!$conn) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('Database connection failed. Please try again later.');
}

mysqli_set_charset($conn, 'utf8mb4');

require_once __DIR__ . '/includes/security.php';
