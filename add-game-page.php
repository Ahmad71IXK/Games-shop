<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'web_project');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game_form'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // Get form data
        $platform = $_POST['platform'] ?? '';
        $gameName = trim($_POST['gameName'] ?? '');
        $gameDescription = trim($_POST['gameDescription'] ?? '');
        $gamePrice = floatval($_POST['gamePrice'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $emoji = trim($_POST['emoji'] ?? '🎮');
        
        // Validate required fields
        if (empty($gameName) || empty($gameDescription) || $gamePrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Game name, description, and price are required']);
            exit;
        }
        
        if (!in_array($platform, ['switch', 'ps5', 'xbox', 'retro'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid platform selected']);
            exit;
        }
        
        // Determine table and platform name
        $tableName = '';
        $platformName = '';
        
        switch ($platform) {
            case 'switch':
                $tableName = 'nintendo_games';
                $platformName = 'Nintendo Switch';
                break;
            case 'ps5':
                $tableName = 'ps5_games';
                $platformName = 'PlayStation 5';
                break;
            case 'xbox':
                $tableName = 'xbox_games';
                $platformName = 'Xbox Series X/S';
                break;
            case 'retro':
                $tableName = 'retro_games';
                $platformName = 'Retro Games';
                break;
        }
        
        // Check if game already exists
        $stmt = $pdo->prepare("SELECT id FROM {$tableName} WHERE LOWER(game_name) = LOWER(?)");
        $stmt->execute([$gameName]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => "Game '{$gameName}' already exists in {$platformName} database"]);
            exit;
        }
        
        // Prepare insert query based on platform
        if ($platform === 'retro') {
            // Retro games table has additional platform field
            $sql = "INSERT INTO {$tableName} (game_name, description, price, quantity, emoji, platform, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params = [$gameName, $gameDescription, $gamePrice, $quantity, $emoji, 'Multiple'];
        } else {
            // Other platforms (PS5, Switch, Xbox)
            if ($platform === 'switch' || $platform === 'xbox') {
                // Nintendo and Xbox games have updated_at column
                $sql = "INSERT INTO {$tableName} (game_name, description, price, quantity, emoji, updated_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
            } else {
                // PS5 games don't have updated_at column
                $sql = "INSERT INTO {$tableName} (game_name, description, price, quantity, emoji) 
                       VALUES (?, ?, ?, ?, ?)";
            }
            $params = [$gameName, $gameDescription, $gamePrice, $quantity, $emoji];
        }
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $message = "✅ Success! Game '{$gameName}' has been added to {$platformName}!";
            $message .= " 💰 Price: $" . number_format($gamePrice, 2);
            $message .= " 📦 Quantity: {$quantity}";
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add game to database']);
        }
        exit;
        
    } catch (Exception $e) {
        error_log("Error adding game: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while adding the game']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .back-navigation {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1000;
}

.back-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    color: #333;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.back-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    background: rgba(255, 255, 255, 1);
}

.back-button .arrow {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.back-button:hover .arrow {
    transform: translateX(-3px);
}

@media (max-width: 600px) {
    .back-navigation {
        top: 15px;
        left: 15px;
    }

    .back-button {
        padding: 10px 15px;
        font-size: 12px;
    }

    .back-button .arrow {
        font-size: 16px;
    }
}

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }
            50% {
                box-shadow: 0 8px 35px rgba(102, 126, 234, 0.5);
            }
            100% {
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }
        }

        .profile-info h2 {
            color: #333;
            margin-bottom: 5px;
            font-size: 24px;
        }

        .profile-info p {
            color: #666;
            font-size: 16px;
        }

        .form-section h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-size: 20px;
        }

        .tabs-container {
            margin-bottom: 25px;
        }

        .tabs {
            display: flex;
            border-radius: 12px;
            background: #f8f9fa;
            padding: 5px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .tab-button {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: 100px;
        }

        .tab-button:hover {
            color: #333;
            background: rgba(102, 126, 234, 0.1);
            transform: none;
            box-shadow: none;
        }

        .tab-button.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .platform-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .platform-info h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .platform-info p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="file"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .emoji-input {
            width: 80px !important;
            text-align: center;
            font-size: 20px;
        }

        .price-quantity-row {
            display: grid;
            grid-template-columns: 2fr 1fr 80px;
            gap: 15px;
            align-items: end;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 600px) {
            .price-quantity-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 10px;
                padding: 20px;
            }
            
            .tabs {
                padding: 3px;
            }
            
            .tab-button {
                padding: 10px 12px;
                font-size: 12px;
                min-width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Navigation -->
<div class="back-navigation">
    <a href="admin-page.php" class="back-button">
        <span class="arrow">←</span>
        Back to Admin
    </a>
</div>
        <div class="profile-section">
            <div class="profile-photo">
                👨‍💼
            </div>
            <div class="profile-info">
                <h2>Game Store Admin</h2>
                <p>Add new games to your inventory</p>
            </div>
        </div>

        <div class="alert" id="alertBox"></div>

        <div class="tabs-container">
            <div class="tabs">
                <button type="button" class="tab-button active" data-platform="ps5">🎮 PS5</button>
                <button type="button" class="tab-button" data-platform="switch">🕹️ Switch</button>
                <button type="button" class="tab-button" data-platform="xbox">🎯 Xbox</button>
                <button type="button" class="tab-button" data-platform="retro">👾 Retro</button>
            </div>

            <div class="platform-info" id="platformInfo">
                <h3>PlayStation 5</h3>
                <p>Add new PS5 games - latest releases and exclusives</p>
            </div>
        </div>

        <div class="form-section">
            <h3>📝 New Game Details</h3>
            
            <form id="addGameForm" method="POST">
                <input type="hidden" name="add_game_form" value="1">
                <input type="hidden" id="platform" name="platform" value="ps5">

                <div class="form-group">
                    <label for="gameName">🎮 Game Name:</label>
                    <input type="text" id="gameName" name="gameName" placeholder="Enter new game name" required />
                </div>

                <div class="form-group">
                    <label for="gameDescription">📄 Game Description:</label>
                    <input type="text" id="gameDescription" name="gameDescription" placeholder="Enter game description" required />
                </div>

                <div class="price-quantity-row">
                    <div class="form-group">
                        <label for="gamePrice">💰 Game Price ($):</label>
                        <input type="number" id="gamePrice" name="gamePrice" placeholder="Enter game price" step="0.01" min="0.01" required />
                    </div>

                    <div class="form-group">
                        <label for="quantity">📦 Quantity:</label>
                        <input type="number" id="quantity" name="quantity" placeholder="Stock" min="1" value="1" required />
                    </div>

                    <div class="form-group">
                        <label for="emoji">😊 Emoji:</label>
                        <input type="text" id="emoji" name="emoji" class="emoji-input" placeholder="🎮" maxlength="2" />
                    </div>
                </div>

                <button type="submit">
                    ➕ Add Game to Store
                </button>
            </form>
        </div>
    </div>

    <script>
        let currentPlatform = 'ps5';

        // Platform information
        const platformData = {
            ps5: {
                title: 'PlayStation 5',
                description: 'Add new PS5 games - latest releases and exclusives',
                placeholder: 'e.g., Spider-Man 3, Horizon Zero Dawn 2'
            },
            switch: {
                title: 'Nintendo Switch',
                description: 'Add new Switch games - portable and console gaming',
                placeholder: 'e.g., Super Mario Wonder, Zelda: Tears of the Kingdom'
            },
            xbox: {
                title: 'Xbox Series X/S',
                description: 'Add new Xbox games - Game Pass and exclusives',
                placeholder: 'e.g., Starfield, Halo Infinite DLC'
            },
            retro: {
                title: 'Retro Games',
                description: 'Add classic games - vintage consoles and timeless titles',
                placeholder: 'e.g., Super Mario Bros. 3, Sonic the Hedgehog'
            }
        };

        // Show alert message
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert ${type}`;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 5000);
        }

        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const platform = this.dataset.platform;
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Update platform info
                const platformInfo = document.getElementById('platformInfo');
                const data = platformData[platform];
                platformInfo.innerHTML = `
                    <h3>${data.title}</h3>
                    <p>${data.description}</p>
                `;
                
                // Update game name placeholder
                document.getElementById('gameName').placeholder = data.placeholder;
                
                // Update current platform
                currentPlatform = platform;
                document.getElementById('platform').value = platform;
            });
        });

        // Form submission
        document.getElementById('addGameForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Reset form
                    this.reset();
                    document.getElementById('platform').value = currentPlatform;
                    document.getElementById('quantity').value = '1';
                    document.getElementById('emoji').value = '';
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        // Add visual feedback for form interactions
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-suggest emojis based on platform
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const platform = this.dataset.platform;
                const emojiInput = document.getElementById('emoji');
                
                const platformEmojis = {
                    ps5: '🎮',
                    switch: '🕹️',
                    xbox: '🎯',
                    retro: '👾'
                };
                
                emojiInput.placeholder = platformEmojis[platform];
            });
        });

        // Set initial emoji placeholder
        document.getElementById('emoji').placeholder = '🎮';
    </script>
</body>
</html>