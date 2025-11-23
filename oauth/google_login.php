<?php
// oauth/google_login.php
session_start();
$client_id = 'YOUR_GOOGLE_CLIENT_ID';
$redirect = 'http://localhost/e-learning-website/oauth/google_callback.php';
$state = bin2hex(random_bytes(8));
$_SESSION['oauth_state'] = $state;
$scope = urlencode('openid email profile');

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code"
         . "&client_id=" . urlencode($client_id)
         . "&redirect_uri=" . urlencode($redirect)
         . "&scope={$scope}&state={$state}&access_type=offline&prompt=select_account";

header("Location: $authUrl");
exit;
