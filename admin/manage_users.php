<?php
// admin/manage_users.php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

// --- Admin only ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Admins only"));
    exit;
}

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

// --- Handle delete request (POST) ---
$flash_success = null;
$flash_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $posted_csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $posted_csrf)) {
        $flash_error = "Invalid request (CSRF mismatch).";
    } else {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid <= 0) {
            $flash_error = "Invalid user id.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            $ok = true;

            // 1) delete enrollments for this user (if any)
            $delEnroll = $conn->prepare("DELETE FROM enrollments WHERE user_id = ?");
            if ($delEnroll) {
                $delEnroll->bind_param("i", $uid);
                if (!$delEnroll->execute()) $ok = false;
                $delEnroll->close();
            } else {
                $ok = false;
            }

            // 2) delete user record
            $delUser = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($delUser) {
                $delUser->bind_param("i", $uid);
                if (!$delUser->execute()) $ok = false;
                $delUser->close();
            } else {
                $ok = false;
            }

            if ($ok) {
                $conn->commit();
                $flash_success = "User deleted successfully (and their enrollments removed).";
            } else {
                $conn->rollback();
                $flash_error = "Failed to delete user. Try again.";
            }
        }
    }
}

// --- Fetch users ---
$stmt = $conn->prepare("SELECT id, fullname, email, role, DATE_FORMAT(reg_date, '%Y-%m-%d') AS reg_date FROM users ORDER BY reg_date DESC");
$users = [];
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    $users = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// --- Fetch enrollments for all users in one query (if any users) ---
$userEnrolls = []; // user_id => array of ['course_id'=>..,'title'=>..,'enrolled_at'=>..]
if (!empty($users)) {
    $ids = array_column($users, 'id');
    // build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT e.user_id, c.id AS course_id, c.title, DATE_FORMAT(e.enrolled_at, '%Y-%m-%d') AS enrolled_at
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.user_id IN ($placeholders)
            ORDER BY e.enrolled_at DESC";
    $stmt2 = $conn->prepare($sql);
    if ($stmt2) {
        // bind params dynamically
        $bind_names = [];
        $bind_names[] = $types;
        foreach ($ids as $k => $v) $bind_names[] = $v;
        // call_user_func_array requires references
        $tmp = [];
        foreach ($bind_names as $key => $val) $tmp[$key] = &$bind_names[$key];
        call_user_func_array([$stmt2, 'bind_param'], $tmp);
        $stmt2->execute();
        $r = $stmt2->get_result();
        while ($row = $r->fetch_assoc()) {
            $uid = (int)$row['user_id'];
            if (!isset($userEnrolls[$uid])) $userEnrolls[$uid] = [];
            $userEnrolls[$uid][] = $row;
        }
        $stmt2->close();
    }
}

// helper
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Users - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <style>
    .avatar { width:40px;height:40px;border-radius:50%;background:#f0f2ff;color:#2a0f8a;display:flex;align-items:center;justify-content:center;font-weight:700; }
    .badge-course { background:#eef2ff;color:#2a0f8a;border-radius:8px;padding:.25rem .5rem;margin-right:.25rem;font-size:.85rem; display:inline-block; }
    .table-actions .btn { margin-right:.25rem; }
    .flash { position:fixed; top:88px; right:20px; z-index:1200; min-width:260px; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage Users</h3>
    <a class="btn btn-outline-secondary" href="dashboard.php"><i class="fa fa-arrow-left me-1"></i> Back to dashboard</a>
  </div>

  <?php if ($flash_success): ?>
    <div class="alert alert-success flash"><?= e($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert alert-danger flash"><?= e($flash_error) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Enrolled</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($users) === 0): ?>
              <tr><td colspan="7" class="text-center py-4">No users yet.</td></tr>
            <?php else: foreach ($users as $i => $u): 
              $uid = (int)$u['id'];
              $enrolled = $userEnrolls[$uid] ?? [];
            ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar"><?= e(strtoupper(substr($u['fullname'] ?? 'U',0,1))) ?></div>
                    <div>
                      <div class="fw-semibold"><?= e($u['fullname']) ?></div>
                      <div class="small text-muted">ID: <?= $uid ?></div>
                    </div>
                  </div>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><?= e(ucfirst($u['role'])) ?></td>
                <td>
                  <?php if (empty($enrolled)): ?>
                    <span class="text-muted small">—</span>
                  <?php else: ?>
                    <?php foreach (array_slice($enrolled, 0, 3) as $c): ?>
                      <span class="badge-course"><?= e($c['title']) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($enrolled) > 3): ?>
                      <button class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#enrollModal<?= $uid ?>">+<?= count($enrolled)-3 ?> more</button>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td><?= e($u['reg_date']) ?></td>
                <td class="table-actions">
                  <!-- View enrollments modal trigger (if any) -->
                  <?php if (!empty($enrolled)): ?>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#enrollModal<?= $uid ?>"><i class="fa fa-eye me-1"></i> Enrollments</button>
                  <?php endif; ?>

                  <!-- Delete (CSRF protected form) -->
                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user and all their enrollments? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa fa-trash"></i> Delete</button>
                  </form>
                </td>
              </tr>

              <!-- Modal: Enrollments list for this user -->
              <?php if (!empty($enrolled)): ?>
                <div class="modal fade" id="enrollModal<?= $uid ?>" tabindex="-1" aria-labelledby="enrollModalLabel<?= $uid ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="enrollModalLabel<?= $uid ?>">Enrollments — <?= e($u['fullname']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <ul class="list-group">
                          <?php foreach ($enrolled as $row): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                              <div>
                                <div class="fw-semibold"><?= e($row['title']) ?></div>
                                <small class="text-muted">Enrolled: <?= e($row['enrolled_at'] ?? '') ?></small>
                              </div>
                              <a href="../view_course.php?id=<?= (int)$row['course_id'] ?>" class="btn btn-sm btn-outline-primary">View Course</a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <p class="mt-3 text-muted small">Tip: Deleting a user will also remove their enrollments from the <code>enrollments</code> table.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
