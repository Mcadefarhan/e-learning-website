<?php
// oauth/facebook_login.php
session_start();
$app_id = 'YOUR_FB_APP_ID';
$redirect = 'http://localhost/e-learning-website/oauth/facebook_callback.php';
$state = bin2hex(random_bytes(8));
$_SESSION['oauth_state'] = $state;

$auth = "https://www.facebook.com/v17.0/dialog/oauth?"
      . "client_id=" . urlencode($app_id)
      . "&redirect_uri=" . urlencode($redirect)
      . "&state={$state}&scope=email,public_profile";

header("Location: $auth");
exit;
