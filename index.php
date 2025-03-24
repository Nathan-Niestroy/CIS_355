<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Issues Tracker</title>
    <style>
        :root {
            --primary: #ff0000; /* Red */
            --primary-dark: #cc0000; 
            --secondary: #000000; /* Black */
            --text: #000000; /* Black text */
            --background: #ffffff; /* White background */
            --light-bg: #f5f5f5; /* Light gray background */
            --border: #dddddd; /* Light border */
            --error-text: #ff0000; /* Red for error messages */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            background-color: var(--background);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 25px;
        }
        
        h1 {
            text-align: center;
            color: var(--text);
            margin-bottom: 20px;
        }
        
        .app-title {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text);
        }
        
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            color: var(--text);
        }
        
        button {
            background-color: var(--primary);
            color: var(--background);
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: var(--primary-dark);
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: var(--text);
        }
        
        .toggle-form a {
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--error-text);
            margin-top: 5px;
            font-size: 14px;
            display: none;
        }
        
        .success-message {
            color: var(--secondary);
            margin-top: 5px;
            font-size: 14px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="app-title">Department Issues Tracker</div>
        
        <!-- Login Form -->
        <div id="login-form">
            <h1>Login</h1>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" placeholder="Enter your username">
                <div id="username-error" class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Enter your password">
                <div id="password-error" class="error-message"></div>
            </div>
            <div id="login-error" class="error-message"></div>
            <button id="login-button">Login</button>
            <div class="toggle-form">
                Don't have an account? <a id="show-register">Join Now</a>
            </div>
        </div>
        
        <!-- Registration Form -->
        <div id="register-form" style="display: none;">
            <h1>Register</h1>
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" placeholder="Enter your first name">
                <div id="first-name-error" class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" placeholder="Enter your last name">
                <div id="last-name-error" class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" placeholder="Enter your email">
                <div id="email-error" class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="reg-password">Password</label>
                <input type="password" id="reg-password" placeholder="Create a password">
                <div id="reg-password-error" class="error-message"></div>
            </div>
            <div id="register-error" class="error-message"></div>
            <div id="register-success" class="success-message"></div>
            <button id="register-button">Register</button>
            <div class="toggle-form">
                Already have an account? <a id="show-login">Login</a>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        
        // Show/Hide Forms
        document.getElementById('show-register').addEventListener('click', () => {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            clearErrors();
        });
        
        document.getElementById('show-login').addEventListener('click', () => {
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
            clearErrors();
        });
        
        // Login Handling
        document.getElementById('login-button').addEventListener('click', login);
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                login();
            }
        });
        
        function login() {
            clearErrors();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            // Basic validation
            let hasError = false;
            
            if (!username) {
                document.getElementById('username-error').textContent = 'Username is required';
                document.getElementById('username-error').style.display = 'block';
                hasError = true;
            }
            
            if (!password) {
                document.getElementById('password-error').textContent = 'Password is required';
                document.getElementById('password-error').style.display = 'block';
                hasError = true;
            }
            
            if (hasError) return;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            
            // Disable login button
            const loginButton = document.getElementById('login-button');
            loginButton.disabled = true;
            loginButton.textContent = 'Logging in...';
            
            // Send login request
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Login successful - redirect to dashboard
                    window.location.href = 'dashboard.php';
                } else {
                    // Login failed
                    document.getElementById('login-error').textContent = data.message;
                    document.getElementById('login-error').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('login-error').textContent = 'An error occurred. Please try again.';
                document.getElementById('login-error').style.display = 'block';
                console.error('Login error:', error);
            })
            .finally(() => {
                // Re-enable login button
                loginButton.disabled = false;
                loginButton.textContent = 'Login';
            });
        }
        
        // Registration Handling
        document.getElementById('register-button').addEventListener('click', register);
        
        function register() {
            clearErrors();
            
            const firstName = document.getElementById('first-name').value.trim();
            const lastName = document.getElementById('last-name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('reg-password').value;
            
            // Basic validation
            let hasError = false;
            
            if (!firstName) {
                document.getElementById('first-name-error').textContent = 'First name is required';
                document.getElementById('first-name-error').style.display = 'block';
                hasError = true;
            }
            
            if (!lastName) {
                document.getElementById('last-name-error').textContent = 'Last name is required';
                document.getElementById('last-name-error').style.display = 'block';
                hasError = true;
            }
            
            if (!email) {
                document.getElementById('email-error').textContent = 'Email is required';
                document.getElementById('email-error').style.display = 'block';
                hasError = true;
            } else if (!isValidEmail(email)) {
                document.getElementById('email-error').textContent = 'Please enter a valid email';
                document.getElementById('email-error').style.display = 'block';
                hasError = true;
            }
            
            if (!password) {
                document.getElementById('reg-password-error').textContent = 'Password is required';
                document.getElementById('reg-password-error').style.display = 'block';
                hasError = true;
            } else if (password.length < 6) {
                document.getElementById('reg-password-error').textContent = 'Password must be at least 6 characters';
                document.getElementById('reg-password-error').style.display = 'block';
                hasError = true;
            }
            
            if (hasError) return;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);
            formData.append('email', email);
            formData.append('password', password);
            
            // Disable register button
            const registerButton = document.getElementById('register-button');
            registerButton.disabled = true;
            registerButton.textContent = 'Registering...';
            
            // Send registration request
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Registration successful
                    document.getElementById('register-success').textContent = `Registration successful! Your username is: ${data.username}`;
                    document.getElementById('register-success').style.display = 'block';
                    
                    // Clear registration form
                    document.getElementById('first-name').value = '';
                    document.getElementById('last-name').value = '';
                    document.getElementById('email').value = '';
                    document.getElementById('reg-password').value = '';
                    
                    // Automatically switch to login form after a delay
                    setTimeout(() => {
                        document.getElementById('show-login').click();
                    }, 3000);
                } else {
                    // Registration failed
                    document.getElementById('register-error').textContent = data.message;
                    document.getElementById('register-error').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('register-error').textContent = 'An error occurred. Please try again.';
                document.getElementById('register-error').style.display = 'block';
                console.error('Registration error:', error);
            })
            .finally(() => {
                // Re-enable register button
                registerButton.disabled = false;
                registerButton.textContent = 'Register';
            });
        }
        
        // Helper functions
        function clearErrors() {
            const errorElements = document.querySelectorAll('.error-message, .success-message');
            errorElements.forEach(element => {
                element.textContent = '';
                element.style.display = 'none';
            });
        }
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>