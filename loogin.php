<?php
session_start();

// Database configuration
$host = '127.0.0.1';
$dbname = 'web_project';
$db_username = 'root'; // Change if needed
$db_password = ''; // Change if needed

// Initialize variables
$error = '';
$success = '';
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $accountType = $_POST['account_type'] ?? 'user';
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Determine which table to query based on account type
        if ($accountType === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM boss WHERE email = :input OR username = :input");
            $stmt->execute([':input' => $email]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :input OR first_name = :input");
            $stmt->execute([':input' => $email]);
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Debug: Check what we found
            error_log("Found user: " . print_r($user, true));
            error_log("Account type: " . $accountType);
            error_log("Password from form: " . $password);
            error_log("Password from DB: " . $user['password']);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                error_log("Password verification SUCCESS");
                // Check if account is active
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    // Login successful - set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['account_type'] = $accountType;
                    
                    if ($accountType === 'user') {
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['is_admin'] = false;
                        // Update last login time
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                        $updateStmt->execute([':id' => $user['id']]);
                    } else {
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['is_admin'] = true;
                        // Update last login time for admin
                        $updateStmt = $pdo->prepare("UPDATE boss SET updated_at = NOW() WHERE id = :id");
                        $updateStmt->execute([':id' => $user['id']]);
                    }
                    
                    
                    if ($accountType === 'admin') {
                        header('Location: admin-page.php');
                        exit();
                    } else {
                        header('Location: Secoand-Page.php');
                        exit();
                    }
                }
            } else {
                $error = 'Invalid email or password';
                
                // For users, update failed login attempts
                if ($accountType === 'user') {
                    $failedAttempts = ($user['failed_attempts'] ?? 0) + 1;
                    $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = :attempts, last_failed_attempt = NOW() WHERE id = :id");
                    $updateStmt->execute([
                        ':attempts' => $failedAttempts,
                        ':id' => $user['id']
                    ]);
                }
            }
        } else {
            $error = 'Invalid email or password';
        }
    } catch(PDOException $e) {
        $error = 'Login failed. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games Store - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1f35 0%, #0f1323 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }

        .login-container {
            background: #1e2538;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            padding: 30px;
            text-align: center;
            background: #252d42;
        }

        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: #a0aec0;
            font-size: 14px;
        }

        .login-form {
            padding: 30px;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            <?php if (!empty($error)): ?>display: block;<?php else: ?>display: none;<?php endif; ?>
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            color: #86efac;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e2e8f0;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            background: #2d3748;
            border: 1px solid #4a5568;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            outline: none;
        }

        input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3);
        }

        .account-type {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .account-btn {
            flex: 1;
            padding: 12px;
            background: #2d3748;
            border: 1px solid #4a5568;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .account-btn.active {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: #6366f1;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .footer {
            padding: 20px;
            text-align: center;
            border-top: 1px solid #2d3748;
            font-size: 12px;
            color: #718096;
        }

        .footer a {
            color: #a0aec0;
            text-decoration: none;
            margin: 0 10px;
        }

        .footer a:hover {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🎮</div>
            <h1>Games Store</h1>
            <p class="subtitle">Your Ultimate Games Destination</p>
        </div>

        <form class="login-form" id="loginForm" method="POST">
            <div class="alert alert-error" id="errorAlert">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="alert alert-success" id="successAlert">
                <?php echo htmlspecialchars($success); ?>
            </div>

            <div class="account-type">
                <div class="account-btn active" data-type="user">User</div>
                <div class="account-btn" data-type="admin">Admin</div>
                <input type="hidden" name="account_type" id="accountType" value="user">
            </div>

            <div class="form-group">
                <label for="email">Email or Name</label>
                <input type="text" id="email" name="email" placeholder="Enter your email or name" required 
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span id="btnText">Sign In</span>
            </button>
        </form>

        <div class="footer">
            <a href="#">Support</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <p>© 2025 Games Store. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const errorAlert = document.getElementById('errorAlert');
            const successAlert = document.getElementById('successAlert');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const accountButtons = document.querySelectorAll('.account-btn');
            const accountTypeInput = document.getElementById('accountType');
            
            // Show alerts if they have content
            if (errorAlert.textContent.trim() !== '') {
                errorAlert.style.display = 'block';
            }
            if (successAlert.textContent.trim() !== '') {
                successAlert.style.display = 'block';
            }
            
            // Account type selection
            accountButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    accountButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    accountTypeInput.value = btn.getAttribute('data-type');
                });
            });
            
            // Form submission
            form.addEventListener('submit', function() {
                // Show loading state
                loginBtn.disabled = true;
                btnText.innerHTML = '<span class="loading"></span> Signing In...';
            });
        });
    </script>
</body>
</html>