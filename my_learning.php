<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

// show errors while debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];

// detect image column
$useCol = null;
$checkImage = $conn->query("SHOW COLUMNS FROM courses LIKE 'image'");
$checkThumb = $conn->query("SHOW COLUMNS FROM courses LIKE 'thumbnail'");

if ($checkImage && $checkImage->num_rows > 0) {
    $useCol = 'image';
} elseif ($checkThumb && $checkThumb->num_rows > 0) {
    $useCol = 'thumbnail';
} else {
    $useCol = null;
}

if ($useCol) {
    $sql = "
      SELECT c.id, c.title, c.{$useCol} AS imagefile, c.description
      FROM courses c
      INNER JOIN enrollments e ON e.course_id = c.id
      WHERE e.user_id = ?
      ORDER BY e.enrolled_at DESC
    ";
} else {
    $sql = "
      SELECT c.id, c.title, '' AS imagefile, c.description
      FROM courses c
      INNER JOIN enrollments e ON e.course_id = c.id
      WHERE e.user_id = ?
      ORDER BY e.enrolled_at DESC
    ";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();

// Project-level fallback (inside webroot)
$projectFallback = 'images/course-Digital.jpg';

// compute base path so links work if project is in a subfolder
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

// helper
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// user display name/initial (used for avatar)
$fullname = $_SESSION['fullname'] ?? 'User';
$initials = strtoupper(substr($fullname, 0, 1));
if ($initials === '') $initials = 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Learning — eduflect</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
      :root { --bg:#f6f7fb; --nav-bg:#1c1d1f; --muted:#6b7280; --accent:#5624d0; --card-shadow: 0 8px 20px rgba(0,0,0,0.08); }
      body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial; background:var(--bg); margin:0; color:#111; }

      /* NAVBAR — dashboard theme */
      .site-navbar {
        background: var(--nav-bg);
        color: #fff;
        border-bottom: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.12);
      }
      .site-navbar .nav-link, .site-navbar .navbar-brand {
        color: #fff;
      }
      .site-navbar .nav-link:hover { color: #fff; opacity: 0.9; }
      .discover-menu { min-width:200px; }
      .nav-centered { flex: 1; display:flex; justify-content:center; align-items:center; }

      /* small search (dashboard style) */
      .search-input { width:480px; max-width:60vw; border-radius:28px; padding-left:14px; }
      .search-wrap { display:flex; align-items:center; gap:8px; }

      /* notification + dashboard */
      .notify-btn { color:#fff; background:transparent; border:0; font-size:18px; }
      .dashboard-btn {
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255,255,255,0.12);
        padding:6px 12px;
        border-radius:8px;
      }
      .dashboard-btn:hover { background: rgba(255,255,255,0.03); }

      /* avatar */
      .profile-avatar {
          width: 38px;
          height: 38px;
          background-color: #fff;
          color: var(--nav-bg);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 700;
          cursor: pointer;
          font-size: 14px;
      }
      .profile-wrapper { position: relative; }
      .profile-dropdown {
          position: absolute;
          top: 52px;
          right: 0;
          width: 220px;
          background: #fff;
          color:#111;
          border-radius: 10px;
          box-shadow: 0 10px 30px rgba(0,0,0,0.15);
          padding:6px 0;
          display:none;
      }
      .profile-dropdown.active { display:block; }

      /* main layout */
      .navbar-spacer { height:64px; } /* spacing for fixed navbar */
      .container-main { max-width:1100px; margin: 0 auto; padding: 18px 16px; }
      .card-course { border-radius:12px; overflow:hidden; cursor:pointer; transition: transform .12s ease, box-shadow .12s ease; background:#fff; }
      .card-course:hover { transform: translateY(-4px); box-shadow: var(--card-shadow); }
      .card-course img { height:180px; object-fit:cover; width:100%; display:block; background:#eee; }
      .no-courses { min-height:140px; display:flex; align-items:center; justify-content:center; }

      /* small responsive tweaks */
      @media (max-width:767px) {
        .search-input { width:220px; }
        .nav-centered { display:none; } /* hide center search on tiny screens to keep navbar compact */
      }
    </style>
</head>
<body>

<!-- NAVBAR (dashboard-style, no logo) -->
<nav class="navbar site-navbar navbar-expand-lg fixed-top">
  <div class="container-fluid" style="max-width:1200px;">
    <!-- left: Discover -->
    <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#minNav">
      <span class="navbar-toggler-icon" style="filter: invert(1)"></span>
    </button>

    <div class="collapse navbar-collapse" id="minNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="discoverMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Discover
          </a>
          <ul class="dropdown-menu discover-menu" aria-labelledby="discoverMenu">
            <li><a class="dropdown-item" href="<?= ($basePath ?: '') ?>/courses.php?category=Development">Development</a></li>
            <li><a class="dropdown-item" href="<?= ($basePath ?: '') ?>/courses.php?category=Design">Design</a></li>
            <li><a class="dropdown-item" href="<?= ($basePath ?: '') ?>/courses.php?category=Marketing">Marketing</a></li>
            <li><a class="dropdown-item" href="<?= ($basePath ?: '') ?>/courses.php">All Courses</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= ($basePath ?: '') ?>/courses.php?category=IT%20%26%20Software">IT &amp; Software</a></li>
          </ul>
        </li>
      </ul>

      <!-- center: optional search (dashboard style) -->
      <div class="nav-centered">
        <form class="search-wrap" action="<?= ($basePath ?: '') ?>/courses.php" method="get">
          <input type="search" name="search" class="form-control search-input" placeholder="Search for anything">
          <button class="btn btn-sm" type="submit" style="border-radius:20px; background:var(--accent); color:#fff; border:0;"><i class="fas fa-search"></i></button>
        </form>
      </div>

      <!-- right: notification + dashboard -->
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
          <button class="notify-btn" title="Notifications" onclick="location.href='<?= ($basePath ?: '') ?>/notifications.php'">
            <i class="fa-regular fa-bell"></i>
          </button>
        </li>

        <li class="nav-item me-3">
          <button class="dashboard-btn" onclick="location.href='<?= ($basePath ?: '') ?>/dashboard.php'">Go to Dashboard</button>
        </li>

        <li class="nav-item profile-wrapper">
          <div class="profile-avatar" id="profileToggle"><?= h($initials) ?></div>
          <div class="profile-dropdown" id="profileMenu">
            <div style="padding:12px; border-bottom:1px solid #eee;">
              <strong><?= h($fullname) ?></strong><br><small style="color:var(--muted)">Student</small>
            </div>
            <a href="<?= ($basePath ?: '') ?>/my_learning.php" style="display:block; padding:8px 12px; color:#111; text-decoration:none;">My Learning</a>
            <a href="<?= ($basePath ?: '') ?>/view_course.php" style="display:block; padding:8px 12px; color:#111; text-decoration:none;">My Courses</a>
            <a href="<?= ($basePath ?: '') ?>/profile.php" style="display:block; padding:8px 12px; color:#111; text-decoration:none;">Profile</a>
            <hr style="margin:8px 0;">
            <a href="<?= ($basePath ?: '') ?>/logout.php" style="display:block; padding:8px 12px; color:#c82333; text-decoration:none;">Log out</a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- spacer -->
<div class="navbar-spacer"></div>

<!-- PAGE CONTENT -->
<div class="container-main">
  <h2 class="mb-4">My Learning</h2>

  <div class="row">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($course = $result->fetch_assoc()): ?>
        <?php
            // DB value
            $imgRaw = $course['imagefile'] ?? '';
            $imgVal = trim((string)$imgRaw);

            // default to project fallback
            $thumbPath = $projectFallback;

            if ($imgVal !== '') {
                // candidate paths
                $uploadsCandidate = __DIR__ . '/' . $imgVal; // raw value could be relative path
                $uploadsWebPath = $imgVal;

                $uploadsByName = __DIR__ . '/uploads/courses/' . basename($imgVal);
                $uploadsByNameWeb = 'uploads/courses/' . basename($imgVal);

                $imagesByName = __DIR__ . '/images/' . basename($imgVal);
                $imagesByNameWeb = 'images/' . basename($imgVal);

                if (file_exists($uploadsCandidate) && is_file($uploadsCandidate)) {
                    $thumbPath = $uploadsWebPath;
                } elseif (file_exists($uploadsByName) && is_file($uploadsByName)) {
                    $thumbPath = $uploadsByNameWeb;
                } elseif (file_exists($imagesByName) && is_file($imagesByName)) {
                    $thumbPath = $imagesByNameWeb;
                } else {
                    $thumbPath = $projectFallback;
                }
            } else {
                $thumbPath = $projectFallback;
            }

            // final check: if file not found inside webroot, use project fallback (or a placeholder if you prefer)
            $checkFull = __DIR__ . '/' . ltrim($thumbPath, '/');
            if (!file_exists($checkFull)) {
                if (file_exists(__DIR__ . '/images/placeholder.png')) {
                    $thumbPath = 'images/placeholder.png';
                } else {
                    $thumbPath = $projectFallback;
                }
            }

            // sanitize outputs
            $cid = (int)$course['id'];
            $title = h($course['title']);
            $descRaw = $course['description'] ?? '';
            $desc = h(mb_substr($descRaw, 0, 120));

            // open course detail page (view_course.php)
            $courseUrl = ($basePath ?: '') . '/view_course.php?id=' . rawurlencode($cid);
            $courseUrlEsc = h($courseUrl);
        ?>
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card card-course shadow-sm h-100"
                 role="button"
                 tabindex="0"
                 onclick="window.location.href='<?= $courseUrlEsc ?>';"
                 onkeypress="if (event.key === 'Enter' || event.keyCode === 13) window.location.href='<?= $courseUrlEsc ?>';"
                 aria-label="Open course <?= $title ?>"
            >
                <img src="<?= h($thumbPath) ?>" alt="<?= $title ?>" loading="lazy">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= $title ?></h5>
                    <p class="card-text text-muted small mb-3"><?= $desc ?><?= (mb_strlen($descRaw) > 120) ? '...' : '' ?></p>

                    <!-- Single Continue Learning button -->
                    <div class="mt-auto">
                        <a href="<?= $courseUrlEsc ?>" class="btn btn-primary btn-sm w-100">Continue Learning</a>
                    </div>
                </div>
            </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-info no-courses">
            You haven't enrolled in any course yet. Browse courses to enroll.
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- optional: bootstrap js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // profile dropdown toggle
  const profileToggle = document.getElementById('profileToggle');
  const profileMenu = document.getElementById('profileMenu');
  profileToggle && profileToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    profileMenu.classList.toggle('active');
  });
  window.addEventListener('click', (e) => {
    if (profileMenu && !profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
      profileMenu.classList.remove('active');
    }
  });
</script>
</body>
</html>
