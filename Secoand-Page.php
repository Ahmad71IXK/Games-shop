<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "web_project";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit;
    }
    
    if ($action == 'add_to_cart') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit;
        }
        
        $game_name = $_POST['game_name'];
        $price = $_POST['price'];
        $user_id = $_SESSION['user_id'];
        
        // Check if item already exists in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND game_name = ?");
        $stmt->bind_param("is", $user_id, $game_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($existing_item = $result->fetch_assoc()) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + 1;
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $existing_item['id']);
            $stmt->execute();
        } else {
            // Insert new item
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, game_name, price, quantity) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("isd", $user_id, $game_name, $price);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action == 'remove_from_cart') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit;
        }
        
        $cart_id = $_POST['cart_id'];
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action == 'get_cart') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_items = [];
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
        
        echo json_encode(['success' => true, 'items' => $cart_items]);
        exit;
    }
    
    if ($action == 'search') {
        $search_term = $_POST['search_term'];
        
        // Define games array (you can move this to database later)
        $games = [
            ['name' => 'Hogwarts Legacy', 'price' => 59.99, 'emoji' => '🏰'],
            ['name' => 'Marvel Spider-Man 2', 'price' => 59.49, 'emoji' => '🕷️'],
            ['name' => 'Call of Duty MW3', 'price' => 69.99, 'emoji' => '🎯'],
            ['name' => 'NBA 2K25', 'price' => 41.99, 'emoji' => '🏀'],
            ['name' => 'Elden Ring', 'price' => 49.99, 'emoji' => '⚔️'],
            ['name' => 'Uncharted 4', 'price' => 31.99, 'emoji' => '🗺️']
        ];
        
        $results = array_filter($games, function($game) use ($search_term) {
            return stripos($game['name'], $search_term) !== false;
        });
        
        echo json_encode(['success' => true, 'results' => array_values($results)]);
        exit;
    }
    
    if ($action == 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get user cart count on page load
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStore - Your Gaming Destination</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: white;
            overflow-x: hidden;
        }

        /* Cart Styles */
        .cart-container {
            position: relative;
        }

        .cart-btn {
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 20px;
            color: white;
            position: relative;
        }

        .cart-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.4);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            min-width: 20px;
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .cart-count.show {
            transform: scale(1);
        }

        .cart-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 350px;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
            max-height: 400px;
            overflow-y: auto;
        }

        .cart-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 212, 255, 0.3);
        }

        .cart-title {
            font-size: 18px;
            font-weight: bold;
            color: #00d4ff;
        }

        .cart-close {
            background: none;
            border: none;
            color: #ff4757;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .cart-close:hover {
            transform: rotate(90deg);
        }

        .cart-items {
            max-height: 200px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: bold;
            color: white;
            margin-bottom: 2px;
        }

        .cart-item-price {
            color: #00d4ff;
            font-size: 14px;
        }

        .cart-item-remove {
            background: #ff4757;
            border: none;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .cart-item-remove:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        .cart-empty {
            text-align: center;
            color: #a0a0a0;
            padding: 30px;
        }

        .cart-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(0, 212, 255, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-total-label {
            font-weight: bold;
            font-size: 16px;
        }

        .cart-total-amount {
            font-weight: bold;
            font-size: 18px;
            color: #00d4ff;
        }

        .cart-checkout {
            width: 100%;
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .cart-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.4);
        }

        /* Sign-in Modal */
        .signin-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .signin-modal.active {
            display: flex;
        }

        .signin-card {
            background: linear-gradient(145deg, #2a2a3e, #1e1e32);
            border: 2px solid #00d4ff;
            border-radius: 20px;
            padding: 40px;
            width: 400px;
            max-width: 90vw;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.3);
            animation: modalSlide 0.4s ease-out;
            position: relative;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .signin-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: #00d4ff;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signin-close:hover {
            transform: rotate(90deg);
            color: #ff4757;
        }

        .signin-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
        }

        .signin-title {
            font-size: 28px;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 10px;
        }

        .signin-subtitle {
            color: #a0a0a0;
            margin-bottom: 30px;
        }

        .signin-form {
            margin-bottom: 20px;
        }

        .signin-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 10px;
            color: white;
            outline: none;
        }

        .signin-input:focus {
            border-color: #00d4ff;
        }

        .signin-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .signin-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .signin-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .signin-btn.primary {
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            color: white;
        }

        .signin-btn.secondary {
            background: transparent;
            color: #00d4ff;
            border: 2px solid #00d4ff;
        }

        .signin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.3);
        }

        .user-info {
            color: #00d4ff;
            font-size: 14px;
            margin-right: 10px;
        }

        /* Search Results */
        .search-results {
            position: absolute;
            top: 45px;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 15px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1002;
            display: none;
        }

        .search-result-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-result-item:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        .search-result-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-result-emoji {
            font-size: 24px;
        }

        .search-result-name {
            font-weight: bold;
            color: white;
        }

        .search-result-price {
            color: #00d4ff;
            font-weight: bold;
        }

        /* Navigation Bar */
        .navbar {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            padding: 15px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 999;
            border-bottom: 1px solid rgba(0, 212, 255, 0.3);
        }

        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(45deg, #00d4ff, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
        }

        .nav-buttons a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-buttons a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .nav-buttons a:hover::before {
            left: 100%;
        }

        .nav-buttons a:hover {
            background: rgba(0, 212, 255, 0.2);
            transform: translateY(-2px);
        }

        .right-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-btn {
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 20px;
            color: white;
        }

        .user-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.4);
        }

        .search-container {
            position: relative;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 212, 255, 0.3);
            border-radius: 25px;
            padding: 10px 20px;
            color: white;
            outline: none;
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-box:focus {
            border-color: #00d4ff;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .search-box::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460);
            z-index: -2;
        }

        .hero-bg::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 30%, rgba(0, 0, 0, 0.7) 100%);
            z-index: -1;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: linear-gradient(45deg, #00d4ff, #ff6b6b);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .hero-content {
            text-align: center;
            z-index: 2;
            max-width: 800px;
            padding: 0 20px;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #00d4ff, #ff6b6b, #ffa726);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { filter: drop-shadow(0 0 20px rgba(0, 212, 255, 0.5)); }
            to { filter: drop-shadow(0 0 40px rgba(255, 107, 107, 0.5)); }
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: #a0a0a0;
            margin-bottom: 40px;
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cta-button {
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            color: white;
            border: none;
            padding: 18px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 1s ease-out 1s both;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 212, 255, 0.4);
        }

        /* Games Store Section */
        .games-store {
            padding: 100px 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .store-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .store-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .store-title {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(45deg, #00d4ff, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }

        .store-subtitle {
            font-size: 1.2rem;
            color: #a0a0a0;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .game-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            position: relative;
            cursor: pointer;
        }

        .game-card:hover {
            transform: translateY(-10px);
            border-color: rgba(0, 212, 255, 0.5);
            box-shadow: 0 20px 60px rgba(0, 212, 255, 0.3);
        }

        .game-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #00d4ff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            position: relative;
            overflow: hidden;
        }

        .game-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .game-card:hover .game-image::before {
            left: 100%;
        }

        .game-info {
            padding: 25px;
        }

        .game-title {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #00d4ff;
        }

        .game-description {
            color: #a0a0a0;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .game-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .game-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00d4ff;
        }

        .game-price .original-price {
            text-decoration: line-through;
            color: #666;
            font-size: 1rem;
            margin-right: 10px;
        }

        .buy-btn {
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .buy-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 212, 255, 0.4);
        }

        .sale-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff4757;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .featured-games {
            margin-bottom: 80px;
        }

        .featured-title {
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Image Links Section */
        .image-links-section {
            padding: 100px 0 0 0;
            background: rgba(0, 0, 0, 0.2);
        }

        .slider-container-consoles {
            position: relative;
            max-width: 800px;
            margin: 0 auto 50px;
            overflow: hidden;
            border-radius: 25px;
        }

        .image-links-wrapper {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .image-links {
            list-style-type: none;
            display: flex;
            justify-content: center;
            gap: 50px;
            position: relative;
            padding: 50px;
            margin: 0;
            min-width: 100%;
        }

        .image-links a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            position: relative;
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0px 0px 20px rgba(0, 212, 255, 0.3);
            border-radius: 20px;
            font-size: 20px;
            transition: all 0.8s ease;
            overflow: hidden;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }

        .image-links .a1 {
            background-image: url("ps.jpg");
        }

        .image-links .a2 {
            background-image: url("ns.jpg");
        }

        .image-links .a3 {
            background-image: url("xbox.jpg");
        }

        .image-links .a4 {
            background-image: url("retro.jpg");
        }

        .image-links a::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
            transition: all 0.3s ease;
        }

        .image-links .a1::after {
            content: "PlayStation";
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .image-links .a2::after {
            content: "Nintendo";
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .image-links .a3::after {
            content: "Xbox";
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .image-links .a4::after {
            content: "Retro";
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        .image-links a:hover {
            transform: skew(7deg) translateY(-10px) scale(1.1);
            box-shadow: 0px 15px 35px rgba(255, 165, 0, 0.7);
        }

        .image-links a::after {
            transition: all 0.3s ease;
        }

        .image-links a:hover::after {
            color: #00d4ff;
        }

        /* Cart Notification */
        .cart-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(145deg, #00d4ff, #0056b3);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .cart-notification.show {
            transform: translateX(0);
        }

        .footer {
          background-color: rgba(14, 34, 46, 0.75);
          padding: 20px;
          text-align: center;
          font-family: 'Segoe UI', sans-serif;
        }

        .footer ul {
          list-style: none;
          padding: 0;
          margin: 0;
          display: flex;
          justify-content: center;
          gap: 30px;
        }

        .footer li a {
          text-decoration: none;
          color: #a8edea;
          font-weight: bold;
          font-size: 1.1rem;
          transition: color 0.3s ease, text-shadow 0.3s ease;
        }

        .footer li a:hover {
          color: #ffd6a5;
          text-decoration: underline;
          text-shadow: 0 0 8px rgba(255, 214, 165, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-inner {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-buttons {
                order: 2;
            }

            .right-section {
                order: 1;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .search-box {
                width: 200px;
            }

            .signin-card {
                padding: 30px 20px;
            }

            .signin-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .image-links {
                flex-wrap: wrap;
                gap: 30px;
            }
            
            .image-links a {
                width: 120px;
                height: 120px;
                font-size: 16px;
            }

            .games-grid {
                grid-template-columns: 1fr;
            }

            .store-title {
                font-size: 2rem;
            }

            .game-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .cart-dropdown {
                width: 300px;
                right: -50px;
            }
        }
    </style>
</head>
<body>
    <!-- Cart Notification -->
    <div class="cart-notification" id="cartNotification">
        Game added to cart! 🎮
    </div>

    <!-- Sign-in Modal -->
    <div class="signin-modal" id="signinModal">
        <div class="signin-card">
            <button class="signin-close" onclick="closeSigninModal()">&times;</button>
            <div class="signin-logo">🎮</div>
            <div class="signin-title">Welcome Back!</div>
            <div class="signin-subtitle">Join the ultimate gaming community</div>
            
            <form class="signin-form" id="loginForm">
                <input type="email" class="signin-input" id="loginEmail" placeholder="Email" required>
                <input type="password" class="signin-input" id="loginPassword" placeholder="Password" required>
                <button type="submit" class="signin-btn primary" style="width: 100%;">Sign In</button>
            </form>
            
            <div class="signin-buttons">
                <button class="signin-btn secondary" onclick="window.location.href='index.php'">Sign Up</button>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="logo">GameStore</div>
            <div class="nav-buttons">
                <a href="#home">Home</a>
                <a href="#games">Games</a>
                <a href="#consoles">Consoles</a>
                <a href="retro.php">Retro</a>
            </div>
            <div class="right-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</div>
                    <button class="user-btn" onclick="logout()" title="Logout">🚪</button>
                <?php else: ?>
                    <button class="user-btn" onclick="showSigninModal()">👤</button>
                <?php endif; ?>
                
                <!-- Cart Container -->
                <div class="cart-container">
                    <button class="cart-btn" onclick="toggleCart()">
                        🛒
                        <div class="cart-count" id="cartCount"><?php echo $cart_count; ?></div>
                    </button>
                    
                    <!-- Cart Dropdown -->
                    <div class="cart-dropdown" id="cartDropdown">
                        <div class="cart-header">
                            <div class="cart-title">Shopping Cart</div>
                            <button class="cart-close" onclick="closeCart()">&times;</button>
                        </div>
                        
                        <div id="cartContent">
                            <div class="cart-empty" id="cartEmpty">
                                Your cart is empty 🛍️
                            </div>
                            
                            <div class="cart-items" id="cartItems" style="display: none;">
                                <!-- Cart items will be inserted here dynamically -->
                            </div>
                            
                            <div class="cart-total" id="cartTotal" style="display: none;">
                                <div class="cart-total-label">Total:</div>
                                <div class="cart-total-amount" id="cartTotalAmount">$0.00</div>
                            </div>
                            
                            <button class="cart-checkout" id="cartCheckout" style="display: none;" onclick="checkout()">
                                Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="search-container">
                    <input type="text" class="search-box" id="searchBox" placeholder="Search games, consoles..." onkeyup="searchGames(this.value)">
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg"></div>
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">GameStore</h1>
            <p class="hero-subtitle">Games, Consoles, Retro & More</p>
            <button class="cta-button" onclick="scrollToSection('games')">Shop Now!</button>
        </div>
    </section>

    <!-- Games Store Section -->
    <section class="games-store" id="games">
        <div class="store-container">
            <div class="store-header">
                <h2 class="store-title">Featured Games</h2>
                <p class="store-subtitle">Discover the latest and greatest games at unbeatable prices</p>
            </div>

            <div class="featured-games">
                <div class="games-grid">
                    <div class="game-card">
                        <div class="sale-badge">-25%</div>
                        <div class="game-image">🏰</div>
                        <div class="game-info">
                            <h3 class="game-title">Hogwarts Legacy</h3>
                            <p class="game-description">Experience the magic of Hogwarts in this immersive open-world adventure. Cast spells, brew potions, and uncover ancient secrets.</p>
                            <div class="game-footer">
                                <div class="game-price">
                                    <span class="original-price">$79.99</span>
                                    $59.99
                                </div>
                                <button class="buy-btn" onclick="addToCart('Hogwarts Legacy', 59.99)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="sale-badge">-15%</div>
                        <div class="game-image">🕷️</div>
                        <div class="game-info">
                            <h3 class="game-title">Marvel's Spider-Man 2</h3>
                            <p class="game-description">Swing through New York City as both Peter Parker and Miles Morales in this epic superhero adventure.</p>
                            <div class="game-footer">
                                <div class="game-price">
                                    <span class="original-price">$69.99</span>
                                    $59.49
                                </div>
                                <button class="buy-btn" onclick="addToCart('Marvel Spider-Man 2', 59.49)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="game-image">🎯</div>
                        <div class="game-info">
                            <h3 class="game-title">Call of Duty: Modern Warfare III</h3>
                            <p class="game-description">Experience the ultimate first-person shooter with cutting-edge graphics and intense multiplayer action.</p>
                            <div class="game-footer">
                                <div class="game-price">$69.99</div>
                                <button class="buy-btn" onclick="addToCart('Call of Duty MW3', 69.99)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="sale-badge">-30%</div>
                        <div class="game-image">🏀</div>
                        <div class="game-info">
                            <h3 class="game-title">NBA 2K25</h3>
                            <p class="game-description">Hit the court with the most realistic basketball simulation ever created. Updated rosters and enhanced gameplay.</p>
                            <div class="game-footer">
                                <div class="game-price">
                                    <span class="original-price">$59.99</span>
                                    $41.99
                                </div>
                                <button class="buy-btn" onclick="addToCart('NBA 2K25', 41.99)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="game-image">⚔️</div>
                        <div class="game-info">
                            <h3 class="game-title">Elden Ring</h3>
                            <p class="game-description">Explore a vast fantasy world filled with mystery and danger. From the creators of Dark Souls.</p>
                            <div class="game-footer">
                                <div class="game-price">$49.99</div>
                                <button class="buy-btn" onclick="addToCart('Elden Ring', 49.99)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="game-card">
                        <div class="sale-badge">-20%</div>
                        <div class="game-image">🗺️</div>
                        <div class="game-info">
                            <h3 class="game-title">Uncharted 4</h3>
                            <p class="game-description">Join Nathan Drake on his final adventure in this action-packed treasure hunting experience.</p>
                            <div class="game-footer">
                                <div class="game-price">
                                    <span class="original-price">$39.99</span>
                                    $31.99
                                </div>
                                <button class="buy-btn" onclick="addToCart('Uncharted 4', 31.99)">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Image Links Section -->
    <section class="image-links-section" id="consoles">
        <div class="slider-container-consoles">
            <div class="image-links-wrapper" id="consoleSliderWrapper">
                <ul class="image-links">
                    <li><a class="a1" href="ps5.php"></a></li>
                    <li><a class="a2" href="nintendo-page.php"></a></li>
                    <li><a class="a3" href="xbox-page.php"></a></li>
                    <li><a class="a4" href="retro.php"></a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <ul>
                <li class="a"><a href="#">Contact us</a></li>
                <li class="b"><a href="#">About us</a></li>
                <li class="c"><a href="#">&copy; Ahmad ali And Abdalslam</a></li>
            </ul>
        </div>
    </section>

    <script>
        // Global variables
        let cart = [];
        let searchTimeout;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadUserCart();
            updateCartDisplay();
        });

        // Login functionality
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=login&email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Login failed');
            });
        });

        // Logout functionality
        function logout() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=logout'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Cart functionality
        function addToCart(gameName, price) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_to_cart&game_name=' + encodeURIComponent(gameName) + '&price=' + price
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUserCart();
                    showCartNotification();
                } else {
                    alert(data.message || 'Please login to add items to cart');
                    if (data.message && data.message.includes('login')) {
                        showSigninModal();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add item to cart');
            });
        }

        function removeFromCart(cartId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_from_cart&cart_id=' + cartId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUserCart();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function loadUserCart() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cart = data.items || [];
                    updateCartDisplay();
                }
            })
            .catch(error => {
                console.error('Error loading cart:', error);
            });
        }

        function updateCartDisplay() {
            const cartCount = document.getElementById('cartCount');
            const cartEmpty = document.getElementById('cartEmpty');
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const cartCheckout = document.getElementById('cartCheckout');
            const cartTotalAmount = document.getElementById('cartTotalAmount');

            // Update cart count
            const totalItems = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
            cartCount.textContent = totalItems;
            cartCount.classList.toggle('show', totalItems > 0);

            if (cart.length === 0) {
                // Show empty state
                cartEmpty.style.display = 'block';
                cartItems.style.display = 'none';
                cartTotal.style.display = 'none';
                cartCheckout.style.display = 'none';
            } else {
                // Show cart items
                cartEmpty.style.display = 'none';
                cartItems.style.display = 'block';
                cartTotal.style.display = 'flex';
                cartCheckout.style.display = 'block';

                // Generate cart items HTML
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.game_name}</div>
                            <div class="cart-item-price">${parseFloat(item.price).toFixed(2)} × ${item.quantity}</div>
                        </div>
                        <button class="cart-item-remove" onclick="removeFromCart(${item.id})">×</button>
                    </div>
                `).join('');

                // Calculate and display total
                const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
                cartTotalAmount.textContent = `${total.toFixed(2)}`;
            }
        }

        function toggleCart() {
            const cartDropdown = document.getElementById('cartDropdown');
            cartDropdown.classList.toggle('show');
        }

        function closeCart() {
            const cartDropdown = document.getElementById('cartDropdown');
            cartDropdown.classList.remove('show');
        }

        function checkout() {
            if (cart.length === 0) return;
            
            const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
            alert(`Thank you for your purchase! Total: ${total.toFixed(2)}`);
            
            // In a real application, you'd process the payment here
            // For now, we'll just clear the cart
            cart.forEach(item => removeFromCart(item.id));
            closeCart();
        }

        // Search functionality
        function searchGames(query) {
            const searchResults = document.getElementById('searchResults');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=search&search_term=' + encodeURIComponent(query)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.results.length > 0) {
                        searchResults.innerHTML = data.results.map(game => `
                            <div class="search-result-item" onclick="addToCart('${game.name}', ${game.price})">
                                <div class="search-result-info">
                                    <span class="search-result-emoji">${game.emoji}</span>
                                    <span class="search-result-name">${game.name}</span>
                                </div>
                                <span class="search-result-price">${game.price.toFixed(2)}</span>
                            </div>
                        `).join('');
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item">No games found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.style.display = 'none';
                });
            }, 300);
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchContainer = document.querySelector('.search-container');
            const cartContainer = document.querySelector('.cart-container');
            
            if (!searchContainer.contains(event.target)) {
                document.getElementById('searchResults').style.display = 'none';
            }
            
            if (!cartContainer.contains(event.target)) {
                closeCart();
            }
        });

        function showCartNotification() {
            const notification = document.getElementById('cartNotification');
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function showSigninModal() {
            document.getElementById("signinModal").classList.add("active");
        }

        function closeSigninModal() {
            document.getElementById("signinModal").classList.remove("active");
        }

        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>