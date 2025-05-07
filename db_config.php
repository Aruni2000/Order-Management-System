<?php
// Database configuration
$servername = "gator4423";
$username = "imwijqte_db";
$password = "imwijqte_db2025a";
$dbname = "imwijqte_feit_db";

// Create database connection
function connectDB() {
    global $servername, $username, $password, $dbname;
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>