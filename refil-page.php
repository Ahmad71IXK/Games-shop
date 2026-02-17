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

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_switch_games') {
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, game_name, price, quantity, emoji FROM nintendo_games ORDER BY game_name ASC");
            $stmt->execute();
            $games = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'games' => $games]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching games']);
            exit;
        }
    }
    
    if ($_GET['action'] === 'get_ps5_games') {
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, game_name, price, quantity, emoji FROM ps5_games ORDER BY game_name ASC");
            $stmt->execute();
            $games = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'games' => $games]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching games']);
            exit;
        }
    }

    // Add Xbox games endpoint
    if ($_GET['action'] === 'get_xbox_games') {
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, game_name, price, quantity, emoji FROM xbox_games ORDER BY game_name ASC");
            $stmt->execute();
            $games = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'games' => $games]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching Xbox games']);
            exit;
        }
    }

    // Add Retro games endpoint
    if ($_GET['action'] === 'get_retro_games') {
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id, game_name, price, quantity, emoji, platform FROM retro_games ORDER BY game_name ASC");
            $stmt->execute();
            $games = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'games' => $games]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching retro games']);
            exit;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refill_form'])) {
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
        $quantity = intval($_POST['quantity'] ?? 0);
        $gamePrice = floatval($_POST['gamePrice'] ?? 0);
        $action = $_POST['action'] ?? '';
        
        // Validate required fields
        if (empty($gameName) || empty($action)) {
            echo json_encode(['success' => false, 'message' => 'Game name and action are required']);
            exit;
        }
        
        // Validate action-specific requirements
        if (in_array($action, ['restock', 'both']) && $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0 for restocking']);
            exit;
        }
        
        if (in_array($action, ['update_price', 'both']) && $gamePrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0 for price updates']);
            exit;
        }
        
        // Now all platforms have database integration
        if (!in_array($platform, ['switch', 'ps5', 'xbox', 'retro'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid platform selected']);
            exit;
        }
        
        // Determine which table to use based on platform
        $tableName = '';
        $platformName = '';
        
        if ($platform === 'switch') {
            $tableName = 'nintendo_games';
            $platformName = 'Nintendo Switch';
        } elseif ($platform === 'ps5') {
            $tableName = 'ps5_games';
            $platformName = 'PlayStation 5';
        } elseif ($platform === 'xbox') {
            $tableName = 'xbox_games';
            $platformName = 'Xbox Series X/S';
        } elseif ($platform === 'retro') {
            $tableName = 'retro_games';
            $platformName = 'Retro Games';
        }
        
        // Handle database operations for all platforms
        $stmt = $pdo->prepare("SELECT id, game_name, price, quantity FROM {$tableName} WHERE LOWER(game_name) = LOWER(?)");
        $stmt->execute([$gameName]);
        $existingGame = $stmt->fetch();
        
        if (!$existingGame) {
            echo json_encode(['success' => false, 'message' => "Game '{$gameName}' not found in {$platformName} database. Please check the spelling or select from available games."]);
            exit;
        }
        
        // Process the action
        $updateFields = [];
        $updateValues = [];
        $message = "✅ Success! '{$existingGame['game_name']}' has been updated. ";
        
        switch ($action) {
            case 'restock':
                $newQuantity = $existingGame['quantity'] + $quantity;
                $updateFields[] = "quantity = ?";
                $updateValues[] = $newQuantity;
                $message .= "Added {$quantity} copies (Total: {$newQuantity}).";
                break;
                
            case 'update_price':
                $updateFields[] = "price = ?";
                $updateValues[] = $gamePrice;
                $message .= "Price updated to $" . number_format($gamePrice, 2) . ".";
                break;
                
            case 'both':
                $newQuantity = $existingGame['quantity'] + $quantity;
                $updateFields[] = "quantity = ?";
                $updateFields[] = "price = ?";
                $updateValues[] = $newQuantity;
                $updateValues[] = $gamePrice;
                $message .= "Added {$quantity} copies (Total: {$newQuantity}) and updated price to $" . number_format($gamePrice, 2) . ".";
                break;
        }
        
        // Add updated_at timestamp for all platforms with this column
        if (in_array($tableName, ['nintendo_games', 'xbox_games', 'retro_games'])) {
            $updateFields[] = "updated_at = NOW()";
        }
        $updateValues[] = $existingGame['id'];
        
        // Execute the update
        $sql = "UPDATE {$tableName} SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update the game in database']);
        }
        exit;
        
    } catch (Exception $e) {
        error_log("Error processing refill: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refill Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            min-height: 100vh;
            padding: 20px;
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

        .header-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .refill-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: linear-gradient(45deg, #ff9a9e, #fecfef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            box-shadow: 0 8px 25px rgba(255, 154, 158, 0.3);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
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
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .header-section p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
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
        select:focus {
            outline: none;
            border-color: #ff9a9e;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 154, 158, 0.1);
        }

        .quantity-price-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #ff9a9e, #fecfef);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 154, 158, 0.3);
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 154, 158, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 14px;
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
            background: rgba(255, 154, 158, 0.1);
            transform: none;
            box-shadow: none;
        }

        .tab-button.active {
            background: linear-gradient(45deg, #ff9a9e, #fecfef);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 154, 158, 0.3);
        }

        .platform-info {
            background: linear-gradient(135deg, rgba(255, 154, 158, 0.1), rgba(254, 207, 239, 0.1));
            border: 1px solid rgba(255, 154, 158, 0.2);
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

        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .game-suggestions {
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            width: calc(100% - 30px);
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }

        .suggestion-item:hover {
            background: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item small {
            display: block;
            color: #666;
            margin-top: 4px;
            font-size: 12px;
        }

        .game-name-wrapper {
            position: relative;
        }

        @media (max-width: 600px) {
            .quantity-price-row {
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
            
            .platform-info h3 {
                font-size: 16px;
            }
            
            .platform-info p {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="refill-icon">
                📦
            </div>
            <h1>Refill Your Game Store</h1>
            <p>Keep your inventory stocked! Add more copies of existing games or update their details.</p>
        </div>

        <div class="info-box">
            <p>💡 <strong>Tip:</strong> Select your gaming platform below, then add more copies of existing games or update their details.</p>
        </div>
<!-- Back Navigation -->
          <div class="back-navigation">
         <a href="admin-page.php" class="back-button">
        <span class="arrow">←</span>
        Back to Admin
         </a>
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
                <p>Manage your PS5 game inventory</p>
            </div>
        </div>

        <form id="refillForm" method="POST">
            <input type="hidden" name="refill_form" value="1">
            <input type="hidden" id="platform" name="platform" value="ps5">
            
            <div class="form-group">
                <label for="gameName">🎮 Game Name:</label>
                <div class="game-name-wrapper">
                    <input type="text" id="gameName" name="gameName" placeholder="e.g., Spider-Man 2, Horizon Forbidden West" required autocomplete="off">
                    <div class="game-suggestions" id="gameSuggestions"></div>
                </div>
            </div>

            <div class="quantity-price-row">
                <div class="form-group">
                    <label for="quantity">📊 Quantity to Add:</label>
                    <input type="number" id="quantity" name="quantity" placeholder="How many copies?" min="1" required>
                </div>

                <div class="form-group">
                    <label for="gamePrice">💰 Updated Price (optional):</label>
                    <input type="number" id="gamePrice" name="gamePrice" placeholder="New price" step="0.01" min="0">
                </div>
            </div>

            <div class="form-group">
                <label for="action">🔄 Action Type:</label>
                <select id="action" name="action" required>
                    <option value="">Select action...</option>
                    <option value="restock">📈 Restock (Add to existing inventory)</option>
                    <option value="update_price">💲 Update Price Only</option>
                    <option value="both">🔄 Restock + Update Price</option>
                </select>
            </div>

            <button type="submit">
                📦 Refill Store Inventory
            </button>
        </form>
    </div>

    <script>
        let currentPlatform = 'ps5';
        let switchGames = [];
        let ps5Games = [];
        let xboxGames = [];
        let retroGames = []; // Add retro games array

        // Platform information
        const platformData = {
            ps5: {
                title: 'PlayStation 5',
                description: 'Manage your PS5 game inventory',
                placeholder: 'e.g., Spider-Man 2, Horizon Forbidden West'
            },
            switch: {
                title: 'Nintendo Switch',
                description: 'Update your Switch game collection',
                placeholder: 'e.g., Super Mario Odyssey, Zelda: BOTW'
            },
            xbox: {
                title: 'Xbox Series X/S',
                description: 'Manage your Xbox game library',
                placeholder: 'e.g., Halo Infinite, Forza Horizon 5'
            },
            retro: {
                title: 'Retro Games',
                description: 'Update Classic gaming collections',
                placeholder: 'e.g., PAC-MAN, Street Fighter II, Tetris'
            }
        };

        // Load Nintendo Switch games from database
        async function loadSwitchGames() {
            try {
                const response = await fetch('?action=get_switch_games');
                const data = await response.json();
                if (data.success) {
                    switchGames = data.games;
                }
            } catch (error) {
                console.error('Error loading Switch games:', error);
            }
        }
        
        // Load PS5 games from database
        async function loadPS5Games() {
            try {
                const response = await fetch('?action=get_ps5_games');
                const data = await response.json();
                if (data.success) {
                    ps5Games = data.games;
                }
            } catch (error) {
                console.error('Error loading PS5 games:', error);
            }
        }

        // Load Xbox games from database
        async function loadXboxGames() {
            try {
                const response = await fetch('?action=get_xbox_games');
                const data = await response.json();
                if (data.success) {
                    xboxGames = data.games;
                }
            } catch (error) {
                console.error('Error loading Xbox games:', error);
            }
        }

        // Load Retro games from database
        async function loadRetroGames() {
            try {
                const response = await fetch('?action=get_retro_games');
                const data = await response.json();
                if (data.success) {
                    retroGames = data.games;
                }
            } catch (error) {
                console.error('Error loading retro games:', error);
            }
        }

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
                
                // Clear suggestions when switching platforms
                document.getElementById('gameSuggestions').style.display = 'none';
            });
        });

        // Game name autocomplete for all platforms
        document.getElementById('gameName').addEventListener('input', function() {
            const value = this.value.toLowerCase();
            const suggestionsDiv = document.getElementById('gameSuggestions');
            
            let currentGames = [];
            if (currentPlatform === 'switch') {
                currentGames = switchGames;
            } else if (currentPlatform === 'ps5') {
                currentGames = ps5Games;
            } else if (currentPlatform === 'xbox') {
                currentGames = xboxGames;
            } else if (currentPlatform === 'retro') {
                currentGames = retroGames;
            }
            
            if (value.length > 0) {
                const matches = currentGames.filter(game => 
                    game.game_name.toLowerCase().includes(value)
                );
                
                if (matches.length > 0) {
                    suggestionsDiv.innerHTML = matches.map(game => 
                        `<div class="suggestion-item" data-game='${JSON.stringify(game)}'>
                            ${game.emoji} ${game.game_name} - $${game.price} (Stock: ${game.quantity})
                            ${game.platform ? `<small>Platform: ${game.platform}</small>` : ''}
                        </div>`
                    ).join('');
                    suggestionsDiv.style.display = 'block';
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            } else {
                suggestionsDiv.style.display = 'none';
            }
        });

        // Handle suggestion clicks
        document.getElementById('gameSuggestions').addEventListener('click', function(e) {
            if (e.target.classList.contains('suggestion-item')) {
                const gameData = JSON.parse(e.target.dataset.game);
                document.getElementById('gameName').value = gameData.game_name;
                document.getElementById('gamePrice').value = gameData.price;
                this.style.display = 'none';
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.game-name-wrapper')) {
                document.getElementById('gameSuggestions').style.display = 'none';
            }
        });

        // Form submission
        document.getElementById('refillForm').addEventListener('submit', async function(event) {
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
                    // Reload games if updated
                    if (currentPlatform === 'switch') {
                        loadSwitchGames();
                    } else if (currentPlatform === 'ps5') {
                        loadPS5Games();
                    } else if (currentPlatform === 'xbox') {
                        loadXboxGames();
                    } else if (currentPlatform === 'retro') {
                        loadRetroGames();
                    }
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        // Show/hide quantity field based on action selection
        document.getElementById('action').addEventListener('change', function() {
            const quantityField = document.getElementById('quantity');
            const priceField = document.getElementById('gamePrice');
            
            if (this.value === 'update_price') {
                quantityField.required = false;
                quantityField.style.opacity = '0.5';
                priceField.required = true;
            } else if (this.value === 'restock') {
                quantityField.required = true;
                quantityField.style.opacity = '1';
                priceField.required = false;
            } else if (this.value === 'both') {
                quantityField.required = true;
                quantityField.style.opacity = '1';
                priceField.required = true;
            } else {
                quantityField.required = true;
                quantityField.style.opacity = '1';
                priceField.required = false;
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

        // Load all games when page loads
        loadSwitchGames();
        loadPS5Games();
        loadXboxGames();
        loadRetroGames(); // Add retro games loading
    </script>
</body>
</html>