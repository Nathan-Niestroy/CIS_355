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

// Check if user is admin
$isAdmin = false;
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND username = 'admin'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$adminResult = $stmt->get_result();
if ($adminResult->num_rows > 0) {
    $isAdmin = true;
}
$stmt->close();

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
        /* Clean Dashboard CSS - Reorganized */
        :root {
            /* Color Variables */
            --primary: #ff0000;
            --primary-dark: #cc0000;
            --secondary: #000000;
            --text: #000000;
            --background: #ffffff;
            --light-bg: #f5f5f5;
            --border: #dddddd;
            
            /* Status Colors */
            --open-badge-bg: #ffeeee;
            --open-badge-text: #cc0000;
            --resolved-badge-bg: #eeeeee;
            --resolved-badge-text: #444444;
            --admin-badge-bg: #ffeecc;
            --admin-badge-text: #cc6600;
            
            /* Utility Colors */
            --success: #444444;
            --info: #222222;
            --danger: #ff0000;
            --error-text: #ff0000;
            --error-bg: #ffeeee;
            
            /* Effects */
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            --transition-speed: 0.2s;
        }

        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text);
            line-height: 1.6;
            min-width: 320px;
            overscroll-behavior-y: contain;
        }

        /* Layout Components */
        .container {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        /* Navbar Styles */
        .navbar {
            background-color: var(--primary);
            color: var(--background);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Button Styles */
        .btn {
            background-color: var(--primary);
            color: var(--background);
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all var(--transition-speed);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        /* Logout Button */
        .navbar-user button {
            background-color: var(--secondary);
            color: var(--background);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color var(--transition-speed);
        }

        .navbar-user button:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        /* Action Buttons Section */
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .action-left, .action-right {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Filter Buttons */
        .filter-btn {
            background-color: var(--background);
            color: #666;
            border: 1px solid var(--border);
            padding: 0.5rem 0.875rem;
            border-radius: 0.375rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--transition-speed);
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
            box-shadow: 0 1px 3px rgba(204, 0, 0, 0.2);
        }

        .filter-btn.active:hover {
            background-color: var(--primary-dark);
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            gap: 0.375rem;
        }

        .toggle-btn {
            background-color: var(--background);
            color: #666;
            border: 1px solid var(--border);
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            transition: all var(--transition-speed);
        }

        .toggle-btn.active {
            background-color: var(--primary);
            color: var(--background);
            border-color: var(--primary);
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 1rem;
        }

        .badge-open {
            background-color: var(--open-badge-bg);
            color: var(--open-badge-text);
        }

        .badge-resolved {
            background-color: var(--resolved-badge-bg);
            color: var(--resolved-badge-text);
        }

        .admin-badge, .badge-admin {
            background-color: var(--admin-badge-bg);
            color: var(--admin-badge-text);
            font-size: 0.625rem;
            padding: 0.125rem 0.375rem;
            border-radius: 1rem;
            text-transform: uppercase;
        }

        .admin-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .badge-admin {
            margin-left: 0.375rem;
        }

        /* Card header styles - with improved spacing */
        .card-header {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.75rem;
        }

        .card-header .badge {
            margin-bottom: 0.75rem;
            align-self: flex-start;
        }

        .card-header h2 {
            font-size: 1.125rem;
            line-height: 1.4;
            color: var(--text);
            margin: 0;
        }

        /* Card View */
        .issues-container.card-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .card-view .issue-card {
            background-color: var(--background);
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: all var(--transition-speed);
            position: relative;
            height: 100%;
        }

        .card-view .issue-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-2px);
        }

        .card-view .open-issue {
            border-top: 3px solid var(--open-badge-text);
        }

        .card-view .resolved-issue {
            border-top: 3px solid var(--resolved-badge-text);
        }

        .card-view .issue-meta {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            clear: both;
        }

        .card-view .issue-card p {
            color: var(--text);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
            line-height: 1.5;
            flex-grow: 1;
        }

        .card-view .view-details {
            margin-top: auto;
        }

        .card-view .view-details .btn {
            width: 100%;
            text-align: center;
        }

        /* List View */
        .issues-container.list-view {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .list-view .issue-card {
            background-color: var(--background);
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
            gap: 1.5rem; /* Increased gap between items */
        }

        .list-view .issue-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-1px);
        }

        .list-view .issue-card.open-issue::before,
        .list-view .issue-card.resolved-issue::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0.25rem;
        }

        .list-view .issue-card.open-issue::before {
            background-color: var(--open-badge-text);
        }

        .list-view .issue-card.resolved-issue::before {
            background-color: var(--resolved-badge-text);
        }

        /* Badge in list view */
        .list-view .badge {
            min-width: 4.5rem;
            text-align: center;
            flex-shrink: 0;
            margin-bottom: 0;
            margin-right: 0;
            padding: 0.375rem 0.75rem; /* Slightly larger padding */
        }

        /* Title in list view */
        .list-view .issue-card h2 {
            margin: 0;
            flex: 1;
            min-width: 12.5rem;
            font-size: 1rem;
            font-weight: 600;
            padding-right: 1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Metadata in list view */
        .list-view .issue-meta {
            width: 16rem;
            font-size: 0.8125rem;
            color: #666;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        /* Comments count in list view */
        .list-view .comments-count {
            margin: 0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Hide description in list view */
        .list-view p {
            display: none;
        }

        /* View details button positioning */
        .list-view .view-details {
            margin-left: auto;
        }

        .list-view .view-details .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Common Components */
        .author-admin {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .comments-count {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            gap: 0.375rem;
        }

        .admin-actions-available {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            background-color: var(--admin-badge-bg);
            color: var(--admin-badge-text);
            font-size: 0.625rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.625rem;
            opacity: 0.9;
        }

        /* Message Styles */
        .no-issues {
            text-align: center;
            padding: 2.5rem;
            background-color: var(--background);
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
        }

        .no-issues h2 {
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        .error-message {
            color: var(--error-text);
            padding: 1rem;
            background-color: var(--error-bg);
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--card-shadow);
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem;
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
                justify-content: center;
            }
            
            .issues-container.card-view {
                grid-template-columns: 1fr;
            }
            
            .list-view .issue-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem;
            }
            
            .list-view .badge {
                margin-bottom: 0.5rem;
            }
            
            .list-view .issue-card h2,
            .list-view .issue-meta,
            .list-view .comments-count {
                width: 100%;
                margin: 0.25rem 0;
            }
            
            .list-view .view-details {
                width: 100%;
                margin-top: 0.5rem;
                margin-left: 0;
            }
            
            .list-view .view-details .btn {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        /* Touch Device Improvements */
        @media (pointer: coarse) {
            .btn, .filter-btn, .toggle-btn {
                min-height: 2.75rem;
                min-width: 2.75rem;
            }
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
        <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="actions">
            <div class="action-left">
                <a href="create-issue.php" class="btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create New Issue
                </a>
                
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
                <div class="card-header">
                    <?php if ($issue['status'] === 'open'): ?>
                    <span class="badge badge-open">Open</span>
                    <?php else: ?>
                    <span class="badge badge-resolved">Resolved</span>
                    <?php endif; ?>
                    
                    <h2><?php echo htmlspecialchars($issue['title']); ?></h2>
                </div>
                
                <div class="issue-meta">
                    <div class="author-admin">
                        By <?php echo htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']); ?>
                        <?php if ($issue['username'] === 'admin'): ?>
                        <span class="badge-admin">ADMIN</span>
                        <?php endif; ?>
                    </div>
                    <div>(@<?php echo htmlspecialchars($issue['username']); ?>)</div>
                    <div><?php echo date('F j, Y, g:i a', strtotime($issue['created_at'])); ?></div>
                </div>
                
                <p><?php echo htmlspecialchars(substr($issue['description'], 0, 150)) . (strlen($issue['description']) > 150 ? '...' : ''); ?></p>
                
                <div class="comments-count">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $issue['comment_count']; ?> comments
                </div>
                
                <?php if ($isAdmin && $_SESSION['user_id'] != $issue['created_by']): ?>
                <div class="admin-actions-available">Admin Actions</div>
                <?php endif; ?>
                
                <div class="view-details">
                    <a href="view-issue.php?id=<?php echo $issue['id']; ?>" class="btn">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($isAdmin): ?>
        <div class="no-issues" id="no-filtered-issues" style="display: none;">
            <h2>No matching issues found</h2>
            <p>No issues match the current filter. As an admin, you can view and manage all issues in the system.</p>
        </div>
        <?php else: ?>
        <div class="no-issues" id="no-filtered-issues" style="display: none;">
            <h2>No matching issues found</h2>
            <p>No issues match the current filter. Try a different filter or create a new issue.</p>
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
