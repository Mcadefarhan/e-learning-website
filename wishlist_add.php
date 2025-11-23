<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    // not logged in — redirect to login and return to current page after login
    $back = urlencode($_SERVER['HTTP_REFERER'] ?? 'courses.php');
    header("Location: login.php?redirect={$back}");
    exit;
}

$user = (int)$_SESSION['user_id'];
$course = (int)($_GET['id'] ?? 0);

// basic validation
if ($course <= 0) {
    $_SESSION['flash_error'] = "Invalid course.";
    $back = $_SERVER['HTTP_REFERER'] ?? 'courses.php';
    header("Location: " . $back);
    exit;
}

// Check if already in wishlist
$stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user, $course);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // insert
    $stmt2 = $conn->prepare("INSERT INTO wishlist (user_id, course_id) VALUES (?, ?)");
    $stmt2->bind_param("ii", $user, $course);
    $stmt2->execute();
    $stmt2->close();
    $_SESSION['flash_success'] = "Added to wishlist!";
} else {
    $_SESSION['flash_success'] = "Already in wishlist.";
}

$stmt->close();

// Redirect back to the page the user came from (or courses.php if unknown)
$back = $_SERVER['HTTP_REFERER'] ?? 'courses.php';
header("Location: " . $back);
exit;
