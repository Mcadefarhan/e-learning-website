<?php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php?error=" . urlencode("Admins only"));
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid course ID");
}

// Fetch course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    die("Course not found");
}

$errors = [];
$success = "";

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $instructor = trim($_POST['instructor']);
    $price = trim($_POST['price']);
    $video_url = trim($_POST['video_url']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);

    $image_name = $course['image']; // default old image

    // Image upload
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['error'] === 0 && in_array(mime_content_type($file['tmp_name']), $allowed)) {

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_img = bin2hex(random_bytes(8)) . '.' . $ext;

            $dest = __DIR__ . '/../uploads/courses/' . $new_img;
            move_uploaded_file($file['tmp_name'], $dest);

            // delete old image
            if (!empty($course['image']) && file_exists(__DIR__ . '/../uploads/courses/' . $course['image'])) {
                unlink(__DIR__ . '/../uploads/courses/' . $course['image']);
            }

            $image_name = $new_img;
        } else {
            $errors[] = "Invalid image file.";
        }
    }

    if (empty($errors)) {

        $stmt2 = $conn->prepare("UPDATE courses SET title=?, instructor=?, price=?, video_url=?, description=?, category=?, image=? WHERE id=?");
        $stmt2->bind_param("ssdssssi", $title, $instructor, $price, $video_url, $description, $category, $image_name, $id);

        if ($stmt2->execute()) {
            $success = "Course updated successfully!";
        } else {
            $errors[] = "Database error: " . $stmt2->error;
        }
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <a href="manage_courses.php" class="btn btn-secondary mb-3">← Back</a>
    <h3>Edit Course</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode("<br>", $errors) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label>Title</label>
            <input name="title" class="form-control" value="<?= htmlspecialchars($course['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Instructor</label>
            <input name="instructor" class="form-control" value="<?= htmlspecialchars($course['instructor']) ?>">
        </div>

        <div class="mb-3">
            <label>Price</label>
            <input name="price" type="number" class="form-control" value="<?= htmlspecialchars($course['price']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <input name="category" class="form-control" value="<?= htmlspecialchars($course['category']) ?>">
        </div>

        <div class="mb-3">
            <label>YouTube Video URL</label>
            <input name="video_url" class="form-control" value="<?= htmlspecialchars($course['video_url']) ?>">
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($course['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label>Course Image (Upload new if needed)</label><br>
            <?php if (!empty($course['image'])): ?>
                <img src="../uploads/courses/<?= htmlspecialchars($course['image']) ?>" style="height:80px;border-radius:6px;">
            <?php endif; ?>
            <input type="file" name="image" class="form-control mt-2">
        </div>

        <button class="btn btn-primary">Update Course</button>

    </form>
</div>
</body>
</html>
