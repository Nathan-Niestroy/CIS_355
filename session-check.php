<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    echo json_encode([
        'loggedIn' => true,
        'userId' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'firstName' => $_SESSION['first_name'] ?? '',
        'lastName' => $_SESSION['last_name'] ?? ''
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>
