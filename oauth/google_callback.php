<?php
// oauth/google_callback.php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php'; // adjust as needed

if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid or missing state/code.');
}
unset($_SESSION['oauth_state']);

$code = $_GET['code'];
$client_id = 'YOUR_GOOGLE_CLIENT_ID';
$client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirect = 'http://localhost/e-learning-website/oauth/google_callback.php';

// exchange code for token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
  'code' => $code,
  'client_id' => $client_id,
  'client_secret' => $client_secret,
  'redirect_uri' => $redirect,
  'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$tokenResp = curl_exec($ch);
curl_close($ch);
$token = json_decode($tokenResp, true);

if (empty($token['access_token'])) die('Failed to get access token.');

// fetch userinfo
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userinfoJson = curl_exec($ch);
curl_close($ch);
$userinfo = json_decode($userinfoJson, true);

$email = $userinfo['email'] ?? null;
$name  = $userinfo['name'] ?? 'Google User';
$google_id = $userinfo['id'] ?? null;
$picture = $userinfo['picture'] ?? null;

if (!$email) die('Google did not return email.');

// --- find or create user ---
$stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    $role = 'student';
    $random_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $fullname = $name;
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, google_id, reg_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $fullname, $email, $random_password, $role, $google_id);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
} else {
    // if existing user, optionally update google_id if empty
    $user_id = $user['id'];
    if (empty($user['google_id'] ?? null)) {
        $u = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $u->bind_param("si", $google_id, $user_id);
        $u->execute();
        $u->close();
    }
}

// login
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user_id;
$_SESSION['fullname'] = $name;
$_SESSION['email'] = $email;
$_SESSION['role'] = $user['role'] ?? 'student';

// redirect where you want (dashboard or back to page)
header("Location: ../dashboard.php");
exit;
