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

// Check if the user is an admin
$isAdmin = false;
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND username = 'admin'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$adminResult = $stmt->get_result();
if ($adminResult->num_rows > 0) {
    $isAdmin = true;
}
$stmt->close();

// Handle issue resolution
if (isset($_POST['resolve_issue']) && $_POST['resolve_issue'] === '1') {
    if ($isAdmin) {
        // Admin can resolve any issue
        $stmt = $conn->prepare("UPDATE issues SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $issueId);
    } else {
        // Regular user can only resolve their own issues
        $stmt = $conn->prepare("UPDATE issues SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ii", $issueId, $_SESSION['user_id']);
    }
    
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

// Check if current user is the issue creator or admin
$isCreator = ($_SESSION['user_id'] == $issue['created_by']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Issue - <?php echo htmlspecialchars($issue['title']); ?></title>
    <style>
        :root {
            --primary: #ff0000; /* Red */
            --primary-dark: #cc0000; 
            --secondary: #000000; /* Black */
            --text: #000000; /* Black text */
            --background: #ffffff; /* White background */
            --light-bg: #f5f5f5; /* Light gray background */
            --border: #dddddd; /* Light border */
            --danger: #ff0000; /* Red for danger buttons */
            --success: #444444; /* Dark gray for success messages */
            --info: #222222; /* Very dark gray for info */
            --open-badge-bg: #ffeeee; /* Light red for open badges */
            --open-badge-text: #cc0000; /* Dark red for open badge text */
            --resolved-badge-bg: #eeeeee; /* Light gray for resolved badges */
            --resolved-badge-text: #444444; /* Dark gray for resolved badge text */
            --error-text: #ff0000; /* Red for error messages */
            --error-bg: #ffeeee; /* Light red for error backgrounds */
            --admin-badge-bg: #ffeecc; /* Light yellow for admin badge */
            --admin-badge-text: #cc6600; /* Orange for admin badge text */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background-color: var(--primary);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--background);
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
        
        .navbar-user .admin-badge {
            background-color: var(--admin-badge-bg);
            color: var(--admin-badge-text);
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .navbar-user button {
            background-color: var(--secondary);
            color: var(--background);
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
            background-color: var(--background);
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
            color: var(--text);
            font-size: 24px;
        }
        
        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .badge-open {
            background-color: var(--open-badge-bg);
            color: var(--open-badge-text);
        }
        
        .badge-resolved {
            background-color: var(--resolved-badge-bg);
            color: var(--resolved-badge-text);
        }
        
        .issue-meta {
            color: var(--text);
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .issue-description {
            background-color: var(--light-bg);
            padding: 15px;
            border-radius: 4px;
            white-space: pre-line;
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .issue-actions {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        
        .btn {
            background-color: var(--primary);
            color: var(--background);
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: var(--background);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--background);
        }
        
        .error-message {
            color: var(--error-text);
            margin-top: 20px;
            padding: 10px;
            background-color: var(--error-bg);
            border-radius: 4px;
        }
        
        .success-message {
            color: var(--success);
            margin-top: 20px;
            padding: 10px;
            background-color: var(--light-bg);
            border-radius: 4px;
        }
        
        /* Comments section */
        .comments-section h3 {
            margin-top: 30px;
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .comment {
            background-color: var(--background);
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
            color: var(--text);
        }
        
        .comment-time {
            color: var(--text);
            font-size: 12px;
        }
        
        .comment-content {
            white-space: pre-line;
            color: var(--text);
        }
        
        .add-comment {
            background-color: var(--background);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        
        .add-comment h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
            margin-bottom: 15px;
            color: var(--text);
        }
        
        .resolved-message {
            background-color: var(--resolved-badge-bg);
            color: var(--resolved-badge-text);
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        /* Admin styling */
        .admin-actions {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--admin-badge-bg);
            border-radius: 8px;
            border: 1px solid var(--admin-badge-text);
        }
        
        .admin-actions h3 {
            color: var(--admin-badge-text);
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .admin-actions .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Department Issues</h1>
        <div class="navbar-user">
            <?php if ($isAdmin): ?>
            <span class="admin-badge">ADMIN</span>
            <?php endif; ?>
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
                
                <?php if ($isCreator || $isAdmin): ?>
                <div>
                    <?php if ($issue['status'] === 'open'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="resolve_issue" value="1">
                        <button type="submit" class="btn" onclick="return confirm('Are you sure you want to mark this issue as resolved?')">Mark as Resolved</button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="delete-issue.php?id=<?php echo $issueId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this issue? This action cannot be undone.')">Delete Issue</a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($isAdmin && !$isCreator): ?>
            <div class="admin-actions">
                <h3>Admin Actions</h3>
                <p>You are viewing this issue as an administrator. You can manage this issue even though you didn't create it.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="comments-section">
            <h3>Comments (<?php echo count($comments); ?>)</h3>
            
            <?php if (empty($comments)): ?>
            <p>No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author">
                            <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?> 
                            (@<?php echo htmlspecialchars($comment['username']); ?>)
                            <?php if ($comment['username'] === 'admin'): ?>
                            <span class="admin-badge">ADMIN</span>
                            <?php endif; ?>
                        </span>
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