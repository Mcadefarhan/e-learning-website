<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

$category = $_GET['category'] ?? null;

if ($category) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE category = ? ORDER BY id DESC");
    $stmt->bind_param("s", $category);
} else {
    $stmt = $conn->prepare("SELECT * FROM courses ORDER BY id DESC");
}
$stmt->execute();
$res = $stmt->get_result();
$courses = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $category ? htmlspecialchars($category) . " Courses" : "All Courses" ?> - eduflect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<style>
body {
  background: #f8f9fa;
  font-family: 'Poppins', sans-serif;
}
.card {
  border: none;
  border-radius: 12px;
  transition: transform .2s ease, box-shadow .2s ease;
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}
.card-img-top {
  border-radius: 12px 12px 0 0;
  height: 180px;
  object-fit: cover;
}
.price-tag {
  font-weight: 600;
  color: #5624d0;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    
    <a class="navbar-brand fw-bold" href="index.php">
      <i class="fa-solid fa-house me-1"></i> eduflect
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">

      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Courses</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="courses.php">All Courses</a></li>
            <hr class="dropdown-divider">
            <li><a class="dropdown-item" href="courses.php?category=Development">Development</a></li>
            <li><a class="dropdown-item" href="courses.php?category=Design">Design</a></li>
            <li><a class="dropdown-item" href="courses.php?category=Marketing">Marketing</a></li>
            <li><a class="dropdown-item" href="courses.php?category=IT%20%26%20Software">IT & Software</a></li>
          </ul>
        </li>
      </ul>

      <form class="d-flex mx-auto w-50" action="courses.php" method="get">
        <div class="input-group">
          <input type="search" name="search" class="form-control" placeholder="Search for anything" aria-label="Search">
          <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <div class="navbar-nav ms-auto align-items-center"></div>

    </div>
  </div>
</nav>

<!-- PAGE CONTENT -->
<div class="container py-5" style="margin-top: 80px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><?= $category ? htmlspecialchars($category) : "All" ?> Courses</h2>
    <a href="index.php" class="btn btn-outline-dark btn-sm">← Back</a>
  </div>

  <div class="row g-4">
    <?php if (empty($courses)): ?>
      <div class="col-12 text-center text-muted py-5">No courses found in this category.</div>
    <?php else: foreach ($courses as $c): ?>
      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card h-100 shadow-sm">
          
          <?php if (!empty($c['image'])): ?>
            <img src="uploads/courses/<?= htmlspecialchars($c['image']) ?>" class="card-img-top">
          <?php else: ?>
            <img src="https://via.placeholder.com/400x250?text=No+Image" class="card-img-top">
          <?php endif; ?>

          <div class="card-body d-flex flex-column">
            <h5 class="fw-semibold mb-1"><?= htmlspecialchars($c['title']) ?></h5>
            <p class="text-muted small mb-1"><?= htmlspecialchars($c['instructor']) ?></p>
            <p class="text-muted small flex-grow-1"><?= htmlspecialchars(substr($c['description'], 0, 70)) ?>...</p>

            <div class="d-flex justify-content-between mt-auto">
              <span class="price-tag">₹<?= number_format($c['price'], 2) ?></span>
              <a href="view_course.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
            </div>
          </div>

        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- FOOTER -->
<footer class="bg-dark text-white pt-5 pb-4 mt-5">
  <div class="container text-center text-md-start">
    <div class="row">
      <div class="col-md-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">eduflect</h5>
        <p>Learn on your schedule. Anytime, anywhere.</p>
      </div>

      <div class="col-md-2 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Links</h5>
        <p><a href="#" class="text-white">About Us</a></p>
        <p><a href="#" class="text-white">Careers</a></p>
        <p><a href="#" class="text-white">Help and Support</a></p>
      </div>

      <div class="col-md-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 fw-bold">Contact</h5>
        <p><i class="fas fa-home me-3"></i> New Delhi, India</p>
        <p><i class="fas fa-envelope me-3"></i> info@eduflect.com</p>
        <p><i class="fas fa-phone me-3"></i> +91 7091987466</p>
      </div>
    </div>

    <hr class="mb-4">
    <div class="text-center">
      <p>© 2025 eduflect, Inc. All rights reserved.</p>
      <div class="social-links mt-2">
        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
        <a href="#" class="text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
