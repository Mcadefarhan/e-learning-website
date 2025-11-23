<?php
// Optional: show messages (if your handler redirects back with ?msg=... or ?error=...)
$message = '';
if (isset($_GET['msg'])) {
    // Basic sanitization for display
    $message = htmlspecialchars($_GET['msg']);
} elseif (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up - Learnify</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .signup-wrapper {
      display: flex;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      max-width: 950px;
      width: 100%;
      overflow: hidden;
    }
    .signup-image-container {
      flex: 1;
      background-color: #f7f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .signup-image-container img { max-width: 100%; height: auto; }
    .signup-form-container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }
    .signup-inner-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      padding: 2rem;
      width: 100%;
    }
    .signup-inner-card h2 {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1c1d1f;
      margin-bottom: 25px;
      text-align: center;
    }
    .form-control {
      border: 1px solid #d1d7dc;
      border-radius: 6px;
      padding: 12px;
      margin-bottom: 15px;
    }
    .btn-continue {
      background-color: #5624d0;
      color: #fff;
      padding: 12px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 1rem;
      border: none;
      width: 100%;
      margin-top: 10px;
    }
    .social-login-divider { text-align: center; margin: 25px 0; color: #6a6f73; font-size: .9rem; position: relative; }
    .social-login-divider::before, .social-login-divider::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #d1d7dc; }
    .social-login-divider::before { left: 0; } .social-login-divider::after { right: 0; }
    .social-buttons .btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 10px; border: 1px solid #1c1d1f; border-radius: 6px; margin-bottom: 10px; font-weight: 600; background-color: #fff; }
    .social-buttons .btn i { margin-right: 8px; font-size: 1.2rem; }
    .terms-text { font-size: 0.75rem; color: #6a6f73; margin-top: 15px; text-align: center; }
    .terms-text a { color: #5624d0; text-decoration: none; font-weight: 600; }
    .login-link { text-align: center; margin-top: 20px; }
    .login-link a { color: #5624d0; text-decoration: none; font-weight: 600; }
    .alert-inline { margin-bottom: 15px; }
    @media (max-width: 768px) {
      .signup-wrapper { flex-direction: column; margin: 1rem; }
      .signup-image-container { display: none; }
    }
  </style>
</head>
<body>

  <div class="signup-wrapper">
    <!-- Left Image -->
    <div class="signup-image-container">
      <img src="images/signup.bg.jpg" alt="Online Education Illustration">
    </div>

    <!-- Right Form -->
    <div class="signup-form-container">
      <div class="signup-inner-card">

        <h2>Sign up with email</h2>

        <!-- show message from handler if any -->
        <?php if (!empty($message)): ?>
          <div class="alert alert-info alert-inline" role="alert">
            <?= $message ?>
          </div>
        <?php endif; ?>

        <!-- NOTE: posts to your existing signup handler -->
        <form method="post" action="Backend/signup_process.php" novalidate>
          <input type="text" name="fullname" class="form-control" placeholder="Full name" required>
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <input type="password" name="password" class="form-control" placeholder="Password" required>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="offersCheckbox" name="offers" checked>
            <label class="form-check-label" for="offersCheckbox">
              Send me special offers and learning tips.
            </label>
          </div>

          <button type="submit" class="btn-continue">Continue</button>
        </form>

        <div class="social-login-divider">Or</div>

        <div class="social-buttons">
          <button class="btn" type="button"><i class="fab fa-google"></i> Continue with Google</button>
          <button class="btn" type="button"><i class="fab fa-facebook-f"></i> Continue with Facebook</button>
          <button class="btn" type="button"><i class="fab fa-apple"></i> Continue with Apple</button>
        </div>

        <p class="terms-text">
          By signing up, you agree to our <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.
        </p>

        <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>
      </div>
    </div>
  </div>

</body>
</html>
