<?php
// oauth/facebook_callback.php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid request.');
}
unset($_SESSION['oauth_state']);

$code = $_GET['code'];
$app_id = 'YOUR_FB_APP_ID';
$app_secret = 'YOUR_FB_APP_SECRET';
$redirect = 'http://localhost/e-learning-website/oauth/facebook_callback.php';

// get access token
$token_url = "https://graph.facebook.com/v17.0/oauth/access_token?"
  . "client_id=" . urlencode($app_id)
  . "&redirect_uri=" . urlencode($redirect)
  . "&client_secret=" . urlencode($app_secret)
  . "&code=" . urlencode($code);

$token_json = file_get_contents($token_url);
$token = json_decode($token_json, true);
if (empty($token['access_token'])) die('Failed to obtain access token.');

// fetch profile
$me_url = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . urlencode($token['access_token']);
$me_json = file_get_contents($me_url);
$me = json_decode($me_json, true);

$email = $me['email'] ?? null;
$name  = $me['name'] ?? 'Facebook User';
$fb_id = $me['id'] ?? null;

if (!$email) {
    // FB may not return email if permission not given; handle gracefully
    die('Facebook did not return email. Please allow email permission or sign up with email.');
}

// find or create
$stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    $role = 'student';
    $random_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, facebook_id, reg_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $name, $email, $random_password, $role, $fb_id);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
} else {
    $user_id = $user['id'];
    // optional: update facebook_id
    if (empty($user['facebook_id'] ?? null)) {
        $u = $conn->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
        $u->bind_param("si", $fb_id, $user_id);
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

header("Location: ../dashboard.php");
exit;
