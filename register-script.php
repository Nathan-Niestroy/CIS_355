<?php
// Include database configuration
$conn = require_once 'config.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate form data
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Create username from email
$username = strtolower(explode('@', $email)[0]);

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use']);
    exit;
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Generate a unique username by adding a number
    $baseUsername = $username;
    $counter = 1;
    
    do {
        $username = $baseUsername . $counter;
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $counter++;
    } while ($result->num_rows > 0);
}

// Hash the password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, username, password_hash) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $firstName, $lastName, $email, $username, $passwordHash);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful', 'username' => $username]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
