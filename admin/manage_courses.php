<?php
// admin/manage_courses.php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

// admin check
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Admins only"));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

// fetch courses
$stmt = $conn->prepare("SELECT id, title, instructor, price, image, video_url, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM courses ORDER BY created_at DESC");
$stmt->execute();
$res = $stmt->get_result();
$courses = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// helper to make YouTube embed link
function youtube_embed($url) {
    if (empty($url)) return null;
    $url = trim($url);
    if (strpos($url, 'watch?v=') !== false) {
        $id = explode('v=', parse_url($url, PHP_URL_QUERY))[1] ?? '';
        return 'https://www.youtube.com/embed/' . strtok($id, '&');
    }
    if (strpos($url, 'youtu.be/') !== false) {
        $id = explode('youtu.be/', $url)[1] ?? '';
        return 'https://www.youtube.com/embed/' . strtok($id, '?');
    }
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    return null;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Courses - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .img-thumb{width:80px;height:50px;object-fit:cover;border-radius:6px;}
    .video-thumb iframe{width:120px;height:70px;border-radius:6px;}
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage Courses</h3>
    <a class="btn btn-primary" href="create_course.php">+ Create course</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Image</th>
              <th>Video</th>
              <th>Title</th>
              <th>Instructor</th>
              <th>Price</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($courses) === 0): ?>
              <tr><td colspan="8" class="text-center py-4">No courses yet.</td></tr>
            <?php else: foreach ($courses as $i => $c): ?>
              <tr>
                <td><?= $i+1 ?></td>

                <!-- Image -->
                <td>
                  <?php if (!empty($c['image']) && file_exists(__DIR__ . '/../uploads/courses/' . $c['image'])): ?>
                    <img class="img-thumb" src="../uploads/courses/<?= htmlspecialchars($c['image']) ?>" alt="">
                  <?php else: ?>
                    <div class="text-muted small">No image</div>
                  <?php endif; ?>
                </td>

                <!-- YouTube video -->
                <td class="video-thumb">
                  <?php 
                    $embed = youtube_embed($c['video_url']);
                    if ($embed): ?>
                      <iframe src="<?= htmlspecialchars($embed) ?>?rel=0&modestbranding=1"
                              allowfullscreen></iframe>
                    <?php else: ?>
                      <div class="text-muted small">No video</div>
                  <?php endif; ?>
                </td>

                <!-- Details -->
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['instructor']) ?></td>
                <td>₹<?= number_format((float)$c['price'],2) ?></td>
                <td><?= htmlspecialchars($c['created_at']) ?></td>

                <!-- Actions -->
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="edit_course.php?id=<?= $c['id'] ?>">Edit</a>
                  <form action="delete_course.php" method="POST" style="display:inline" onsubmit="return confirm('Delete this course?');">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <p class="mt-3 text-muted small">Tip: You can add YouTube (Unlisted) links when creating courses. Video will appear here automatically.</p>
</div>
</body>
</html>
