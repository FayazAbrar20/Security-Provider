<?php
/*
 * Shared security helpers. Loaded by db.php, so every page gets them.
 *
 *  - Secure session cookie (HttpOnly, SameSite=Lax) and session start.
 *  - CSRF protection: every POST must carry the session's token.
 *  - e(): HTML-escape anything printed into a page (prevents XSS).
 *  - db_query(): prepared statements, so input never becomes SQL.
 *  - Password rules and "remember me" tokens that cannot be forged.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/* ---------- Output escaping ---------- */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Reject any POST that does not carry this session's token.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session expired or the form was invalid. Please go back, refresh the page and try again.');
    }
}

/* ---------- Database ---------- */

/**
 * Runs a prepared statement. All parameters are bound as strings; MySQL converts
 * them to the column type. Returns a mysqli_result for SELECT, true/false otherwise.
 */
function db_query(mysqli $conn, string $sql, array $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        error_log('SQL prepare failed: ' . mysqli_error($conn));
        return false;
    }
    if ($params) {
        $types = str_repeat('s', count($params));
        $values = array_map(static function ($v) { return $v === null ? null : (string)$v; }, $params);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log('SQL execute failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        // INSERT / UPDATE / DELETE
        mysqli_stmt_close($stmt);
        return true;
    }
    return $result;
}

function db_one(mysqli $conn, string $sql, array $params = []): ?array
{
    $result = db_query($conn, $sql, $params);
    if (!$result || $result === true) {
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

/* ---------- Passwords ---------- */

// Returns an error message, or "" when the password is acceptable.
function password_problem(string $password): string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return '';
}

/* ---------- Account recovery ---------- */

// A random key like "K7QM-2XRA-9DHT-WC4P" (no 0/O/1/I to avoid misreading).
function new_recovery_key(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $key = '';
    for ($i = 0; $i < 16; $i++) {
        $key .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        if ($i % 4 === 3 && $i < 15) {
            $key .= '-';
        }
    }
    return $key;
}

function normalize_recovery_key(string $key): string
{
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $key));
    return implode('-', str_split($clean, 4));
}

const RESET_WINDOW_MINUTES = 15;
const RESET_MAX_PER_ACCOUNT = 5;
const RESET_MAX_PER_IP = 20;

// True when this email or this visitor has failed identity checks too often recently.
function reset_blocked(mysqli $conn, string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - RESET_WINDOW_MINUTES * 60);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $row = db_one($conn,
        "SELECT SUM(email = ?) AS by_email, SUM(ip = ?) AS by_ip
         FROM password_reset_attempts WHERE attempted_at >= ?",
        [strtolower($email), $ip, $since]);
    return (int)($row['by_email'] ?? 0) >= RESET_MAX_PER_ACCOUNT || (int)($row['by_ip'] ?? 0) >= RESET_MAX_PER_IP;
}

function reset_record_failure(mysqli $conn, string $email): void
{
    db_query($conn, "INSERT INTO password_reset_attempts (email, ip, attempted_at) VALUES (?, ?, NOW())",
        [strtolower($email), $_SERVER['REMOTE_ADDR'] ?? '']);
}

function reset_clear_failures(mysqli $conn, string $email): void
{
    db_query($conn, "DELETE FROM password_reset_attempts WHERE email = ?", [strtolower($email)]);
}

/* ---------- Login and "remember me" ---------- */

function login_user(array $user): void
{
    session_regenerate_id(true); // new session id after login (prevents session fixation)
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
}

/*
 * The cookie holds "selector:validator", both random. The database keeps the
 * selector and only a SHA-256 hash of the validator, so a stolen database cannot
 * be turned into cookies, and a user id typed into a cookie logs nobody in.
 */
function remember_issue(mysqli $conn, int $user_id, string $cookie): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires   = time() + 86400 * 7;

    db_query($conn,
        "INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)",
        [$user_id, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires)]);

    setcookie($cookie, $selector . ':' . $validator, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

// Logs the user in from a valid remember-me cookie for the given role. Returns true on success.
function remember_login(mysqli $conn, string $cookie, string $role): bool
{
    $raw = $_COOKIE[$cookie] ?? '';
    if (!is_string($raw) || strpos($raw, ':') === false) {
        return false;
    }
    [$selector, $validator] = explode(':', $raw, 2);

    $row = db_one($conn,
        "SELECT t.id, t.validator_hash, t.expires_at, u.user_id, u.email, u.role
         FROM remember_tokens t JOIN users u ON u.user_id = t.user_id
         WHERE t.selector = ? LIMIT 1",
        [$selector]);

    if (!$row || strtotime($row['expires_at']) < time()
        || !hash_equals($row['validator_hash'], hash('sha256', $validator))
        || $row['role'] !== $role) {
        remember_forget($conn, $cookie);
        return false;
    }

    // One-time use: replace the token on every automatic login.
    db_query($conn, "DELETE FROM remember_tokens WHERE id = ?", [$row['id']]);
    login_user($row);
    remember_issue($conn, (int)$row['user_id'], $cookie);
    return true;
}

function remember_forget(mysqli $conn, string $cookie): void
{
    $raw = $_COOKIE[$cookie] ?? '';
    if (is_string($raw) && strpos($raw, ':') !== false) {
        [$selector] = explode(':', $raw, 2);
        db_query($conn, "DELETE FROM remember_tokens WHERE selector = ?", [$selector]);
    }
    setcookie($cookie, '', ['expires' => time() - 3600, 'path' => '/']);
}

function logout_user(mysqli $conn, string $cookie): void
{
    remember_forget($conn, $cookie);
    $_SESSION = [];
    session_destroy();
}
