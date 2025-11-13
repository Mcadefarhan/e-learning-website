<?php
// Backend/login_process.php
session_start();
require_once __DIR__ . "/db_connect.php"; // Ensure this path is correct and $conn (mysqli) is available

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

// Read inputs
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$requested_role = strtolower(trim($_POST['role'] ?? 'student')); // default to student if not provided
$redirect = trim($_POST['redirect'] ?? '');

// Basic validation
if ($email === '' || $password === '') {
    header("Location: ../login.php?error=" . urlencode("Please fill in all fields."));
    exit;
}

// Prepare and fetch user by email
$sql = "SELECT id, fullname, email, password, role FROM users WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("DB prepare error: " . $conn->error);
    header("Location: ../login.php?error=" . urlencode("Server error. Try again later."));
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: ../login.php?error=" . urlencode("No account found with that email."));
    exit;
}

// Verify password (hashed)
if (!password_verify($password, $user['password'])) {
    header("Location: ../login.php?error=" . urlencode("Incorrect email or password."));
    exit;
}

// Normalize DB role
$dbRole = strtolower(trim($user['role'] ?? 'student'));

// Strict role check: selected role must equal stored role
if ($requested_role !== $dbRole) {
    // Friendly message (without leaking sensitive info)
    if ($requested_role === 'admin') {
        $msg = "This account is not an admin. You cannot login as Admin.";
    } elseif ($requested_role === 'teacher') {
        $msg = "This account is not a teacher. You cannot login as Teacher.";
    } else {
        $msg = "This account is not a student. You cannot login as Student.";
    }
    header("Location: ../login.php?error=" . urlencode($msg));
    exit;
}

// Passed authentication and role check -> create session
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $dbRole;

// set secure cookie params (optional: adjust for HTTPS in production)
$cookieParams = session_get_cookie_params();
setcookie(session_name(), session_id(), [
    'expires' => 0,
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Helper: is redirect internal and safe?
function is_safe_internal_redirect($url) {
    if (empty($url)) return false;
    // disallow absolute URLs with host / protocol
    $parts = parse_url($url);
    if ($parts === false) return false;
    if (isset($parts['scheme']) || isset($parts['host'])) return false;
    // prevent "//domain.com/..." style
    if (strpos($url, '//') === 0) return false;
    // disallow suspicious characters (basic)
    if (preg_match('/[\r\n]/', $url)) return false;
    return true;
}

// If redirect present and safe → go there
if (!empty($redirect) && is_safe_internal_redirect($redirect)) {
    // ensure redirect doesn't start with a leading slash that breaks root (allow both)
    $target = $redirect;
    header("Location: " . $target);
    exit;
}

// Redirect based on role (fallback)
if ($dbRole === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
} elseif ($dbRole === 'teacher') {
    header("Location: ../teacher/dashboard.php");
    exit;
} else {
    header("Location: ../dashboard.php");
    exit;
}
?>
