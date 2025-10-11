<?php
    // Database credentials
    $servername = "localhost";
    $username = "root";
    $password = "nayabfatima@123";
    $dbname = "eduflect";
    $port = 3307; // ✅ your custom port

    // Create and check connection
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    // -------------------------------^^^^^^
    // Added $port here!

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
