<?php
session_start();
require_once __DIR__ . "/db_connect.php"; // Ensure correct path to db_connect.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        // Agar koi field empty hai
        header("Location: login.php?error=" . urlencode("Please fill in all fields."));
        exit;
    }

    // User ko database me dhundo
    $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Agar password hashed hai
        if (password_verify($password, $user['password'])) {
            // Session set karo
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];

            // Dashboard pe redirect
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Wrong password
            header("Location: ../login.php?error=" . urlencode("Incorrect password."));
            exit;
        }
    } else {
        // User not found
        header("Location: ../login.php?error=" . urlencode("No account found with that email."));
        exit;
    }
} else {
    // Agar direct access hua login_process.php pe
    header("Location: login.php");
    exit;
}
?>
