<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Learnify</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
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
    .login-wrapper {
      display: flex;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      max-width: 950px;
      width: 100%;
      overflow: hidden;
    }
    .login-image-container {
      flex: 1;
      background-color: #f7f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .login-image-container img { max-width: 100%; height: auto; }
    .login-form-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 2rem; }
    .login-inner-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 2rem; width: 100%; }
    .login-inner-card h2 { font-size: 1.8rem; font-weight: 700; color: #1c1d1f; margin-bottom: 25px; text-align: center; }
    .form-control { border: 1px solid #d1d7dc; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
    .btn-login { background-color: #5624d0; color: #fff; padding: 12px; border-radius: 6px; font-weight: 600; font-size: 1rem; border: none; width: 100%; margin-top: 10px; }
    .forgot-link { text-align: right; margin-top: -10px; margin-bottom: 15px; }
    .forgot-link a { font-size: 0.85rem; color: #5624d0; text-decoration: none; font-weight: 500; }
    .social-login-divider { text-align: center; margin: 25px 0; color: #6a6f73; font-size: 0.9rem; position: relative; }
    .social-login-divider::before, .social-login-divider::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: #d1d7dc; }
    .social-login-divider::before { left: 0; } .social-login-divider::after { right: 0; }
    .social-buttons .btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 10px; border: 1px solid #1c1d1f; border-radius: 6px; margin-bottom: 10px; font-weight: 600; background-color: #fff; }
    .social-buttons .btn i { margin-right: 8px; font-size: 1.2rem; }
    .login-link { text-align: center; margin-top: 20px; }
    .login-link a { color: #5624d0; text-decoration: none; font-weight: 600; }
    @media (max-width: 768px) {
      .login-wrapper { flex-direction: column; margin: 1rem; }
      .login-image-container { display: none; }
    }
  </style>
</head>
<body>

  <div class="login-wrapper">
    <div class="login-image-container">
      <img src="images/signup.bg.jpg" alt="Online Education Illustration">
    </div>

    <div class="login-form-container">
      <div class="login-inner-card">
        <h2>Log in to your account</h2>
        <?php
$error = $_GET['error'] ?? '';
?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

         <form action="Backend/login_process.php" method="POST">


          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <input type="password" name="password" class="form-control" placeholder="Password" required>

          <div class="forgot-link">
            <a href="#">Forgot Password?</a>
          </div>

          <button type="submit" class="btn-login">Log In</button>
        </form>

        <div class="social-login-divider">Or</div>
        <div class="social-buttons">
          <button class="btn" type="button"><i class="fab fa-google"></i> Continue with Google</button>
          <button class="btn" type="button"><i class="fab fa-facebook-f"></i> Continue with Facebook</button>
          <button class="btn" type="button"><i class="fab fa-apple"></i> Continue with Apple</button>
        </div>

        <p class="login-link">Don’t have an account? <a href="signup.php">Sign up</a></p>
      </div>
    </div>
  </div>

</body>
</html>
