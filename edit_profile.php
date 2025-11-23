<?php
session_start();
require_once __DIR__ . '/Backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['user_id'];
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// fetch current user
$stmt = $conn->prepare("SELECT id, fullname, email, avatar, bio, phone, social_links FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['flash_error'] = "User not found.";
    header("Location: index.php");
    exit;
}

// helpers
function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
$uploadsDir = __DIR__ . '/uploads/avatars/';
$publicUploads = 'uploads/avatars/';

// handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // validation
    $errors = [];
    if ($fullname === '') $errors[] = "Full name cannot be empty.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email.";

    // check email uniqueness (if changed)
    if ($email !== $user['email']) {
        $s = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $s->bind_param("si", $email, $uid);
        $s->execute();
        $r = $s->get_result();
        if ($r->num_rows) $errors[] = "Email already in use by another account.";
        $s->close();
    }

    // password change: if new_password provided, verify current password and confirm
    $wantPasswordChange = ($new_password !== '' || $confirm_password !== '');
    if ($wantPasswordChange) {
        if ($new_password !== $confirm_password) $errors[] = "New password and confirmation do not match.";
        if (strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters.";
        // verify current password
        if (!password_verify($current_password, $user['password_hash'])) $errors[] = "Current password is incorrect.";
    }

    // avatar upload (optional)
    $avatarFilename = $user['avatar']; // keep existing by default
    if (!empty($_FILES['avatar']['name'])) {
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $file = $_FILES['avatar'];
        // basic validations
        $allowed = ['image/jpeg','image/png','image/webp'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Avatar upload failed (error ".$file['error'].").";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "Avatar must be smaller than 2MB.";
        } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $errors[] = "Avatar must be JPG, PNG or WEBP.";
        } else {
            // create safe filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $uploadsDir . $safe;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $avatarFilename = $safe;
                // optionally remove old avatar (if exists and not default)
                if (!empty($user['avatar']) && file_exists($uploadsDir . $user['avatar'])) {
                    @unlink($uploadsDir . $user['avatar']);
                }
            } else {
                $errors[] = "Failed to move uploaded avatar.";
            }
        }
    }

    if (empty($errors)) {
        // prepare update
        if ($wantPasswordChange) {
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE users SET fullname = ?, email = ?, avatar = ?, bio = ?, password_hash = ?, updated_at = NOW() WHERE id = ?");
            $u->bind_param("sssssi", $fullname, $email, $avatarFilename, $bio, $newHash, $uid);
        } else {
            $u = $conn->prepare("UPDATE users SET fullname = ?, email = ?, avatar = ?, bio = ?, updated_at = NOW() WHERE id = ?");
            $u->bind_param("ssssi", $fullname, $email, $avatarFilename, $bio, $uid);
        }

        if ($u->execute()) {
            // sync session fullname
            $_SESSION['fullname'] = $fullname;
            $_SESSION['flash_success'] = "Profile updated successfully.";
            $u->close();
            header("Location: edit_profile.php");
            exit;
        } else {
            $errors[] = "Database update failed: " . $conn->error;
            $u->close();
        }
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header("Location: edit_profile.php");
        exit;
    }
}

// compute avatar public URL for display
$avatarPublic = 'images/avatar-placeholder.png'; // default placeholder (create this file)
if (!empty($user['avatar']) && file_exists($uploadsDir . $user['avatar'])) {
    $avatarPublic = $publicUploads . $user['avatar'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Profile — eduflect</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;}
    .avatar-preview{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 6px 18px rgba(0,0,0,.08);}
    .card-profile{max-width:900px;margin:28px auto;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">eduflect</a>
    <div class="d-flex ms-auto align-items-center">
      <a class="btn btn-outline-secondary me-2" href="dashboard.php">← Dashboard</a>
      <div class="dropdown">
        <a class="btn btn-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <?= h($_SESSION['fullname'] ?? 'User') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="edit_profile.php">Edit Profile</a></li>
          <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="card card-profile shadow-sm p-3">
  <div class="card-body">
    <h3 class="mb-3">Edit Profile</h3>

    <?php if ($flash_success): ?>
      <div class="alert alert-success"><?= h($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="alert alert-danger"><?= h($flash_error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-4 text-center">
        <img id="avatarPreview" src="<?= h($avatarPublic) ?>" alt="avatar" class="avatar-preview mb-2">
        <div class="mb-2">
          <label class="btn btn-outline-secondary btn-sm">
            Change Avatar <input type="file" name="avatar" accept="image/*" class="d-none" id="avatarInput">
          </label>
        </div>
        <p class="text-muted small">JPG / PNG / WEBP. Max 2MB.</p>
      </div>

      <div class="col-md-8">
        <div class="mb-3">
          <label class="form-label">Full name</label>
          <input name="fullname" class="form-control" value="<?= h($user['fullname']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" value="<?= h($user['email']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Bio / About</label>
          <textarea name="bio" class="form-control" rows="3"><?= h($user['bio'] ?? '') ?></textarea>
        </div>

        <hr>
        <h6>Change password (optional)</h6>
        <div class="row">
          <div class="col-md-12 mb-2">
            <label class="form-label">Current password</label>
            <input name="current_password" type="password" class="form-control" placeholder="Enter current password to change">
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">New password</label>
            <input name="new_password" type="password" class="form-control" placeholder="New password (min 6 chars)">
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Confirm new password</label>
            <input name="confirm_password" type="password" class="form-control" placeholder="Confirm new password">
          </div>
        </div>

        <div class="mt-3">
          <button class="btn btn-primary">Save changes</button>
          <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('avatarInput').addEventListener('change', function(e){
    const f = this.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = function(ev){
        document.getElementById('avatarPreview').src = ev.target.result;
    };
    reader.readAsDataURL(f);
});
</script>
</body>
</html>
