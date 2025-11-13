<?php
session_start();
require_once __DIR__ . '/Backend/db_connect.php';

$course_id = (int)($_GET['course_id'] ?? 0);
if ($course_id <= 0) {
    $_SESSION['flash_error'] = "Invalid course selected.";
    header("Location: index.php");
    exit;
}

// if user not logged in → redirect to login
if (!isset($_SESSION['user_id'])) {
    $back = urlencode('view_course.php?id=' . $course_id);
    header("Location: login.php?redirect={$back}");
    exit;
}

// user logged in
$user_id = (int)$_SESSION['user_id'];
$user_role = strtolower($_SESSION['role'] ?? '');

// allow only student
if ($user_role !== 'student') {
    $_SESSION['flash_error'] = "Only students can enroll in courses.";
    header("Location: view_course.php?id={$course_id}");
    exit;
}

// verify course exists
$stmt = $conn->prepare("SELECT id, title FROM courses WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    $_SESSION['flash_error'] = "Course not found.";
    header("Location: index.php");
    exit;
}

// create enrollments table if not exists
$conn->query("
CREATE TABLE IF NOT EXISTS enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_course (user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// check if already enrolled
$stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    $_SESSION['flash_success'] = "You are already enrolled in this course.";
    header("Location: view_course.php?id={$course_id}");
    exit;
}

// enroll now
$stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $course_id);
if ($stmt->execute()) {
    $_SESSION['flash_success'] = "You have successfully enrolled in '{$course['title']}'.";
    header("Location: view_course.php?id={$course_id}");
} else {
    $_SESSION['flash_error'] = "Error enrolling. Please try again later.";
    header("Location: view_course.php?id={$course_id}");
}
$stmt->close();
