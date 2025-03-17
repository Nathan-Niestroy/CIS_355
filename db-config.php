<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "crud_app";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Create initial admin user if not exists
$adminUsername = "admin";
$adminPassword = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, username, password_hash) VALUES (?, ?, ?, ?, ?)");
    $firstName = "Admin";
    $lastName = "User";
    $email = "admin@example.com";
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $adminUsername, $adminPassword);
    $stmt->execute();
}

// Return connection object
return $conn;
?>
