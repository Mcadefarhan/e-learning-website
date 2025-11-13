<?php
// admin/dashboard.php
session_start();

// Only admins allowed
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Access denied. Admins only."));
    exit;
}

$fullname = $_SESSION['fullname'] ?? 'Admin';
$initials = strtoupper(substr($fullname, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - eduflect</title>

  <!-- Assets -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* Base */
    body { font-family: 'Poppins', sans-serif; background-color:#f0f2f5; margin:0; }

    /* Keep bootstrap's container-fluid behavior intact (do not limit its width) */
    /* Top navbar */
    .navbar-logged-in { background:#fff; box-shadow:0 2px 10px rgba(0,0,0,0.05); position:fixed; width:100%; z-index:1000; }
    .navbar-logged-in .container-fluid { align-items:center; }

    /* Avatar & dropdown */
    .profile-avatar { width:40px; height:40px; background:#1c1d1f; color:#fff; border-radius:50%; display:flex;align-items:center;justify-content:center; font-weight:700; cursor:pointer; position:relative; }
    .notification-dot { width:10px;height:10px;background:#5624d0;border:2px solid #fff;border-radius:50%;position:absolute;top:0;right:0; transform: translate(30%, -30%); }
    .profile-dropdown { position:absolute; top:56px; right:0; width:230px; background:#fff; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.12); overflow:hidden; opacity:0; visibility:hidden; transform:translateY(-8px); transition:all .22s; }
    .profile-dropdown.active { opacity:1; visibility:visible; transform:none; }
    .profile-dropdown-header { background:#f8f9fa; padding:12px; text-align:center; border-bottom:1px solid #eee; }
    .profile-dropdown a { display:flex; align-items:center; padding:10px 14px; color:#1c1d1f; text-decoration:none; }
    .profile-dropdown a i { width:20px; color:#5624d0; margin-right:10px; }
    .profile-dropdown .logout { color:#dc3545 !important; }

    /* Main layout - full width */
    .main-wrapper { padding-top:88px; width:100%; box-sizing:border-box; }

    /* hero / welcome box - full-bleed background; content padded */
    .welcome-header { background:#1c1d1f; color:#fff; padding:3.5rem 0; margin-bottom:24px; }
    .welcome-header .welcome-inner { max-width:1600px; margin:0 auto; padding-left:1rem; padding-right:1rem; }

    /* cards and tables expand to full available width */
    .card { width:100%; }
    .card-stat { border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.06); }
    .small-muted { color:#6b6f73; font-size:.95rem; }

    /* manage users table */
    .manage-users-card .table { white-space:nowrap; }

    footer { background:#111; color:#fff; padding:40px 0; margin-top:32px; width:100%; box-sizing:border-box; }
    footer .footer-inner { max-width:1600px; margin:0 auto; padding-left:1rem; padding-right:1rem; }

    /* make the card sections not look cramped on very wide screens */
    .content-row { max-width:1600px; margin:0 auto; padding-left:1rem; padding-right:1rem; }

    @media (max-width:768px) {
      .welcome-header { padding:2rem 0; }
      .profile-dropdown { right: -8px; }
    }
  </style>
</head>
<body>

<!-- Topbar (full width) -->
<nav class="navbar navbar-expand-lg navbar-logged-in">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="#">eduflect Admin</a>

    <div class="ms-auto d-flex align-items-center">
      <!-- Admin-only quick links -->
      <a href="manage_users.php" class="nav-link me-3">Manage Users</a>
      <a href="manage_courses.php" class="nav-link me-3">Manage Courses</a>
      <a href="site_reports.php" class="nav-link me-3">Reports</a>

      <div class="profile-wrapper position-relative">
        <div id="profileToggle" class="profile-avatar"><?= htmlspecialchars($initials) ?><span class="notification-dot"></span></div>

        <div id="profileMenu" class="profile-dropdown">
          <div class="profile-dropdown-header">
            <strong><?= htmlspecialchars($fullname) ?></strong>
            <br/><small>Admin</small>
          </div>
          <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
          <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
          <hr style="margin:6px 0;border:none;border-top:1px solid #eee;">
          <a href="../login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Main wrapper (full width). Use inner containers with max-width to avoid very wide lines -->
<div class="main-wrapper">

  <!-- Full-bleed welcome background; inner content constrained by .welcome-inner -->
  <div class="welcome-header">
    <div class="welcome-inner">
      <h1 class="display-5 fw-bold text-white">Welcome back, <?= htmlspecialchars($fullname) ?>!</h1>
      <p class="lead small-muted text-white-50">Admin dashboard — manage users, courses, approvals and reports.</p>
    </div>
  </div>

  <!-- Dashboard content: full-bleed background but content uses content-row for max width + gutters -->
  <div class="content-row">
    <!-- Stats row -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card card-stat p-3">
          <div class="d-flex align-items-center">
            <div class="me-3"><i class="fa-solid fa-users fa-2x" style="color:#5624d0;"></i></div>
            <div>
              <div class="small-muted">Total Users</div>
              <div class="fs-4 fw-bold">124</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card card-stat p-3">
          <div class="d-flex align-items-center">
            <div class="me-3"><i class="fa-solid fa-book fa-2x" style="color:#5624d0;"></i></div>
            <div>
              <div class="small-muted">Courses</div>
              <div class="fs-4 fw-bold">45</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card card-stat p-3">
          <div class="d-flex align-items-center">
            <div class="me-3"><i class="fa-solid fa-list-check fa-2x" style="color:#5624d0;"></i></div>
            <div>
              <div class="small-muted">Pending Approvals</div>
              <div class="fs-4 fw-bold">7</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card card-stat p-3">
          <div class="d-flex align-items-center">
            <div class="me-3"><i class="fa-solid fa-sack-dollar fa-2x" style="color:#5624d0;"></i></div>
            <div>
              <div class="small-muted">Revenue</div>
              <div class="fs-4 fw-bold">₹1.2L</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Recent Activity</h5>
            <a href="site_reports.php" class="small-muted">View all</a>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Action</th><th>Target</th><th>User</th><th>Date</th><th>Status</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>Approved Course</td><td>Advanced JavaScript</td><td>Amir Suhail</td><td>2025-11-11</td><td><span class="badge bg-success">Completed</span></td></tr>
                <tr><td>2</td><td>Removed User</td><td>John Doe</td><td>Md Farhan</td><td>2025-11-10</td><td><span class="badge bg-danger">Deleted</span></td></tr>
                <tr><td>3</td><td>Added Instructor</td><td>Sarah Ali</td><td>Admin</td><td>2025-11-09</td><td><span class="badge bg-info text-dark">Updated</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Manage Users full width inside content-row -->
      <div class="col-12 mb-4">
        <div class="card p-3 manage-users-card">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Manage Users</h5>
            <a href="manage_users.php" class="small-muted">See all users</a>
          </div>

          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td>1</td><td>Md Farhan Kalim</td><td>farhankhan22eu@gmail.com</td><td>Admin</td><td>2025-10-11</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="edit_user.php?id=1"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a class="btn btn-sm btn-outline-danger" href="delete_user.php?id=1" onclick="return confirm('Delete user?');"><i class="fa-solid fa-trash"></i></a>
                  </td></tr>

                <tr><td>2</td><td>Danish Ahmed</td><td>farhan.054641@tmu.ac.in</td><td>Student</td><td>2025-10-11</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="edit_user.php?id=2"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a class="btn btn-sm btn-outline-danger" href="delete_user.php?id=2" onclick="return confirm('Delete user?');"><i class="fa-solid fa-trash"></i></a>
                  </td></tr>

                <tr><td>3</td><td>Amir Suhail</td><td>amir057765@tmu.ac.in</td><td>Student</td><td>2025-10-11</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="edit_user.php?id=3"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a class="btn btn-sm btn-outline-danger" href="delete_user.php?id=3" onclick="return confirm('Delete user?');"><i class="fa-solid fa-trash"></i></a>
                  </td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div> <!-- /.content-row -->

  <!-- Footer (full width) -->
  <footer>
    <div class="footer-inner">
      <div class="row text-white">
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
      <hr class="my-4" style="border-color:#333;">
      <div class="row align-items-center">
        <div class="col-md-8">
          <p class="mb-0">© 2025 eduflect, Inc. All rights reserved.</p>
        </div>
        <div class="col-md-4 text-end">
          <a href="#" class="text-white me-3 fs-5"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="text-white me-3 fs-5"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-white fs-5"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </div>
  </footer>

</div> <!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggle = document.getElementById('profileToggle');
  const menu = document.getElementById('profileMenu');
  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('active');
  });
  window.addEventListener('click', (e) => {
    if (!toggle.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('active');
  });
</script>
</body>
</html>
