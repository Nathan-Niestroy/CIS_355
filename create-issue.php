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

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $userId = $_SESSION['user_id'];
    
    // Validate form data
    if (empty($title) || empty($description)) {
        $error = 'Title and description are required';
    } else {
        // Insert issue into database
        $stmt = $conn->prepare("INSERT INTO issues (title, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $userId);
        
        if ($stmt->execute()) {
            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Error creating issue: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Issue</title>
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
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--background);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            margin-top: 0;
            color: var(--text);
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            font-family: inherit;
            color: var(--text);
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            background-color: var(--primary);
            color: var(--background);
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
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
        <h2 class="page-title">Create New Issue</h2>
        
        <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Issue Title</label>
                <input type="text" id="title" name="title" placeholder="Enter a descriptive title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe the issue in detail" required></textarea>
            </div>
            
            <div class="btn-container">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn">Create Issue</button>
            </div>
        </form>
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