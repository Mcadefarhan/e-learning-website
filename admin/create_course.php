<?php
// admin/create_course.php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

// Only admins can access
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Admins only"));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '0');
    $video_url = trim($_POST['video_url'] ?? '');
    $category = trim($_POST['category'] ?? 'Development');

    if ($title === '') $errors[] = "Title is required.";

    // Handle image upload if present
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $f = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } elseif (!in_array(mime_content_type($f['tmp_name']), $allowed)) {
            $errors[] = "Invalid image type (only JPG, PNG, WEBP allowed).";
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image size must be ≤ 2MB.";
        } else {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $image_name = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/courses/' . $image_name;

            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded image.";
                $image_name = null;
            }
        }
    }

    // If no errors → insert into DB
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO courses (title, instructor, description, price, image, video_url, category) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("sssdsss", $title, $instructor, $description, $price, $image_name, $video_url, $category);

            if ($stmt->execute()) {
                header("Location: manage_courses.php?success=" . urlencode("Course created successfully"));
                exit;
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create Course - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <a href="manage_courses.php" class="btn btn-link">&larr; Back</a>

  <div class="card shadow-sm p-3">
    <h4 class="mb-3">Create New Course</h4>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <!-- Title -->
      <div class="mb-2">
        <label class="form-label">Course Title</label>
        <input name="title" class="form-control" placeholder="Enter course title" required>
      </div>

      <!-- Instructor -->
      <div class="mb-2">
        <label class="form-label">Instructor Name</label>
        <input name="instructor" class="form-control" placeholder="Instructor name">
      </div>

      <!-- Price -->
      <div class="mb-2">
        <label class="form-label">Price (INR)</label>
        <input name="price" type="number" class="form-control" value="0" min="0">
      </div>

      <!-- Category -->
      <div class="mb-2">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
          <option value="Development">Development</option>
          <option value="Design">Design</option>
          <option value="Marketing">Marketing</option>
          <option value="IT & Software">IT & Software</option>
        </select>
      </div>

      <!-- Image -->
      <div class="mb-2">
        <label class="form-label">Course Thumbnail (jpg/png/webp, ≤2MB)</label>
        <input type="file" name="image" accept="image/*" class="form-control">
      </div>

      <!-- YouTube video -->
      <div class="mb-2">
        <label class="form-label">YouTube Video Link</label>
        <input name="video_url" type="url" class="form-control" placeholder="https://youtu.be/your-video-id">
        <div class="form-text">Paste a YouTube video link (Unlisted works too).</div>
      </div>

      <!-- Description -->
      <div class="mb-2">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="6" placeholder="Write a short description of the course"></textarea>
      </div>

      <button class="btn btn-primary mt-2">Create Course</button>
    </form>
  </div>
</div>

</body>
</html>
