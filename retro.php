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
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if item is in stock
            $stmt = $conn->prepare("SELECT quantity FROM retro_games WHERE game_name = ?");
            $stmt->bind_param("s", $game_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($game = $result->fetch_assoc()) {
                if ($game['quantity'] <= 0) {
                    throw new Exception("This item is out of stock");
                }
                
                // Decrement stock
                $stmt = $conn->prepare("UPDATE retro_games SET quantity = quantity - 1 WHERE game_name = ? AND quantity > 0");
                $stmt->bind_param("s", $game_name);
                $stmt->execute();
                
                if ($stmt->affected_rows === 0) {
                    throw new Exception("Failed to update stock");
                }
            } else {
                throw new Exception("Game not found");
            }
            
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
            
            // Commit transaction
            $conn->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action == 'remove_from_cart') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit;
        }
        
        $cart_id = $_POST['cart_id'];
        $user_id = $_SESSION['user_id'];
        
        // Start transaction to restore stock
        $conn->begin_transaction();
        
        try {
            // Get the game name and quantity from cart
            $stmt = $conn->prepare("SELECT game_name, quantity FROM cart_items WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($cart_item = $result->fetch_assoc()) {
                // Restore stock
                $stmt = $conn->prepare("UPDATE retro_games SET quantity = quantity + ? WHERE game_name = ?");
                $stmt->bind_param("is", $cart_item['quantity'], $cart_item['game_name']);
                $stmt->execute();
            }
            
            // Remove from cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error removing item']);
        }
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
        $search_term = trim($_POST['search_term']);
        
        if (empty($search_term) || strlen($search_term) < 1) {
            echo json_encode(['success' => false, 'message' => 'Search term required']);
            exit;
        }
        
        // Search in database with proper LIKE syntax
        $stmt = $conn->prepare("SELECT game_name, price, emoji FROM retro_games WHERE game_name LIKE ? AND quantity > 0 LIMIT 10");
        $search_param = "%" . $search_term . "%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'name' => $row['game_name'],
                'price' => (float)$row['price'],
                'emoji' => $row['emoji']
            ];
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
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

// Get retro games for display
$retro_games = [];
$stmt = $conn->prepare("SELECT game_name, price, emoji, description FROM retro_games WHERE quantity > 0 ORDER BY game_name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $retro_games[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RetroPixel Gaming Store</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
          /* Google Fonts Import - Retro Style Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Press+Start+2P&family=Bungee&display=swap');
        
        /* CSS Variables for Retro Colors */
        :root{
            --neon-pink: #ff0080;
            --neon-cyan: #00ffff;
            --neon-green: #39ff14;
            --neon-orange: #ff6600;
            --neon-purple: #bf00ff;
            --dark-bg: #0a0a0f;
            --card-bg: #1a0d2e;
            --grid-bg: #16001e;
            --light-text: #ffffff;
            --retro-yellow: #ffff00;
            --pink-glow: 0 0 20px var(--neon-pink), 0 0 40px var(--neon-pink), 0 0 60px var(--neon-pink);
            --cyan-glow: 0 0 20px var(--neon-cyan), 0 0 40px var(--neon-cyan);
            --green-glow: 0 0 15px var(--neon-green), 0 0 30px var(--neon-green);
        }
        
        /* Global Reset and Base Styles */
        *{
            padding: 0px;
            margin: 0px;
            box-sizing: border-box;
            font-family: "Orbitron", monospace;
        }
        
        /* Retro Scanline Effect */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(
                    90deg,
                    transparent 98%,
                    rgba(0, 255, 255, 0.03) 100%
                ),
                linear-gradient(
                    0deg,
                    transparent 98%,
                    rgba(255, 0, 128, 0.03) 100%
                );
            background-size: 3px 3px;
            pointer-events: none;
            z-index: 1000;
            opacity: 0.4;
        }
        
        /* Body Styling with Retro Background */
        body{
            background: 
                radial-gradient(circle at 25% 25%, rgba(255, 0, 128, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(57, 255, 20, 0.05) 0%, transparent 70%),
                linear-gradient(135deg, var(--dark-bg) 0%, #1a0d2e 50%, var(--dark-bg) 100%);
            color: var(--light-text);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }

        /* Retro Grid Pattern */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 0, 128, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
            z-index: -1;
            opacity: 0.3;
        }

        /* Shopping Cart Styles */
        .cart-container {
            position: relative;
        }

        /* Cart Button Styling - Retro Style */
        .cart-btn {
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            border: 2px solid var(--neon-cyan);
            border-radius: 8px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            color: white;
            position: relative;
            box-shadow: var(--pink-glow);
            text-shadow: 0 0 10px currentColor;
        }

        /* Cart Button Hover Effect */
        .cart-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--pink-glow), inset 0 0 20px rgba(0, 255, 255, 0.3);
            border-color: var(--neon-green);
        }

        /* Cart Item Count Badge */
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--neon-green);
            color: #000;
            border: 2px solid var(--neon-cyan);
            border-radius: 4px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            font-family: "Press Start 2P", cursive;
            min-width: 20px;
            transform: scale(0);
            transition: transform 0.3s ease;
            box-shadow: var(--green-glow);
        }

        /* Show Cart Count Badge */
        .cart-count.show {
            transform: scale(1);
        }

        /* Cart Dropdown Menu - Retro Style */
        .cart-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 350px;
            background: linear-gradient(145deg, var(--card-bg), var(--grid-bg));
            border: 3px solid var(--neon-cyan);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--cyan-glow), inset 0 0 20px rgba(255, 0, 128, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
            max-height: 400px;
            overflow-y: auto;
        }

        /* Show Cart Dropdown */
        .cart-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Cart Header Section */
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--neon-pink);
        }

        /* Cart Title */
        .cart-title {
            font-size: 16px;
            font-weight: bold;
            color: var(--neon-cyan);
            font-family: "Press Start 2P", cursive;
            text-shadow: 0 0 10px currentColor;
        }

        /* Cart Close Button */
        .cart-close {
            background: none;
            border: none;
            color: var(--neon-green);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-shadow: 0 0 10px currentColor;
        }

        /* Cart Close Button Hover */
        .cart-close:hover {
            transform: rotate(90deg);
            color: var(--neon-pink);
        }

        /* Cart Items Container */
        .cart-items {
            max-height: 200px;
            overflow-y: auto;
        }

        /* Individual Cart Item */
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 255, 255, 0.3);
        }

        /* Last Cart Item Border */
        .cart-item:last-child {
            border-bottom: none;
        }

        /* Cart Item Information */
        .cart-item-info {
            flex: 1;
        }

        /* Cart Item Name */
        .cart-item-name {
            font-weight: bold;
            color: white;
            margin-bottom: 2px;
            font-size: 12px;
        }

        /* Cart Item Price */
        .cart-item-price {
            color: var(--neon-green);
            font-size: 11px;
            font-family: "Press Start 2P", cursive;
        }

        /* Remove Item Button */
        .cart-item-remove {
            background: var(--neon-pink);
            border: 2px solid var(--neon-cyan);
            color: white;
            border-radius: 4px;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        /* Remove Item Button Hover */
        .cart-item-remove:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px var(--neon-pink);
        }

        /* Empty Cart Message */
        .cart-empty {
            text-align: center;
            color: var(--neon-cyan);
            padding: 30px;
            font-family: "Press Start 2P", cursive;
            font-size: 12px;
        }

        /* Cart Total Section */
        .cart-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--neon-pink);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Cart Total Label */
        .cart-total-label {
            font-weight: bold;
            font-size: 14px;
            font-family: "Press Start 2P", cursive;
        }

        /* Cart Total Amount */
        .cart-total-amount {
            font-weight: bold;
            font-size: 16px;
            color: var(--neon-green);
            font-family: "Press Start 2P", cursive;
            text-shadow: 0 0 10px currentColor;
        }

        /* Checkout Button */
        .cart-checkout {
            width: 100%;
            background: linear-gradient(145deg, var(--neon-green), var(--neon-cyan));
            border: 2px solid var(--neon-pink);
            color: black;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            font-family: "Press Start 2P", cursive;
            font-size: 10px;
        }

        /* Checkout Button Hover */
        .cart-checkout:hover {
            transform: translateY(-2px);
            box-shadow: var(--green-glow);
        }

        /* Sign-in Modal Overlay */
        .signin-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1500;
            backdrop-filter: blur(10px);
        }

        /* Active Sign-in Modal */
        .signin-modal.active {
            display: flex;
        }

        /* Sign-in Modal Card */
        .signin-card {
            background: linear-gradient(145deg, var(--card-bg), var(--grid-bg));
            border: 3px solid var(--neon-cyan);
            border-radius: 12px;
            padding: 40px;
            width: 400px;
            max-width: 90vw;
            text-align: center;
            box-shadow: var(--cyan-glow);
            animation: modalSlide 0.4s ease-out;
            position: relative;
        }

        /* Modal Slide Animation */
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

        /* Sign-in Modal Close Button */
        .signin-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            color: var(--neon-pink);
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-shadow: 0 0 10px currentColor;
        }

        /* Sign-in Modal Close Hover */
        .signin-close:hover {
            transform: rotate(90deg);
            color: var(--neon-green);
        }

        /* Sign-in Modal Logo */
        .signin-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            border: 3px solid var(--neon-cyan);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: var(--pink-glow);
        }

        /* Sign-in Modal Title */
        .signin-title {
            font-family: "Bungee", cursive;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(255, 0, 128, 0.5);
        }

        /* Sign-in Modal Subtitle */
        .signin-subtitle {
            color: var(--neon-green);
            margin-bottom: 30px;
            font-size: 12px;
            font-family: "Press Start 2P", cursive;
        }

        /* Sign-in Form */
        .signin-form {
            margin-bottom: 20px;
        }

        .signin-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: rgba(26, 13, 46, 0.8);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 6px;
            color: white;
            outline: none;
            font-family: "Orbitron", monospace;
        }

        .signin-input:focus {
            border-color: var(--neon-cyan);
            box-shadow: var(--cyan-glow);
        }

        .signin-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Sign-in Buttons Container */
        .signin-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        /* Sign-in Buttons */
        .signin-btn {
            padding: 12px 24px;
            border: 2px solid var(--neon-cyan);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            font-family: "Press Start 2P", cursive;
        }

        /* Primary Sign-in Button */
        .signin-btn.primary {
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            color: white;
        }

        /* Secondary Sign-in Button */
        .signin-btn.secondary {
            background: linear-gradient(145deg, var(--neon-cyan), var(--neon-green));
            color: black;
        }

        /* Sign-in Button Hover */
        .signin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px currentColor;
        }

        .user-info {
            color: var(--neon-cyan);
            font-size: 12px;
            margin-right: 10px;
            font-family: "Press Start 2P", cursive;
        }

        /* Search Results */
        .search-results {
            position: absolute;
            top: 45px;
            left: 0;
            right: 0;
            background: linear-gradient(145deg, var(--card-bg), var(--grid-bg));
            border: 3px solid var(--neon-cyan);
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1002;
            display: none;
            box-shadow: var(--cyan-glow);
        }

        .search-result-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-result-item:hover {
            background: rgba(255, 0, 128, 0.2);
            border-left: 4px solid var(--neon-pink);
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
            font-size: 12px;
            font-family: "Press Start 2P", cursive;
        }

        .search-result-price {
            color: var(--neon-green);
            font-weight: bold;
            font-family: "Orbitron", monospace;
        }

        /* Navigation Container */
        .container{
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 999;
            backdrop-filter: blur(15px);
            background: linear-gradient(90deg, rgba(10, 10, 15, 0.95), rgba(26, 13, 46, 0.95));
            position: fixed;
            width: 100%;
            top: 0;
            border-bottom: 3px solid var(--neon-cyan);
            padding: 1.5rem 5%;
            box-shadow: 0 5px 20px rgba(0, 255, 255, 0.3);
        }

        /* Navigation Links */
        .container a{
            text-decoration: none;
        }

        /* Logo Styling */
        .logo {
            font-family: "Bungee", cursive;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(255, 0, 128, 0.5);
            animation: logoGlow 2s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            from { filter: drop-shadow(0 0 5px rgba(255, 0, 128, 0.5)); }
            to { filter: drop-shadow(0 0 15px rgba(0, 255, 255, 0.7)); }
        }

        /* Navigation Buttons Container */
        .nav-buttons {
            display: flex;
            gap: 20px;
        }

        /* Navigation Button Links */
        .nav-buttons a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid transparent;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-weight: 600;
            font-size: 12px;
            font-family: "Press Start 2P", cursive;
            text-transform: uppercase;
        }

        /* Navigation Button Hover Effect */
        .nav-buttons a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 0, 128, 0.4), rgba(0, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }

        /* Navigation Button Hover Animation */
        .nav-buttons a:hover::before {
            left: 100%;
        }

        /* Navigation Button Hover State */
        .nav-buttons a:hover {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        /* Right Section of Navigation */
        .right-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* User Button */
        .user-btn {
            background: linear-gradient(145deg, var(--neon-cyan), var(--neon-green));
            border: 2px solid var(--neon-pink);
            border-radius: 8px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            color: black;
            box-shadow: var(--cyan-glow);
        }

        /* User Button Hover */
        .user-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--cyan-glow), inset 0 0 10px rgba(255, 0, 128, 0.3);
        }

        /* Search Container */
        .search-container {
            position: relative;
        }

        /* Search Input Box */
        .search-box {
            background: rgba(26, 13, 46, 0.8);
            border: 2px solid var(--neon-pink);
            border-radius: 6px;
            padding: 10px 20px;
            color: white;
            outline: none;
            width: 250px;
            transition: all 0.3s ease;
            font-family: "Orbitron", monospace;
        }

        /* Search Box Focus State */
        .search-box:focus {
            border-color: var(--neon-cyan);
            box-shadow: var(--cyan-glow);
            background: rgba(26, 13, 46, 0.95);
        }

        /* Search Box Placeholder */
        .search-box::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Platform/Hero Section */
        .platform{
            min-height: 100vh;
            padding: 4.5rem 5% 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Platform Background Animation */
        .platform::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(255, 0, 128, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(0, 255, 255, 0.1) 0%, transparent 50%);
            animation: retroPulse 4s ease-in-out infinite alternate;
            z-index: -1;
        }

        @keyframes retroPulse {
            0% { opacity: 0.5; transform: scale(1); }
            100% { opacity: 0.8; transform: scale(1.05); }
        }
        
        /* Platform Content Container */
        .platform-content{
            max-width: 600px;
        }
        
        /* Platform Main Heading */
        .platform h1{
            font-family: "Bungee", cursive;
            font-size: clamp(2.5rem, 5vw, 4rem);
            line-height: 1.2;
            margin-bottom: 1.45rem;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 0, 128, 0.5);
            animation: textGlow 3s ease-in-out infinite alternate;
        }

        @keyframes textGlow {
            from { filter: drop-shadow(0 0 5px rgba(255, 0, 128, 0.5)); }
            to { filter: drop-shadow(0 0 20px rgba(0, 255, 255, 0.8)); }
        }
        
        /* Platform Description */
        .platform p{
            font-size: 1.2rem;
            margin-bottom: 1.85rem;
            color: var(--light-text);
            line-height: 1.6;
            font-family: "Orbitron", monospace;
        }
        
        /* Platform Image Container */
        .platform-img{
            position: relative;
            border: 3px solid var(--neon-cyan);
            border-radius: 12px;
            overflow: hidden;
            height: 450px;
            width: 550px;
            box-shadow: var(--cyan-glow);
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-purple));
        }
        
        /* Platform Image */
        .platform-img img {
            border-radius: 9px;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
            filter: contrast(1.1) saturate(1.2);
        }
        
        /* Platform Image Hover Effect */
        .platform-img:hover img{
            transform: scale(1.05);
            filter: contrast(1.3) saturate(1.4) hue-rotate(10deg);
        }
        
        /* Buy Button */
        .buy-btn {
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            border: 3px solid var(--neon-cyan);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 40px 0 0 0;
            font-size: 14px;
            box-shadow: var(--pink-glow);
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: "Press Start 2P", cursive;
            animation: buttonPulse 2s ease-in-out infinite alternate;
        }

        @keyframes buttonPulse {
            from { box-shadow: var(--pink-glow); }
            to { box-shadow: var(--pink-glow), 0 0 30px rgba(0, 255, 255, 0.4); }
        }

        /* Buy Button Hover */
        .buy-btn:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: var(--pink-glow), 0 10px 30px rgba(255, 0, 128, 0.5);
        }

        /* Games Section Container */
        .games-section {
            padding: 6rem 5%;
            background: linear-gradient(135deg, var(--dark-bg), var(--card-bg), var(--dark-bg));
            position: relative;
        }

        /* Games Section Background Pattern */
        .games-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 0, 128, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(0, 255, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 10%, rgba(57, 255, 20, 0.08) 0%, transparent 60%);
            z-index: -1;
        }

        /* Games Section Title */
        .games-section h2 {
            text-align: center;
            font-family: "Bungee", cursive;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(255, 0, 128, 0.5);
            animation: titleGlow 4s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { filter: drop-shadow(0 0 10px rgba(255, 0, 128, 0.5)); }
            to { filter: drop-shadow(0 0 25px rgba(0, 255, 255, 0.7)); }
        }

        /* Games Container */
        .games-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            overflow: visible;
            padding: 20px 0;
        }

        /* Games Slider Wrapper */
        .games-slider {
            overflow: visible;
            border-radius: 12px;
            position: relative;
        }

        /* Games Grid Container */
        .games-grid {
            display: flex;
            transition: transform 0.5s ease;
            gap: 1.5rem;
        }

        /* Individual Game Card */
        .game-card {
            background: linear-gradient(145deg, var(--card-bg), var(--grid-bg));
            border: 2px solid var(--neon-cyan);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7), var(--cyan-glow);
            transition: all 0.3s ease;
            position: relative;
            flex: 0 0 220px;
            height: 320px;
            display: flex;
            flex-direction: column;
        }

        /* Game Card Hover Effect */
        .game-card:hover {
            transform: translateY(-10px) scale(1.03);
            border-color: var(--neon-pink);
            box-shadow: var(--pink-glow), 0 20px 40px rgba(0, 0, 0, 0.8);
            z-index: 10;
            position: relative;
        }

        /* Game Card Image Section */
        .game-card-image {
            width: 100%;
            height: 120px;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan), var(--neon-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            position: relative;
            overflow: hidden;
            border-bottom: 2px solid var(--neon-green);
        }

        /* Game Card Image Hover Animation */
        .game-card-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(57, 255, 20, 0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        /* Game Card Image Hover Animation Trigger */
        .game-card:hover .game-card-image::before {
            transform: translateX(100%);
        }

        /* Game Card Content Container */
        .game-card-content {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            height: 200px;
            background: linear-gradient(180deg, var(--card-bg), var(--grid-bg));
        }

        /* Game Card Title */
        .game-card-title {
            font-size: 11px;
            font-weight: bold;
            color: var(--neon-cyan);
            margin-bottom: 0.3rem;
            text-shadow: 0 0 8px currentColor;
            height: 28px;
            display: flex;
            align-items: center;
            font-family: "Press Start 2P", cursive;
        }

        /* Game Card Price */
        .game-card-price {
            font-size: 14px;
            font-weight: bold;
            color: var(--neon-green);
            margin-bottom: 0.8rem;
            text-shadow: 0 0 8px currentColor;
            height: 30px;
            display: flex;
            align-items: center;
            font-family: "Orbitron", monospace;
        }

        /* Game Card Description */
        .game-card-description {
            color: #c0c0c0;
            line-height: 1.4;
            font-size: 0.8rem;
            height: 56px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            margin-bottom: 1rem;
            font-family: "Orbitron", monospace;
        }

        /* Game Card Add to Cart Button */
        .game-card-btn {
            width: 100%;
            background: linear-gradient(145deg, var(--neon-green), var(--neon-cyan));
            border: 2px solid var(--neon-pink);
            color: black;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 9px;
            margin-top: auto;
            font-family: "Press Start 2P", cursive;
            text-transform: uppercase;
        }

        /* Game Card Button Hover Effect */
        .game-card-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--green-glow);
        }

        /* Slider Navigation Container */
        .slider-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }

        /* Slider Navigation Buttons */
        .slider-btn {
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            border: 2px solid var(--neon-cyan);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--pink-glow);
        }

        /* Slider Button Hover */
        .slider-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--pink-glow), inset 0 0 10px rgba(0, 255, 255, 0.3);
        }

        /* Disabled Slider Button */
        .slider-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Slider Dots Container */
        .slider-dots {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Individual Slider Dot */
        .dot {
            width: 12px;
            height: 12px;
            border: 2px solid var(--neon-cyan);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Active Slider Dot */
        .dot.active {
            background: var(--neon-pink);
            box-shadow: 0 0 10px var(--neon-pink);
            border-color: var(--neon-green);
        }

        /* Notification Popup */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(145deg, var(--neon-pink), var(--neon-purple));
            color: white;
            padding: 15px 25px;
            border: 2px solid var(--neon-cyan);
            border-radius: 8px;
            box-shadow: var(--pink-glow);
            z-index: 2000;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.5s ease;
            font-weight: bold;
            min-width: 250px;
            text-align: center;
            font-family: "Press Start 2P", cursive;
            font-size: 10px;
        }

        /* Show Notification */
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        /* Notification Icon */
        .notification::before {
            content: '🔥';
            display: inline-block;
            margin-right: 8px;
            font-size: 1.2em;
            background: rgba(57, 255, 20, 0.2);
            border: 1px solid var(--neon-green);
            border-radius: 4px;
            width: 24px;
            height: 24px;
            line-height: 22px;
            text-align: center;
        }

        /* Price Styling */
        #h2 {
            color: var(--neon-green);
            font-size: 1.8rem;
            text-shadow: 0 0 15px currentColor;
            font-family: "Orbitron", monospace;
            animation: priceGlow 3s ease-in-out infinite alternate;
        }

        @keyframes priceGlow {
            from { text-shadow: 0 0 15px var(--neon-green); }
            to { text-shadow: 0 0 25px var(--neon-green), 0 0 35px var(--neon-cyan); }
        }

        /* Retro Console Styling */
        .retro-console-name {
            font-family: "Bungee", cursive;
            background: linear-gradient(45deg, var(--neon-pink), var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-buttons {
                order: 2;
                display: none;
            }

            .right-section {
                order: 1;
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

            .platform {
                flex-direction: column;
                text-align: center;
                padding: 5rem 5% 3rem;
            }
            
            .platform-img {
                width: 100%;
                max-width: 400px;
                height: 300px;
                margin-top: 2rem;
            }
            
            .games-grid {
                justify-content: center;
            }
            
            .game-card {
                flex: 0 0 280px;
            }
            
            .cart-dropdown {
                width: 300px;
                right: -50px;
            }
        }
      </style>
</head>
<body>
    <!-- Sign-in Modal Dialog -->
    <div class="signin-modal" id="signinModal">
        <div class="signin-card">
            <button class="signin-close" onclick="closeSigninModal()">&times;</button>
            <div class="signin-logo">🕹️</div>
            <div class="signin-title">ENTER THE GRID</div>
            <div class="signin-subtitle">Join the retro revolution</div>
            
            <form class="signin-form" id="loginForm">
                <input type="email" class="signin-input" id="loginEmail" placeholder="Email" required>
                <input type="password" class="signin-input" id="loginPassword" placeholder="Password" required>
                <button type="submit" class="signin-btn primary" style="width: 100%;">LOG IN</button>
            </form>
            
            <div class="signin-buttons">
                <button class="signin-btn secondary" onclick="redirectToSignup()">Sign UP</button>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation Bar -->
    <nav class="container">
        <div class="logo">RETROPIXEL</div>
        <div class="nav-buttons">
            <a href="../Secoand-Page.php">HOME</a>
            <a href="#games">GAMES</a>
            <a href="#home">RETRO</a>
        </div>
        <div class="right-section">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">PLAYER: <?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                <button class="user-btn" onclick="logout()" title="Logout">🚪</button>
            <?php else: ?>
                <button class="user-btn" onclick="showSigninModal()">👾</button>
            <?php endif; ?>
            
            <div class="cart-container">
                <button class="cart-btn" onclick="toggleCart()">
                    🛒
                    <div class="cart-count" id="cartCount"><?php echo $cart_count; ?></div>
                </button>
                
                <div class="cart-dropdown" id="cartDropdown">
                    <div class="cart-header">
                        <div class="cart-title">CART</div>
                        <button class="cart-close" onclick="closeCart()">&times;</button>
                    </div>
                    
                    <div id="cartContent">
                        <div class="cart-empty" id="cartEmpty">
                            CART EMPTY 🚀
                        </div>
                        
                        <div class="cart-items" id="cartItems" style="display: none;">
                        </div>
                        
                        <div class="cart-total" id="cartTotal" style="display: none;">
                            <div class="cart-total-label">TOTAL:</div>
                            <div class="cart-total-amount" id="cartTotalAmount">$0.00</div>
                        </div>
                        
                        <button class="cart-checkout" id="cartCheckout" style="display: none;" onclick="checkout()">
                            CHECKOUT
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="search-container">
                <input type="text" class="search-box" id="searchBox" placeholder="Search retro games..." onkeyup="searchGames(this.value)" autocomplete="off">
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>
    </nav>
    
    <!-- Hero/Platform Section -->
    <section class="platform" id="home">
        <div class="platform-content">
            <h1 class="retro-console-name">RETRO ARCADE</h1>
            <p>Experience the golden age of gaming with authentic retro consoles and classic arcade games. Relive the 8-bit and 16-bit era that defined gaming culture forever.</p>
            <h2 id="h2">ONLY $199.99</h2>
            <br>
            <button class="buy-btn" onclick="addToCart('Retro Arcade Console', 199.99)">ADD TO CART</button>
        </div>
        <div class="platform-img">
            <img src="https://pctechmag.com/wp-content/uploads/2024/01/retro.jpg" alt="Retro Gaming Console" style="width: 100%; height: 100%; object-fit: cover; border-radius: 9px;">
        </div>
    </section>

    <!-- Games Gallery Section -->
    <section class="games-section" id="games">
        <h2>CLASSIC RETRO GAMES</h2>
        <div class="games-container">
            <div class="games-slider">
                <div class="games-grid" id="gamesGrid">
                    <?php foreach ($retro_games as $game): ?>
                    <div class="game-card">
                        <div class="game-card-image"><?php echo $game['emoji']; ?></div>
                        <div class="game-card-content">
                            <div class="game-card-title"><?php echo htmlspecialchars($game['game_name']); ?></div>
                            <div class="game-card-price">$<?php echo number_format($game['price'], 2); ?></div>
                            <div class="game-card-description"><?php echo htmlspecialchars($game['description']); ?></div>
                            <button class="game-card-btn" onclick="addToCart('<?php echo addslashes($game['game_name']); ?>', <?php echo $game['price']; ?>)">ADD TO CART</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="slider-nav">
                <button class="slider-btn" id="prevBtn" onclick="moveSlider(-1)">‹</button>
                <div class="slider-dots" id="sliderDots"></div>
                <button class="slider-btn" id="nextBtn" onclick="moveSlider(1)">›</button>
            </div>
        </div>
    </section>

    <div class="notification" id="notification"></div>

    <script>
        let cart = [];
        let searchTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            loadUserCart();
            updateCartDisplay();
            initializeSlider();
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

        function logout() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=logout'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
            });
        }

        function addToCart(gameName, price) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add_to_cart&game_name=' + encodeURIComponent(gameName) + '&price=' + price
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUserCart();
                    showNotification(gameName + ' added to cart!');
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove_from_cart&cart_id=' + cartId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) loadUserCart();
            })
            .catch(error => console.error('Error:', error));
        }

        function loadUserCart() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cart = data.items || [];
                    updateCartDisplay();
                }
            })
            .catch(error => console.error('Error loading cart:', error));
        }

        function updateCartDisplay() {
            const cartCount = document.getElementById('cartCount');
            const cartEmpty = document.getElementById('cartEmpty');
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const cartCheckout = document.getElementById('cartCheckout');
            const cartTotalAmount = document.getElementById('cartTotalAmount');

            const totalItems = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
            cartCount.textContent = totalItems;
            cartCount.classList.toggle('show', totalItems > 0);

            if (cart.length === 0) {
                cartEmpty.style.display = 'block';
                cartItems.style.display = 'none';
                cartTotal.style.display = 'none';
                cartCheckout.style.display = 'none';
            } else {
                cartEmpty.style.display = 'none';
                cartItems.style.display = 'block';
                cartTotal.style.display = 'flex';
                cartCheckout.style.display = 'block';

                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.game_name}</div>
                            <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)} × ${item.quantity}</div>
                        </div>
                        <button class="cart-item-remove" onclick="removeFromCart(${item.id})">×</button>
                    </div>
                `).join('');

                const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
                cartTotalAmount.textContent = `$${total.toFixed(2)}`;
            }
        }

        function searchGames(query) {
            const searchResults = document.getElementById('searchResults');
            
            clearTimeout(searchTimeout);
            
            if (!query || query.trim().length === 0) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
                return;
            }
            
            query = query.trim();
            
            searchTimeout = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=search&search_term=' + encodeURIComponent(query)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.results && data.results.length > 0) {
                        searchResults.innerHTML = data.results.map(game => `
                            <div class="search-result-item" onclick="addToCart('${game.name.replace(/'/g, "\\'")}', ${game.price}); closeSearchResults();">
                                <div class="search-result-info">
                                    <span class="search-result-emoji">${game.emoji}</span>
                                    <span class="search-result-name">${game.name}</span>
                                </div>
                                <span class="search-result-price">$${game.price.toFixed(2)}</span>
                            </div>
                        `).join('');
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item" style="pointer-events: none;">NO GAMES FOUND</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="search-result-item" style="pointer-events: none;">SEARCH ERROR</div>';
                    searchResults.style.display = 'block';
                });
            }, 200);
        }

        function closeSearchResults() {
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('searchBox').value = '';
        }

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

        function toggleCart() {
            document.getElementById('cartDropdown').classList.toggle('show');
        }

        function closeCart() {
            document.getElementById('cartDropdown').classList.remove('show');
        }

        function checkout() {
            if (cart.length === 0) return;
            
            const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
            alert(`GAME OVER! TOTAL SCORE: $${total.toFixed(2)}\nThanks for playing! 🕹️`);
            
            cart.forEach(item => removeFromCart(item.id));
            closeCart();
        }

        function showSigninModal() {
            document.getElementById("signinModal").classList.add("active");
        }

        function closeSigninModal() {
            document.getElementById("signinModal").classList.remove("active");
        }

        function redirectToSignup() {
            window.location.href = 'index.php';
        }

        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        let currentSlide = 0;
        const cardsPerView = 4;
        const totalCards = document.querySelectorAll('.game-card').length;
        const maxSlides = Math.ceil(totalCards / cardsPerView);

        function initializeSlider() {
            createDots();
            updateSlider();
        }

        function createDots() {
            const dotsContainer = document.getElementById('sliderDots');
            dotsContainer.innerHTML = '';
            
            for (let i = 0; i < maxSlides; i++) {
                const dot = document.createElement('div');
                dot.className = 'dot';
                dot.onclick = () => goToSlide(i);
                dotsContainer.appendChild(dot);
            }
        }

        function moveSlider(direction) {
            currentSlide += direction;
            
            if (currentSlide < 0) currentSlide = 0;
            if (currentSlide >= maxSlides) currentSlide = maxSlides - 1;
            
            updateSlider();
        }

        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            updateSlider();
        }

        function updateSlider() {
            const gamesGrid = document.getElementById('gamesGrid');
            
            const translateX = -(currentSlide * (220 + 24) * cardsPerView);
            gamesGrid.style.transform = `translateX(${translateX}px)`;
            
            const dots = document.querySelectorAll('.dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
            
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            prevBtn.disabled = currentSlide === 0;
            nextBtn.disabled = currentSlide >= maxSlides - 1;
        }
    </script>
</body>
</html>