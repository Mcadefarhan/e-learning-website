<?php
session_start();
require_once __DIR__ . '/../Backend/db_connect.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    die("Unauthorized");
}

$id = (int)($_POST['id'] ?? 0);
$csrf = $_POST['csrf'] ?? '';

if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
    die("CSRF error");
}

if ($id <= 0) {
    die("Invalid ID");
}

// Fetch image to delete
$stmt = $conn->prepare("SELECT image FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$img = $stmt->get_result()->fetch_assoc()['image'] ?? null;
$stmt->close();

// Delete from database
$stmt2 = $conn->prepare("DELETE FROM courses WHERE id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$stmt2->close();

// Delete image file
if ($img && file_exists(__DIR__ . '/../uploads/courses/' . $img)) {
    unlink(__DIR__ . '/../uploads/courses/' . $img);
}

header("Location: manage_courses.php?msg=" . urlencode("Course deleted"));
exit;
?>
