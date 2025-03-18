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

// Message and error variables
$message = '';
$error = '';

// Handle issue resolution
if (isset($_POST['resolve_issue']) && $_POST['resolve_issue'] === '1') {
    $stmt = $conn->prepare("UPDATE issues SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $issueId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = 'Issue marked as resolved successfully';
    } else {
        $error = 'Error resolving issue: ' . $conn->error;
    }
    
    $stmt->close();
}

// Handle new comment submission
if (isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
    // Check if issue is still open
    $checkStmt = $conn->prepare("SELECT status FROM issues WHERE id = ?");
    $checkStmt->bind_param("i", $issueId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $issueStatus = $checkResult->fetch_assoc()['status'];
    $checkStmt->close();
    
    if ($issueStatus === 'resolved') {
        $error = 'Cannot add comments to a resolved issue';
    } else {
        $comment = trim($_POST['comment']);
        $userId = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $issueId, $userId, $comment);
        
        if ($stmt->execute()) {
            $message = 'Comment added successfully';
        } else {
            $error = 'Error adding comment: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Get issue details
$stmt = $conn->prepare("SELECT i.*, u.first_name, u.last_name, u.username 
                      FROM issues i 
                      JOIN users u ON i.created_by = u.id 
                      WHERE i.id = ?");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit;
}

$issue = $result->fetch_assoc();
$stmt->close();

// Get comments for this issue
$stmt = $conn->prepare("SELECT c.*, u.first_name, u.last_name, u.username 
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.issue_id = ? 
                      ORDER BY c.created_at ASC");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$commentsResult = $stmt->get_result();
$comments = [];

if ($commentsResult->num_rows > 0) {
    while ($row = $commentsResult->fetch_assoc()) {
        $comments[] = $row;
    }
}

$stmt->close();
$conn->close();

// Check if current user is the issue creator
$isCreator = ($_SESSION['user_id'] == $issue['created_by']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Issue - <?php echo htmlspecialchars($issue['title']); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background-color: #4CAF50;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .navbar h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
        }
        
        .navbar-user span {
            margin-right: 15px;
        }
        
        .navbar-user button {
            background-color: #388E3C;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .issue-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .issue-title {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        
        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .badge-open {
            background-color: #e3f2fd;
            color: #1976D2;
        }
        
        .badge-resolved {
            background-color: #e8f5e9;
            color: #388E3C;
        }
        
        .issue-meta {
            color: #777;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .issue-description {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-line;
            margin-bottom: 20px;
        }
        
        .issue-actions {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        
        .btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e8e8e8;
        }
        
        .error-message {
            color: #f44336;
            margin-top: 20px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        
        .success-message {
            color: #4CAF50;
            margin-top: 20px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
        
        /* Comments section */
        .comments-section h3 {
            margin-top: 30px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .comment {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .comment-author {
            font-weight: 500;
            color: #333;
        }
        
        .comment-time {
            color: #777;
            font-size: 12px;
        }
        
        .comment-content {
            white-space: pre-line;
        }
        
        .add-comment {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        
        .add-comment h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        
        .resolved-message {
            background-color: #e8f5e9;
            color: #388E3C;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Department Issues</h1>
        <div class="navbar-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <button id="logout-btn">Logout</button>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
        <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="issue-container">
            <div class="issue-header">
                <h2 class="issue-title"><?php echo htmlspecialchars($issue['title']); ?></h2>
                <span class="badge <?php echo $issue['status'] === 'open' ? 'badge-open' : 'badge-resolved'; ?>">
                    <?php echo ucfirst($issue['status']); ?>
                </span>
            </div>
            
            <div class="issue-meta">
                Created by <?php echo htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']); ?> (@<?php echo htmlspecialchars($issue['username']); ?>)
                on <?php echo date('F j, Y, g:i a', strtotime($issue['created_at'])); ?>
                <?php if ($issue['status'] === 'resolved'): ?>
                <br>Resolved on <?php echo date('F j, Y, g:i a', strtotime($issue['resolved_at'])); ?>
                <?php endif; ?>
            </div>
            
            <div class="issue-description">
                <?php echo nl2br(htmlspecialchars($issue['description'])); ?>
            </div>
            
            <div class="issue-actions">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                
                <?php if ($isCreator && $issue['status'] === 'open'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="resolve_issue" value="1">
                    <button type="submit" class="btn" onclick="return confirm('Are you sure you want to mark this issue as resolved?')">Mark as Resolved</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="comments-section">
            <h3>Comments (<?php echo count($comments); ?>)</h3>
            
            <?php if (empty($comments)): ?>
            <p>No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author"><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?> (@<?php echo htmlspecialchars($comment['username']); ?>)</span>
                        <span class="comment-time"><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($issue['status'] === 'open'): ?>
            <div class="add-comment">
                <h3>Add a Comment</h3>
                <form method="POST" class="comment-form">
                    <textarea name="comment" placeholder="Enter your comment" required></textarea>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            </div>
            <?php else: ?>
            <div class="resolved-message">
                This issue is marked as resolved. No more comments can be added.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', function() {
            fetch('logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'index.php';
            });
        });
    </script>
</body>
</html>