<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    die("Login required");
}

$user_id = (int)$_SESSION['user_id'];
$course_id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$stmt->close();

$back = $_SERVER['HTTP_REFERER'] ?? 'courses.php';
header("Location: " . $back);
exit;
