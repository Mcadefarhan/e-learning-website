<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php?error=" . urlencode("Please login first."));
    exit;
}

$fullname = $_SESSION['fullname'] ?? 'User';
$initials = strtoupper(substr($fullname, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - eduflect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Poppins', sans-serif; }
    .navbar-logged-in .nav-icon { font-size: 1.2rem; color: #1c1d1f; }

    /* Avatar */
    .profile-avatar {
      width: 40px;
      height: 40px;
      background-color: #1c1d1f;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      position: relative;
      cursor: pointer;
      transition: 0.2s ease;
    }
    .profile-avatar:hover { transform: scale(1.05); }

    .notification-dot {
      width: 10px;
      height: 10px;
      background-color: #5624d0;
      border: 2px solid #fff;
      border-radius: 50%;
      position: absolute;
      top: 0;
      right: 0;
    }

    /* Custom Dropdown */
    .profile-wrapper { position: relative; }
    .profile-dropdown {
      position: absolute;
      top: 50px;
      right: 0;
      width: 230px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      overflow: hidden;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.25s ease;
      z-index: 999;
    }
    .profile-dropdown.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .profile-dropdown-header {
      background: #f8f9fa;
      padding: 12px;
      text-align: center;
      border-bottom: 1px solid #eee;
    }
    .profile-dropdown-header strong {
      display: block;
      font-size: 15px;
      color: #1c1d1f;
    }
    .profile-dropdown a {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      color: #1c1d1f;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.2s ease;
    }
    .profile-dropdown a:hover { background: #f3f3f3; }
    .profile-dropdown a i {
      width: 20px;
      color: #5624d0;
      margin-right: 10px;
    }
    .profile-dropdown .logout { color: #dc3545 !important; }
    .profile-dropdown hr {
      margin: 6px 0;
      border: none;
      border-top: 1px solid #eee;
    }

    .welcome-header {
      background-color: #1c1d1f;
      color: white;
      padding: 4rem 0;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top navbar-logged-in">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">eduflect</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">Categories</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Development</a></li>
            <li><a class="dropdown-item" href="#">Design</a></li>
            <li><a class="dropdown-item" href="#">Marketing</a></li>
          </ul>
        </li>
      </ul>

      <form class="d-flex mx-auto w-50">
        <div class="input-group">
          <input type="search" class="form-control" placeholder="Search for anything" aria-label="Search">
          <button class="btn btn-dark" type="button"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <div class="navbar-nav ms-auto align-items-center">
        <a href="#" class="nav-link me-3">My learning</a>
        <a href="#" class="nav-link me-3"><i class="far fa-heart nav-icon"></i></a>
        <a href="#" class="nav-link me-3"><i class="fas fa-shopping-cart nav-icon"></i></a>
        <a href="#" class="nav-link me-3"><i class="far fa-bell nav-icon"></i></a>

        <!-- 🔥 Custom Profile Dropdown -->
        <div class="profile-wrapper">
          <div class="profile-avatar" id="profileToggle">
            <?= htmlspecialchars($initials) ?>
            <span class="notification-dot"></span>
          </div>

          <div class="profile-dropdown" id="profileMenu">
            <div class="profile-dropdown-header">
              <strong><?= htmlspecialchars($fullname) ?></strong>
              <small>Student</small>
            </div>
            <a href="#"><i class="fa-solid fa-user"></i> Edit Profile</a>
            <a href="#"><i class="fa-solid fa-book"></i> My Learning</a>
            <a href="#"><i class="fa-solid fa-heart"></i> Wishlist</a>
            <a href="#"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a>
            <a href="#"><i class="fa-solid fa-language"></i> Language</a>
            <hr>
            <a href="login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<main>
  <header class="welcome-header">
    <div class="container">
      <h1 class="display-4 fw-bold">Welcome back, <?= htmlspecialchars($fullname) ?>!</h1>
      <p class="lead">Ready to jump back in? Let's start learning.</p>
    </div>
  </header>

  <!-- ✅ Your course + footer sections remain unchanged -->
  <section id="courses" class="py-5">
    <div class="container">
      <h2 class="text-center mb-4 fw-bold">Our Most Popular Courses</h2>
      <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100 course-card">
            <img src="images/course-web-dev.jpg" class="card-img-top" alt="Course Image">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title fw-bold">The Complete Web Development Bootcamp</h5>
              <p class="card-text text-muted small">Angela Yu</p>
              <div class="rating mb-2">
                <span class="text-warning">4.7</span>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star-half-alt text-warning"></i>
                <span class="text-muted">(123,456)</span>
              </div>
              <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
          <div class="card h-100 course-card">
            <img src="images/course-python.jpg" class="card-img-top" alt="Course Image">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title fw-bold">Python for Data Science and Machine Learning</h5>
              <p class="card-text text-muted small">Jose Portilla</p>
              <div class="rating mb-2">
                <span class="text-warning">4.6</span>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star text-warning"></i>
                <i class="fas fa-star-half-alt text-warning"></i>
                <span class="text-muted">(98,765)</span>
              </div>
              <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="categories" class="py-5 bg-light"></section>
  <section id="testimonials" class="py-5"></section>
</main>

<footer class="bg-dark text-white pt-5 pb-4">
  <div class="container text-center text-md-start">
    <div class="row">
      <div class="col-md-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">eduflect</h5>
        <p>Learn on your schedule. Anytime, anywhere.</p>
      </div>
      <div class="col-md-2 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Links</h5>
        <p><a href="#" class="text-white text-decoration-none">About Us</a></p>
        <p><a href="#" class="text-white text-decoration-none">Careers</a></p>
        <p><a href="#" class="text-white text-decoration-none">Help and Support</a></p>
      </div>
      <div class="col-md-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Contact</h5>
        <p><i class="fas fa-home me-3"></i> New Delhi, India</p>
        <p><i class="fas fa-envelope me-3"></i> info@eduflect.com</p>
        <p><i class="fas fa-phone me-3"></i> +91 7091987466</p>
      </div>
    </div>
    <hr class="my-4">
    <div class="row align-items-center">
      <div class="col-md-7 col-lg-8">
        <p class="mb-0">© 2025 eduflect, Inc. All rights reserved.</p>
      </div>
      <div class="col-md-5 col-lg-4">
        <div class="text-center text-md-end">
          <a href="#" class="text-white me-3 fs-5"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="text-white me-3 fs-5"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-white fs-5"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggle = document.getElementById('profileToggle');
  const menu = document.getElementById('profileMenu');
  toggle.addEventListener('click', () => menu.classList.toggle('active'));
  window.addEventListener('click', (e) => {
    if (!toggle.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('active');
  });
</script>
</body>
</html>
