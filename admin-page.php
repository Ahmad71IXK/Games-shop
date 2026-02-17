<?php
session_start();

// Handle logout request
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Redirect to second-page.php
    header("Location: secoand-page.php");
    exit();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .greeting {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
        }

        .greeting h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .greeting p {
            font-size: 18px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
        }

        .button-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            width: 100%;
        }

        .logout-row {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            text-align: center;
        }

        button {
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            min-width: 160px;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        button:active {
            transform: translateY(-1px);
        }

        .add-btn {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
        }

        .add-btn:hover {
            box-shadow: 0 8px 25px rgba(86, 171, 47, 0.4);
        }

        .refill-btn {
            background: linear-gradient(45deg, #ff6b6b, #ffa8a8);
        }

        .refill-btn:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        }

        .delete-btn {
            background: linear-gradient(45deg, #ff0000, #ff6b6b);
        }

        .delete-btn:hover {
            box-shadow: 0 8px 25px rgba(255, 0, 0, 0.4);
        }

        .logout-btn {
            background: linear-gradient(45deg, #6c757d, #adb5bd);
            width: 200px;
            margin: 0 auto;
        }

        .logout-btn:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .greeting {
                padding: 30px 20px;
            }
            
            .greeting h1 {
                font-size: 2em;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            button {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="greeting">
            <h1>Welcome Boss!</h1>
            <p>Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
            <p>Here you can add new games to sell or refill existing game inventory.</p>
            <p>Choose an action below to get started:</p>
        </div>
        
        <div class="button-section">
            <div class="action-buttons">
                <button class="add-btn" onclick="addGame()">🎮 Add Game</button>
                <button class="refill-btn" onclick="refillGame()">📦 Refill Stock</button>
                <button class="delete-btn" onclick="deleteGames()">🗑️ Delete Games</button>
            </div>
            
            <div class="logout-row">
                <button class="logout-btn" onclick="logout()">🚪 Logout</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for logout -->
    <form id="logoutForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="logout">
    </form>

    <script>
        function addGame() {
            // Redirect to add game page
            window.location.href = 'add-game-page.php';
        }

        function refillGame() {
            // Redirect to refill page
            window.location.href = 'refil-page.php';
        }

        function deleteGames() {
            // Redirect to delete games page
            window.location.href = 'aaa.php';
        }

        function logout() {
            // Confirm logout
            if (confirm('Are you sure you want to logout?')) {
                // Submit the hidden form to handle logout
                document.getElementById('logoutForm').submit();
            }
        }

        // Add ripple effect to buttons
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>
</body>
</html>