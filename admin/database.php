<?php

$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "login_register";

// Try to connect to login_register first, then fall back to qr_attendance_db
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);

if(!$conn){
    // Try alternative database name
    $dbName = "qr_attendance_db";
    $conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);
    
    if(!$conn){
        die("Database connection failed: " . mysqli_connect_error());
    }
}

// Set charset for security
mysqli_set_charset($conn, "utf8mb4");

