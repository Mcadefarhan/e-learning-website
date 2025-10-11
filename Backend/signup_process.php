<?php
// Step 1: Database connection file ko include karein
require_once 'db_connect.php';

// Step 2: Check karein ki form submit hua hai ya nahi
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Step 3: Form se data receive karein aur clean karein
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Step 4: Validate karein ki koi field khaali na ho
    if (empty($fullname) || empty($email) || empty($password)) {
        echo "Error: All fields are required.";
        exit();
    }

    // Step 5: Password ko securely hash karein (sabse zaroori)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Step 6: SQL query prepare karein (SQL Injection se bachne ke liye)
    $sql = "INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Variables ko statement se bind karein
        mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $hashed_password);

        // Step 7: Statement ko execute karein
        if (mysqli_stmt_execute($stmt)) {
            // Agar success ho, to user ko login page par bhej dein
            echo "Sign up successful! You can now log in.";
            // header("Location: ../login.html"); // Uncomment to redirect
            // exit();
        } else {
            // Error check karein (jaise ki duplicate email)
            if (mysqli_errno($conn) == 1062) { // 1062 error code duplicate entry ke liye hota hai
                echo "Error: This email is already registered.";
            } else {
                echo "Error: Could not execute the query. " . mysqli_error($conn);
            }
        }
        
        // Statement ko close karein
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Could not prepare the query. " . mysqli_error($conn);
    }

    // Step 8: Database connection ko close karein
    mysqli_close($conn);
}
?>