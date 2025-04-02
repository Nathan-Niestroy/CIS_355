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

// Set default view or get from localStorage
$defaultView = 'card';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#ff0000">
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
        
        * {
            box-sizing: border-box;
            tap-highlight-color: transparent;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            min-width: 320px;
            overscroll-behavior-y: contain;
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
            font-weight: bold;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .navbar-user span {
            margin-right: 15px;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .navbar-user button {
            background-color: black;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .action-left, .action-right {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-btn {
            background-color: var(--background);
            color: #666;
            border: 1px solid var(--border);
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .filter-btn:hover {
            border-color: #bbb;
            color: #444;
            background-color: #f9f9f9;
        }
        
        .filter-btn.active {
            background-color: var(--primary);
            color: var(--background);
            border-color: var(--primary);
            box-shadow: 0 2px 5px rgba(204, 0, 0, 0.2);
        }
        
        .filter-btn.active:hover {
            background-color: var(--primary-dark);
        }
        
        .btn {
            background-color: var(--primary);
            color: var(--background);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(204, 0, 0, 0.2);
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(204, 0, 0, 0.3);
        }
        
        /* Toggle View Buttons */
        .view-toggle {
            display: flex;
            gap: 5px;
        }
        
        .toggle-btn {
            background-color: var(--background);
            color: #666;
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .toggle-btn.active {
            background-color: var(--primary);
            color: var(--background);
            border-color: var(--primary);
        }
        
        /* Card View Styles */
        .issues-container.card-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card-view .issue-card {
            background-color: var(--background);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .card-view .open-issue .badge-open {
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .card-view .resolved-issue .badge-resolved {
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .card-view .issue-card h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 18px;
            color: var(--text);
            line-height: 1.3;
        }
        
        .card-view .issue-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .card-view .issue-card p {
            color: var(--text);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
            line-height: 1.5;
        }
        
        .card-view .issue-footer {
            display: none;
        }
        
        .card-view .comments-count {
            margin-bottom: 15px;
        }
        
        .card-view .list-view-badge {
            display: none;
        }
        
        .card-view .view-details {
            margin-top: auto;
        }
        
        .card-view .view-details .btn {
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            display: inline-block;
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
            color: #666;
            font-size: 14px;
        }
        
        .comments-count svg {
            margin-right: 5px;
        }
        
        /* List View Styles */
        .issues-container.list-view {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .list-view .issue-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background-color: var(--background);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .list-view .badge {
            min-width: 70px;
            text-align: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .list-view .issue-card h2 {
            margin: 0;
            flex: 1;
            font-size: 16px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .list-view .issue-meta {
            width: 220px;
            margin: 0 15px;
            font-size: 13px;
            line-height: 1.4;
            flex-shrink: 0;
        }
        
        .list-view .comments-count {
            margin: 0 15px 0 0;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .list-view p {
            display: none;
        }
        
        .list-view .view-details .btn {
            padding: 6px 12px;
            font-size: 14px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .list-view .issue-card {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border-radius: 8px;
            background-color: var(--background);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .list-view .issue-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .list-view .issue-card.open-issue::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--open-badge-text);
        }
        
        .list-view .issue-card.resolved-issue::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--resolved-badge-text);
        }
        
        .list-view .list-view-badge {
            min-width: 70px;
            text-align: center;
            margin-right: 18px;
            flex-shrink: 0;
        }
        
        .list-view .issue-card h2 {
            margin: 0;
            flex: 1;
            min-width: 200px;
            font-size: 16px;
            font-weight: 600;
            padding-right: 15px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .list-view .issue-meta {
            width: 280px;
            margin: 0 25px 0 0;
            font-size: 13px;
            line-height: 1.4;
            color: #666;
            flex-shrink: 0;
        }
        
        .list-view .comments-count {
            margin: 0 25px 0 0;
            white-space: nowrap;
            color: #666;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        
        .list-view .comments-count svg {
            margin-right: 6px;
        }
        
        .list-view p {
            display: none;
        }
        
        .list-view .view-details .btn {
            padding: 8px 16px;
            font-size: 14px;
            white-space: nowrap;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .list-view .view-details .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }
        
        .list-view .issue-footer {
            display: none;
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

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 15px;
            }
            
            .navbar-user {
                width: 100%;
                justify-content: space-between;
            }
            
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-left, .action-right {
                flex-direction: column;
                width: 100%;
            }
            
            .action-left .btn, 
            .action-left .filter-btn,
            .action-right .toggle-btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
            
            .card-view.issues-container {
                grid-template-columns: 1fr;
            }
            
            .list-view .issue-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .list-view .comments-count {
                margin-left: 0;
                width: 100%;
            }

            .navbar h1 {
                font-size: 20px;
            }
        }

        /* Mobile-specific touch improvements */
        @media (pointer: coarse) {
            .btn, .filter-btn, .toggle-btn {
                min-height: 44px;
                min-width: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
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
            <div class="action-left">
                <a href="create-issue.php" class="btn">Create New Issue</a>
                
                <button id="filter-all-btn" class="filter-btn active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                    </svg>
                    All Issues
                </button>
                
                <button id="filter-open-btn" class="filter-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                    </svg>
                    Open
                </button>
                
                <button id="filter-resolved-btn" class="filter-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    Resolved
                </button>
            </div>
            
            <div class="action-right">
                <div class="view-toggle">
                    <button id="card-view-btn" class="toggle-btn active">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                            <rect x="14" y="14" width="7" height="7" />
                        </svg>
                        Cards
                    </button>
                    <button id="list-view-btn" class="toggle-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                        List
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (empty($issues)): ?>
        <div class="no-issues">
            <h2>No issues found</h2>
            <p>Click on "Create New Issue" to add the first issue.</p>
        </div>
        <?php else: ?>
        <div id="issues-container" class="issues-container card-view">
            <?php foreach ($issues as $issue): ?>
            <div class="issue-card <?php echo $issue['status'] === 'open' ? 'open-issue' : 'resolved-issue'; ?>">
                <?php if ($issue['status'] === 'open'): ?>
                <span class="badge badge-open">Open</span>
                <?php else: ?>
                <span class="badge badge-resolved">Resolved</span>
                <?php endif; ?>
                
                <h2><?php echo htmlspecialchars($issue['title']); ?></h2>
                
                <div class="issue-meta">
                    By <?php echo htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']); ?> (@<?php echo htmlspecialchars($issue['username']); ?>)
                    <br>
                    <?php echo date('F j, Y, g:i a', strtotime($issue['created_at'])); ?>
                </div>
                
                <p><?php echo htmlspecialchars(substr($issue['description'], 0, 150)) . (strlen($issue['description']) > 150 ? '...' : ''); ?></p>
                
                <div class="comments-count">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $issue['comment_count']; ?> comments
                </div>
                
                <div class="view-details">
                    <a href="view-issue.php?id=<?php echo $issue['id']; ?>" class="btn">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Get elements
        const cardViewBtn = document.getElementById('card-view-btn');
        const listViewBtn = document.getElementById('list-view-btn');
        const issuesContainer = document.getElementById('issues-container');
        const filterAllBtn = document.getElementById('filter-all-btn');
        const filterOpenBtn = document.getElementById('filter-open-btn');
        const filterResolvedBtn = document.getElementById('filter-resolved-btn');
        const issueCards = document.querySelectorAll('.issue-card');
        
        // Function to set view mode
        function setViewMode(mode) {
            // Save preference to localStorage
            localStorage.setItem('viewMode', mode);
            
            // Update button states
            if (mode === 'card') {
                cardViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                issuesContainer.classList.add('card-view');
                issuesContainer.classList.remove('list-view');
            } else {
                listViewBtn.classList.add('active');
                cardViewBtn.classList.remove('active');
                issuesContainer.classList.add('list-view');
                issuesContainer.classList.remove('card-view');
            }
        }
        
        // Function to filter issues
        function filterIssues(filter) {
            // Save filter preference to localStorage
            localStorage.setItem('issueFilter', filter);
            
            // Update button states
            filterAllBtn.classList.remove('active');
            filterOpenBtn.classList.remove('active');
            filterResolvedBtn.classList.remove('active');
            
            if (filter === 'all') {
                filterAllBtn.classList.add('active');
                issueCards.forEach(card => {
                    card.style.display = '';
                });
            } else if (filter === 'open') {
                filterOpenBtn.classList.add('active');
                issueCards.forEach(card => {
                    if (card.classList.contains('open-issue')) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            } else if (filter === 'resolved') {
                filterResolvedBtn.classList.add('active');
                issueCards.forEach(card => {
                    if (card.classList.contains('resolved-issue')) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            // Check if no issues are visible after filtering
            let visibleIssues = 0;
            issueCards.forEach(card => {
                if (card.style.display !== 'none') {
                    visibleIssues++;
                }
            });
            
            // Show or hide "no issues" message
            const noFilteredIssues = document.getElementById('no-filtered-issues');
            if (noFilteredIssues) {
                if (visibleIssues === 0) {
                    noFilteredIssues.style.display = 'block';
                    
                    // Update message based on filter
                    if (filter === 'open') {
                        noFilteredIssues.innerHTML = '<h2>No open issues found</h2><p>All issues have been resolved or you can create a new issue.</p>';
                    } else if (filter === 'resolved') {
                        noFilteredIssues.innerHTML = '<h2>No resolved issues found</h2><p>There are no resolved issues yet.</p>';
                    } else {
                        noFilteredIssues.innerHTML = '<h2>No issues found</h2><p>Click on "Create New Issue" to add the first issue.</p>';
                    }
                } else {
                    noFilteredIssues.style.display = 'none';
                }
            }
        }
        
        // Load saved preferences
        document.addEventListener('DOMContentLoaded', function() {
            const savedViewMode = localStorage.getItem('viewMode') || 'card';
            setViewMode(savedViewMode);
            
            const savedFilter = localStorage.getItem('issueFilter') || 'all';
            filterIssues(savedFilter);
        });
        
        // Add event listeners for view toggle
        cardViewBtn.addEventListener('click', function() {
            setViewMode('card');
        });
        
        listViewBtn.addEventListener('click', function() {
            setViewMode('list');
        });
        
        // Add event listeners for filters
        filterAllBtn.addEventListener('click', function() {
            filterIssues('all');
        });
        
        filterOpenBtn.addEventListener('click', function() {
            filterIssues('open');
        });
        
        filterResolvedBtn.addEventListener('click', function() {
            filterIssues('resolved');
        });
        
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