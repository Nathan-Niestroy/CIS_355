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

// Get all issues
$sql = "SELECT i.*, u.first_name, u.last_name, u.username, 
        (SELECT COUNT(*) FROM comments WHERE issue_id = i.id) AS comment_count 
        FROM issues i 
        JOIN users u ON i.created_by = u.id 
        ORDER BY i.status ASC, i.created_at DESC";
$result = $conn->query($sql);
$issues = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Issues Dashboard</title>
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
        
        .navbar-user button {
            background-color: var(--secondary);
            color: var(--background);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
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
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .issues-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .issue-card {
            background-color: var(--background);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s;
        }
        
        .issue-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .issue-card h2 {
            margin-top: 0;
            font-size: 18px;
            color: var(--text);
        }
        
        .issue-card p {
            color: var(--text);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
        }
        
        .issue-meta {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 10px;
        }
        
        .issue-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            align-items: center;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
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
        
        .comments-count {
            display: flex;
            align-items: center;
            color: var(--text);
            font-size: 14px;
        }
        
        .comments-count svg {
            margin-right: 5px;
        }
        
        /* No issues message */
        .no-issues {
            text-align: center;
            padding: 40px 0;
            color: var(--text);
        }
        
        /* Error message display */
        .error-message {
            color: var(--error-text);
            margin-top: 20px;
            padding: 10px;
            background-color: var(--error-bg);
            border-radius: 4px;
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
        <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="create-issue.php" class="btn">Create New Issue</a>
        </div>
        
        <?php if (empty($issues)): ?>
        <div class="no-issues">
            <h2>No issues found</h2>
            <p>Click on "Create New Issue" to add the first issue.</p>
        </div>
        <?php else: ?>
        <div class="issues-container">
            <?php foreach ($issues as $issue): ?>
            <div class="issue-card">
                <h2><?php echo htmlspecialchars($issue['title']); ?></h2>
                <div class="issue-meta">
                    By <?php echo htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']); ?> (@<?php echo htmlspecialchars($issue['username']); ?>)
                </div>
                <div class="issue-meta">
                    <?php echo date('F j, Y, g:i a', strtotime($issue['created_at'])); ?>
                </div>
                <p><?php echo htmlspecialchars(substr($issue['description'], 0, 150)) . (strlen($issue['description']) > 150 ? '...' : ''); ?></p>
                <div class="issue-footer">
                    <span class="badge <?php echo $issue['status'] === 'open' ? 'badge-open' : 'badge-resolved'; ?>">
                        <?php echo ucfirst($issue['status']); ?>
                    </span>
                    <div class="comments-count">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo $issue['comment_count']; ?> comments
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <a href="view-issue.php?id=<?php echo $issue['id']; ?>" class="btn" style="width: 100%; text-align: center; box-sizing: border-box;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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