<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Include database configuration
$conn = require_once 'db-config.php';

// Get issue ID from URL
$issueId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($issueId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Check if the current user is the creator of the issue
$stmt = $conn->prepare("SELECT created_by FROM issues WHERE id = ?");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Issue doesn't exist
    header('Location: dashboard.php');
    exit;
}

$issue = $result->fetch_assoc();
$stmt->close();

// Verify that the current user is the creator of the issue
if ($_SESSION['user_id'] != $issue['created_by']) {
    // User is not the creator, redirect to dashboard
    header('Location: dashboard.php');
    exit;
}

// Delete all comments associated with the issue first (to maintain referential integrity)
$stmt = $conn->prepare("DELETE FROM comments WHERE issue_id = ?");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$stmt->close();

// Now delete the issue
$stmt = $conn->prepare("DELETE FROM issues WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $issueId, $_SESSION['user_id']);

if ($stmt->execute()) {
    // Issue deleted successfully, redirect to dashboard
    $stmt->close();
    $conn->close();
    header('Location: dashboard.php');
    exit;
} else {
    // Error occurred during deletion
    $stmt->close();
    $conn->close();
    
    // Redirect with error message
    header('Location: dashboard.php?error=Failed+to+delete+issue');
    exit;
}
?>