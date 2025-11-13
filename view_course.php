<?php
// view_course.php (fixed)
session_start();
require_once __DIR__ . '/Backend/db_connect.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "<h2 style='padding:40px;text-align:center;color:#c00;'>Invalid course id.</h2>";
    exit;
}

// fetch course
$stmt = $conn->prepare("SELECT id, title, instructor, description, price, image, video_url, category, DATE_FORMAT(created_at,'%Y-%m-%d') AS created_at FROM courses WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    http_response_code(404);
    echo "<h2 style='padding:40px;text-align:center;color:#c00;'>Course not found.</h2>";
    exit;
}

// youtube embed helper (more robust)
function youtube_embed_url($url) {
    $url = trim($url);
    if ($url === '') return null;

    // remove URL params after &
    $u = $url;
    // common patterns:
    // https://youtu.be/ID
    if (preg_match('#youtu\.be/([A-Za-z0-9_\-]+)#i', $u, $m)) return 'https://www.youtube.com/embed/' . $m[1];
    // watch?v=ID or &v=ID
    if (preg_match('#[?&]v=([A-Za-z0-9_\-]+)#i', $u, $m)) return 'https://www.youtube.com/embed/' . $m[1];
    // embed/ID
    if (preg_match('#/embed/([A-Za-z0-9_\-]+)#i', $u, $m)) return 'https://www.youtube.com/embed/' . $m[1];
    // fallback: try last path segment
    $parts = parse_url($u);
    if (!empty($parts['path'])) {
        $seg = trim($parts['path'], '/');
        $segParts = explode('/', $seg);
        $maybe = end($segParts);
        if (preg_match('/^[A-Za-z0-9_\-]+$/', $maybe)) {
            return 'https://www.youtube.com/embed/' . $maybe;
        }
    }
    return null;
}
$embed = youtube_embed_url($course['video_url']);

function e($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// flash messages (pull and clear)
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// fetch 3 other courses for "related" (same category)
$related = [];
$stmt2 = $conn->prepare("SELECT id, title, image, price FROM courses WHERE category = ? AND id != ? ORDER BY id DESC LIMIT 3");
if ($stmt2) {
    $cat = $course['category'] ?? '';
    $stmt2->bind_param("si", $cat, $id);
    $stmt2->execute();
    $related = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();
}

// uploads path (for checking existence)
$uploadsDir = __DIR__ . '/uploads/courses/'; // server path
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= e($course['title']) ?> — eduflect</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background:#f8f9fa; color:#222; }
    .hero-card { border-radius:14px; overflow:hidden; box-shadow:0 8px 30px rgba(17,24,39,0.06); margin-top:88px; }
    .course-media { border-radius:10px; overflow:hidden; background:#000; }
    .placeholder { width:100%; height:360px; background:linear-gradient(90deg,#f0f0f0,#e9e9e9); display:block; }
    .price-badge { color:#5624d0; font-weight:700; font-size:1.4rem; }
    .meta-small { color:#6b6f73; font-size:.95rem; }
    .related-card img { height:90px; object-fit:cover; border-radius:8px; }
    footer .fw-bold { letter-spacing:.6px; }
    .flash { position:fixed; top:88px; right:20px; z-index:1200; min-width:260px; }
    @media (max-width:991px){ .hero-card{ margin-top:120px } .course-media iframe, .placeholder{ height:220px; } }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">eduflect</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Courses</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="courses.php">All Courses</a></li>
            <li><a class="dropdown-item" href="courses.php?category=Development">Development</a></li>
            <li><a class="dropdown-item" href="courses.php?category=Design">Design</a></li>
            <li><a class="dropdown-item" href="courses.php?category=Marketing">Marketing</a></li>
          </ul>
        </li>
      </ul>

      <form class="d-flex mx-auto w-50" action="courses.php" method="get">
        <div class="input-group">
          <input name="search" class="form-control" placeholder="Search courses">
          <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <div class="d-flex ms-auto align-items-center">
    
      </div>
    </div>
  </div>
</nav>

<!-- FLASH -->
<?php if ($flash_success): ?>
  <div class="alert alert-success flash"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger flash"><?= e($flash_error) ?></div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<div class="container py-5">
  <a href="javascript:history.back()" class="btn btn-outline-secondary mb-3">← Back</a>

  <div class="card hero-card mb-4">
    <div class="row g-0">
      <div class="col-lg-8 p-4">
        <!-- MEDIA -->
        <?php if ($embed): ?>
          <div class="course-media mb-3 ratio ratio-16x9">
            <iframe src="<?= e($embed) ?>?rel=0&modestbranding=1" title="<?= e($course['title']) ?> video"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
          </div>
        <?php elseif (!empty($course['image']) && file_exists($uploadsDir . $course['image'])): ?>
          <img src="uploads/courses/<?= e($course['image']) ?>" alt="<?= e($course['title']) ?>" class="img-fluid rounded mb-3">
        <?php else: ?>
          <div class="placeholder rounded mb-3" role="img" aria-label="No image"></div>
        <?php endif; ?>

        <h1 class="fw-bold"><?= e($course['title']) ?></h1>
        <p class="meta-small mb-1">By <strong><?= e($course['instructor']) ?></strong> · <?= e($course['category'] ?? '—') ?> · Joined <?= e($course['created_at']) ?></p>
        <hr>
        <h5 class="fw-semibold">About this course</h5>
        <p style="white-space:pre-line;"><?= e($course['description']) ?></p>
      </div>

      <div class="col-lg-4 p-4 d-flex flex-column">
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="price-badge">₹<?= number_format((float)$course['price'], 2) ?></div>
              <div class="meta-small">One-time payment</div>
            </div>
            <div class="text-end">
              <div class="mb-2"><small class="meta-small">Seats: <strong>Unlimited</strong></small></div>
              <div class="mb-2"><small class="meta-small">Language: <strong>English</strong></small></div>
            </div>
          </div>
        </div>

        <!-- CTA: if logged in -> enroll.php?course_id=..., else go to login with redirect back -->
        <!-- CTA: Enroll Button -->
<div class="mt-2">
  <?php if (!isset($_SESSION['user_id'])): ?>
    <?php $redirect = urlencode('view_course.php?id=' . (int)$course['id']); ?>
    <a href="login.php?redirect=<?= $redirect ?>" class="btn btn-primary w-100 mb-2">
      <i class="fa fa-sign-in-alt me-2"></i> Log in to enroll
    </a>
  <?php else: ?>
    <a href="enroll.php?course_id=<?= (int)$course['id'] ?>" class="btn btn-success w-100 mb-2">
      <i class="fa fa-graduation-cap me-2"></i> Enroll Now
    </a>
  <?php endif; ?>

  <a href="courses.php?category=<?= urlencode($course['category'] ?? '') ?>" class="btn btn-outline-secondary w-100">
    <i class="fa fa-list me-2"></i> More in <?= htmlspecialchars($course['category'] ?? 'this category') ?>
  </a>
</div>


        <!-- Instructor / support -->
        <div>
          <h6 class="mb-2">Instructor</h6>
          <div class="d-flex align-items-center">
            <div style="width:48px;height:48px;background:#dde2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;margin-right:10px;">
              <?= strtoupper(substr($course['instructor'],0,1)) ?>
            </div>
            <div>
              <div class="fw-semibold"><?= e($course['instructor']) ?></div>
              <div class="meta-small">Top instructor</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="mb-2">Support</h6>
          <p class="meta-small">Need help? <a href="mailto:support@eduflect.com">Contact support</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- RELATED COURSES -->
  <?php if (!empty($related)): ?>
  <h5 class="mb-3">Related courses</h5>
  <div class="row g-3 mb-5">
    <?php foreach ($related as $r): ?>
      <div class="col-md-4">
        <div class="card related-card p-2 shadow-sm">
          <div class="d-flex gap-3 align-items-center">
            <?php if (!empty($r['image']) && file_exists($uploadsDir . $r['image'])): ?>
              <img src="uploads/courses/<?= e($r['image']) ?>" alt="<?= e($r['title']) ?>">
            <?php else: ?>
              <div style="width:90px;height:60px;background:#f0f0f0;border-radius:8px;"></div>
            <?php endif; ?>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= e($r['title']) ?></div>
              <div class="meta-small">₹<?= number_format($r['price'],2) ?></div>
            </div>
            <a href="view_course.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- FOOTER -->
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
