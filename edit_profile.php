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
$stmt = $conn->prepare("SELECT id, fullname, email, avatar, bio, phone, social_links, password FROM users WHERE id = ?");
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
    $phone    = trim($_POST['phone'] ?? '');
    $social_links = trim($_POST['social_links'] ?? '');
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
        if (!password_verify($current_password, $user['password'])) $errors[] = "Current password is incorrect.";
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
            $u = $conn->prepare("UPDATE users SET fullname = ?, email = ?, avatar = ?, bio = ?, phone = ?, social_links = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $u->bind_param("sssssssi", $fullname, $email, $avatarFilename, $bio, $phone, $social_links, $newHash, $uid);
        } else {
            $u = $conn->prepare("UPDATE users SET fullname = ?, email = ?, avatar = ?, bio = ?, phone = ?, social_links = ?, updated_at = NOW() WHERE id = ?");
            $u->bind_param("ssssssi", $fullname, $email, $avatarFilename, $bio, $phone, $social_links, $uid);
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

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{--accent:#5b8def;--muted:#6c757d}
    body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;background:linear-gradient(180deg,#f6f9ff 0%, #ffffff 40%);min-height:100vh}
    .card-profile{max-width:980px;margin:36px auto;border:none;border-radius:18px;overflow:hidden}
    .left-panel{background:linear-gradient(135deg, rgba(91,141,239,.08), rgba(91,141,239,.02));padding:30px}
    .avatar-wrap{width:128px;height:128px;border-radius:22px;overflow:hidden;box-shadow:0 10px 30px rgba(91,141,239,.12);}
    .avatar-wrap img{width:100%;height:100%;object-fit:cover;display:block}
    .small-muted{color:var(--muted);font-size:.9rem}
    .form-label.required:after{content:' *';color:#d63384}
    .file-input-label{cursor:pointer}
    .btn-save{background:var(--accent);border:none}
    .btn-save:hover{filter:brightness(.95)}
    .field-note{font-size:.85rem;color:#6b7280}
    @media (max-width:767px){.left-panel{text-align:center}.avatar-wrap{margin:0 auto 14px}}
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

<div class="card card-profile shadow-sm">
  <div class="row g-0">
    <div class="col-md-4 left-panel d-flex flex-column justify-content-center align-items-start">
      <div class="avatar-wrap mb-3">
        <img id="avatarPreviewLeft" src="<?= h($avatarPublic) ?>" alt="avatar">
      </div>
      <h5 class="mb-1"><?= h($user['fullname']) ?></h5>
      <div class="small-muted mb-2"><?= h($user['email']) ?></div>
      <div class="small-muted mb-3"><?= h($user['bio'] ?? 'Add a short bio about yourself') ?></div>
      <div class="w-100">
        <label class="btn btn-outline-secondary btn-sm file-input-label">
          <i class="bi bi-image"></i> Change Avatar <input type="file" name="avatar" accept="image/*" class="d-none" form="profileForm" id="avatarInputLeft">
        </label>
        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">← Back</a>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card-body p-4">
        <h4 class="mb-3">Edit Profile</h4>

        <?php if ($flash_success): ?>
          <div class="alert alert-success"><?= h($flash_success) ?></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
          <div class="alert alert-danger"><?= h($flash_error) ?></div>
        <?php endif; ?>

        <form id="profileForm" method="post" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
          <div class="col-md-6">
            <label class="form-label required">Full name</label>
            <input name="fullname" class="form-control" value="<?= h($user['fullname']) ?>" required>
            <div class="invalid-feedback">Please enter your full name.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label required">Email</label>
            <input name="email" type="email" class="form-control" value="<?= h($user['email']) ?>" required>
            <div class="invalid-feedback">Please provide a valid email address.</div>
          </div>

          <div class="col-12">
            <label class="form-label">Bio / About</label>
            <textarea name="bio" class="form-control" rows="3"><?= h($user['bio'] ?? '') ?></textarea>
            <div class="field-note mt-1">Tip: a short 1–2 line bio helps other users know you better.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input name="phone" type="text" class="form-control" value="<?= h($user['phone'] ?? '') ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Social links (comma separated)</label>
            <input name="social_links" type="text" class="form-control" value="<?= h($user['social_links'] ?? '') ?>">
            <div class="field-note">eg. https://twitter.com/you, https://linkedin.com/in/you</div>
          </div>

          <hr class="mt-3">
          <h6 class="mb-2">Change password (optional)</h6>

          <div class="col-12">
            <label class="form-label">Current password</label>
            <input name="current_password" type="password" class="form-control" placeholder="Enter current password to change">
          </div>

          <div class="col-md-6">
            <label class="form-label">New password</label>
            <input name="new_password" type="password" class="form-control" placeholder="New password (min 6 chars)">
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm new password</label>
            <input name="confirm_password" type="password" class="form-control" placeholder="Confirm new password">
          </div>

          <!-- invisible file input synced with left panel -->
          <input type="file" name="avatar" accept="image/*" class="d-none" id="avatarInput" />

          <div class="col-12 mt-3 d-flex align-items-center">
            <button type="submit" class="btn btn-save btn-lg px-4 text-white me-2"><i class="bi bi-check2-circle me-2"></i>Save changes</button>
            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
            <div class="ms-auto small-muted">Last updated: <?= h($user['updated_at'] ?? '—') ?></div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// avatar preview: sync two inputs and two previews
const avatarInputLeft = document.getElementById('avatarInputLeft');
const avatarInputMain = document.getElementById('avatarInput');
const previewLeft = document.getElementById('avatarPreviewLeft');

// when user uses left panel file picker (label), copy file to hidden main input so PHP receives it
avatarInputLeft && avatarInputLeft.addEventListener('change', function(e){
    const f = this.files[0];
    if (!f) return;
    // show preview left
    const reader = new FileReader();
    reader.onload = function(ev){ previewLeft.src = ev.target.result; };
    reader.readAsDataURL(f);

    // also set the hidden input used by the form
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(f);
    avatarInputMain.files = dataTransfer.files;
});

// client-side bootstrap validation
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>
</body>
</html> 