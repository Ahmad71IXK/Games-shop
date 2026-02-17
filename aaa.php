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

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        $platform = $_POST['platform'] ?? '';
        $gameId = intval($_POST['game_id'] ?? 0);
        
        if (!in_array($platform, ['switch', 'ps5', 'xbox', 'retro']) || $gameId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid platform or game ID']);
            exit;
        }
        
        // Determine table name
        $tableName = '';
        switch ($platform) {
            case 'switch':
                $tableName = 'nintendo_games';
                break;
            case 'ps5':
                $tableName = 'ps5_games';
                break;
            case 'xbox':
                $tableName = 'xbox_games';
                break;
            case 'retro':
                $tableName = 'retro_games';
                break;
        }
        
        // Get game name before deletion for confirmation message
        $stmt = $pdo->prepare("SELECT game_name FROM {$tableName} WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        
        if (!$game) {
            echo json_encode(['success' => false, 'message' => 'Game not found']);
            exit;
        }
        
        // Delete the game
        $stmt = $pdo->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $result = $stmt->execute([$gameId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => "✅ Game '{$game['game_name']}' has been deleted successfully!"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete game']);
        }
        exit;
        
    } catch (Exception $e) {
        error_log("Error deleting game: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the game']);
        exit;
    }
}

// Get games for display
function getGames($pdo, $platform) {
    $tableName = '';
    switch ($platform) {
        case 'switch':
            $tableName = 'nintendo_games';
            break;
        case 'ps5':
            $tableName = 'ps5_games';
            break;
        case 'xbox':
            $tableName = 'xbox_games';
            break;
        case 'retro':
            $tableName = 'retro_games';
            break;
        default:
            return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$tableName} ORDER BY game_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching games: " . $e->getMessage());
        return [];
    }
}

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'SF Pro Display', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }

        /* Navigation */
        .nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 12px;
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
            color: #667eea;
        }

        .nav-btn:active {
            transform: translateY(-1px) scale(1.02);
            transition: all 0.1s;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 3em;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2em;
            font-weight: 500;
        }

        /* Platform Tabs */
        .platforms {
            display: flex;
            justify-content: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .platform {
            padding: 14px 28px;
            background: transparent;
            border: none;
            color: #666;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 1.1em;
            position: relative;
            overflow: hidden;
        }

        .platform::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(102, 126, 234, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .platform:hover::after {
            width: 200px;
            height: 200px;
        }

        .platform:hover {
            color: #333;
            transform: translateY(-2px);
        }

        .platform.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 700;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .platform.active::after {
            display: none;
        }

        /* Games Grid */
        .games {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .game {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .game::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 16px;
            padding: 2px;
            background: linear-gradient(135deg, 
                #667eea, 
                #764ba2, 
                #f093fb, 
                #f5576c, 
                #4facfe, 
                #00f2fe
            );
            background-size: 300% 300%;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: xor;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            opacity: 0;
            animation: gradientShift 3s ease infinite;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .game:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .game:hover::before {
            opacity: 1;
        }

        .game-emoji {
            font-size: 3em;
            text-align: center;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
            transition: transform 0.3s;
        }

        .game:hover .game-emoji {
            transform: scale(1.1) rotate(5deg);
        }

        .game-name {
            font-size: 1.4em;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
            color: #333;
        }

        .game-desc {
            color: #666;
            font-size: 1em;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.5;
            font-weight: 400;
        }

        .game-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
        }

        .price {
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
        }

        .stock {
            color: #666;
            font-size: 1em;
            font-weight: 500;
        }

        .delete-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .delete-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent
            );
            transition: left 0.6s;
        }

        .delete-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.4s ease-out;
            pointer-events: none;
        }

        .delete-btn:hover::before {
            left: 100%;
        }

        .delete-btn:hover::after {
            width: 300px;
            height: 300px;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(118, 75, 162, 0.4);
        }

        .delete-btn:active {
            transform: translateY(-1px) scale(1.02);
            transition: all 0.1s;
        }

        .delete-btn:active::after {
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.4);
            transition: all 0.1s;
        }

        /* Enhanced loading state */
        .delete-btn.deleting {
            background: #ccc;
            cursor: not-allowed;
            transform: scale(0.98);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Empty state */
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.8;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .empty h3 {
            color: white;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .empty p {
            font-size: 1.2em;
            font-weight: 400;
        }

        /* Enhanced Alert */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1em;
            z-index: 1000;
            display: none;
            min-width: 320px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transform: translateX(400px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .alert.show {
            display: block;
            transform: translateX(0);
        }

        .alert.success {
            background: rgba(76, 175, 80, 0.95);
            color: white;
            border-left: 4px solid #4CAF50;
        }

        .alert.error {
            background: rgba(244, 67, 54, 0.95);
            color: white;
            border-left: 4px solid #F44336;
        }

        /* Loading */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            filter: blur(1px);
        }

        /* Game deletion animation */
        .game.removing {
            animation: slideOut 0.5s ease-in-out forwards;
        }

        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translateX(-100%) scale(0.8);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 12px;
            }

            .platforms {
                flex-wrap: wrap;
                gap: 10px;
            }

            .platform {
                flex: 1;
                min-width: 140px;
                font-size: 1em;
            }

            .games {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .header h1 {
                font-size: 2.5em;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .games {
            animation: fadeIn 0.6s ease;
        }

        .game {
            animation: fadeIn 0.6s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav">
        <a href="admin-page.php" class="nav-btn">
            <span>←</span> Back to Admin
        </a>
        <!-- <a href="admin-page.php" class="nav-btn">
            <span>🏠</span> Admin -->
        </a>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>Game Manager</h1>
        <p>Delete games from your collection</p>
    </div>

    <!-- Alert -->
    <div class="alert" id="alert"></div>

    <!-- Platform Tabs -->
    <div class="platforms">
        <button class="platform active" data-platform="ps5">🎮 PS5</button>
        <button class="platform" data-platform="switch">🕹️ Switch</button>
        <button class="platform" data-platform="xbox">🎯 Xbox</button>
        <button class="platform" data-platform="retro">👾 Retro</button>
    </div>

    <!-- Games Grid -->
    <div class="games" id="games">
        <!-- Games loaded here -->
    </div>

    <script>
        let currentPlatform = 'ps5';

        // Enhanced show alert with animation
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = `alert ${type}`;
            alert.textContent = message;
            alert.classList.add('show');
            
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.style.display = 'none', 400);
            }, 4000);
        }

        // Load games
        async function loadGames(platform) {
            const games = <?php 
                echo json_encode([
                    'ps5' => getGames($pdo, 'ps5'),
                    'switch' => getGames($pdo, 'switch'),
                    'xbox' => getGames($pdo, 'xbox'),
                    'retro' => getGames($pdo, 'retro')
                ]);
            ?>;
            
            const container = document.getElementById('games');
            const platformGames = games[platform] || [];
            
            if (platformGames.length === 0) {
                container.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon">📭</div>
                        <h3>No games found</h3>
                        <p>Add some games to get started!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = platformGames.map(game => `
                <div class="game" data-game-id="${game.id}">
                    <div class="game-emoji">${game.emoji || '🎮'}</div>
                    <div class="game-name">${escapeHtml(game.game_name)}</div>
                    <div class="game-desc">${escapeHtml(game.description)}</div>
                    <div class="game-info">
                        <span class="price">$${parseFloat(game.price).toFixed(2)}</span>
                        <span class="stock">${game.quantity} in stock</span>
                    </div>
                    <button class="delete-btn" onclick="deleteGame('${platform}', ${game.id}, '${escapeHtml(game.game_name)}')">
                        Delete Game
                    </button>
                </div>
            `).join('');
        }

        // Enhanced delete game with animations
        async function deleteGame(platform, gameId, gameName) {
            if (!confirm(`Delete "${gameName}"?`)) return;
            
            const gameElement = document.querySelector(`[data-game-id="${gameId}"]`);
            const deleteBtn = gameElement.querySelector('.delete-btn');
            
            // Add deleting state
            deleteBtn.classList.add('deleting');
            deleteBtn.textContent = 'Deleting...';
            
            try {
                const formData = new FormData();
                formData.append('delete_game', '1');
                formData.append('platform', platform);
                formData.append('game_id', gameId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Animate game removal
                    gameElement.classList.add('removing');
                    
                    setTimeout(() => {
                        showAlert(data.message, 'success');
                        // Reload games after animation
                        setTimeout(() => loadGames(currentPlatform), 100);
                    }, 250);
                } else {
                    showAlert(data.message, 'error');
                    deleteBtn.classList.remove('deleting');
                    deleteBtn.textContent = 'Delete Game';
                }
            } catch (error) {
                showAlert('Error deleting game', 'error');
                deleteBtn.classList.remove('deleting');
                deleteBtn.textContent = 'Delete Game';
            }
        }

        // Platform switching
        document.querySelectorAll('.platform').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.platform').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentPlatform = this.dataset.platform;
                loadGames(currentPlatform);
            });
        });

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize
        loadGames(currentPlatform);
    </script>
</body>
</html>