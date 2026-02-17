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
            // For Xbox Series X console, don't check game stock
            if ($game_name !== 'Xbox Series X') {
                // Check if item is in stock for games
                $stmt = $conn->prepare("SELECT id, quantity FROM xbox_games WHERE game_name = ?");
                $stmt->bind_param("s", $game_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($game = $result->fetch_assoc()) {
                    if ($game['quantity'] <= 0) {
                        throw new Exception("This item is out of stock");
                    }
                    
                    // Decrement stock
                    $stmt = $conn->prepare("UPDATE xbox_games SET quantity = quantity - 1 WHERE game_name = ? AND quantity > 0");
                    $stmt->bind_param("s", $game_name);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows === 0) {
                        throw new Exception("Failed to update stock");
                    }
                } else {
                    throw new Exception("Game not found");
                }
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
                // Only restore stock if it's not Xbox Series X console
                if ($cart_item['game_name'] !== 'Xbox Series X') {
                    $stmt = $conn->prepare("UPDATE xbox_games SET quantity = quantity + ? WHERE game_name = ?");
                    $stmt->bind_param("is", $cart_item['quantity'], $cart_item['game_name']);
                    $stmt->execute();
                }
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
        
        if (empty($search_term)) {
            echo json_encode(['success' => true, 'results' => []]);
            exit;
        }
        
        // Search in database for games
        $stmt = $conn->prepare("SELECT game_name as name, price, emoji FROM xbox_games WHERE game_name LIKE ? AND quantity > 0 ORDER BY game_name LIMIT 10");
        $search_param = "%" . $search_term . "%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'name' => $row['name'],
                'price' => floatval($row['price']),
                'emoji' => $row['emoji']
            ];
        }
        
        // Also search for Xbox Series X console if it matches
        if (stripos('Xbox Series X', $search_term) !== false) {
            array_unshift($results, [
                'name' => 'Xbox Series X',
                'price' => 499.99,
                'emoji' => '🎮'
            ]);
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

// Get Xbox games for display (excluding Xbox Series X console)
$xbox_games = [];
$stmt = $conn->prepare("SELECT id, game_name, price, emoji, description FROM xbox_games WHERE quantity > 0 AND game_name != 'Xbox Series X' ORDER BY game_name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $xbox_games[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xbox Series X Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<style>
    /* Google Fonts Import */
        @import url('https://fonts.googleapis.com/css2?family=Bitcount+Grid+Double:wght@100..900&family=Libertinus+Sans:ital,wght@0,400;0,700;1,400&family=Mozilla+Headline:wght@200..700&family=Orbitron:wght@700&family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap');
        
        /* CSS Variables for Xbox Colors */
        :root{
            --xbox-green:#0e7a0d;
            --xbox-light-green:#6bb26a;
            --xbox-dark-green:#004c00;
            --dark-bg:#0d0d0d;
            --light-text:#ffffff;
            --glow-shadow: 0 0 10px var(--xbox-green) , 0 0 20px var(--xbox-green) , 0px 0px 40px var(--xbox-green);
        }
        
        /* Global Reset and Base Styles */
        *{
            padding: 0px;
            margin: 0px;
            box-sizing: border-box;
            font-family:"Roboto Mono";
        }
        
        /* Body Styling */
        body{
            background-color: var(--dark-bg);
            color: var(--light-text);
            overflow-x: hidden;
        }

        /* Shopping Cart Styles */
        .cart-container {
            position: relative;
        }

        /* Cart Button Styling */
        .cart-btn {
            background: linear-gradient(145deg, #0e7a0d, #004c00);
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

        /* Cart Button Hover Effect */
        .cart-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 20px rgba(14, 122, 13, 0.4);
        }

        /* Cart Item Count Badge */
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

        /* Show Cart Count Badge */
        .cart-count.show {
            transform: scale(1);
        }

        /* Cart Dropdown Menu */
        .cart-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 350px;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(14, 122, 13, 0.3);
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
            border-bottom: 1px solid rgba(14, 122, 13, 0.3);
        }

        /* Cart Title */
        .cart-title {
            font-size: 18px;
            font-weight: bold;
            color: #0e7a0d;
        }

        /* Cart Close Button */
        .cart-close {
            background: none;
            border: none;
            color: #ff4757;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        /* Cart Close Button Hover */
        .cart-close:hover {
            transform: rotate(90deg);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
        }

        /* Cart Item Price */
        .cart-item-price {
            color: #0e7a0d;
            font-size: 14px;
        }

        /* Remove Item Button */
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

        /* Remove Item Button Hover */
        .cart-item-remove:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        /* Empty Cart Message */
        .cart-empty {
            text-align: center;
            color: #a0a0a0;
            padding: 30px;
        }

        /* Cart Total Section */
        .cart-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(14, 122, 13, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Cart Total Label */
        .cart-total-label {
            font-weight: bold;
            font-size: 16px;
        }

        /* Cart Total Amount */
        .cart-total-amount {
            font-weight: bold;
            font-size: 18px;
            color: #0e7a0d;
        }

        /* Checkout Button */
        .cart-checkout {
            width: 100%;
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        /* Checkout Button Hover */
        .cart-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 122, 13, 0.4);
        }

        /* Sign-in Modal Overlay */
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

        /* Active Sign-in Modal */
        .signin-modal.active {
            display: flex;
        }

        /* Sign-in Modal Card */
        .signin-card {
            background: linear-gradient(145deg, #2a2a3e, #1e1e32);
            border: 2px solid #0e7a0d;
            border-radius: 20px;
            padding: 40px;
            width: 400px;
            max-width: 90vw;
            text-align: center;
            box-shadow: 0 20px 60px rgba(14, 122, 13, 0.3);
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
            color: #0e7a0d;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Sign-in Modal Close Hover */
        .signin-close:hover {
            transform: rotate(90deg);
            color: #ff4757;
        }

        /* Sign-in Modal Logo */
        .signin-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(14, 122, 13, 0.4);
        }

        /* Sign-in Modal Title */
        .signin-title {
            font-size: 28px;
            font-weight: bold;
            color: #0e7a0d;
            margin-bottom: 10px;
        }

        /* Sign-in Modal Subtitle */
        .signin-subtitle {
            color: #a0a0a0;
            margin-bottom: 30px;
        }

        /* Sign-in Form */
        .signin-form {
            margin-bottom: 20px;
        }

        .signin-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(14, 122, 13, 0.3);
            border-radius: 10px;
            color: white;
            outline: none;
        }

        .signin-input:focus {
            border-color: #0e7a0d;
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
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        /* Primary Sign-in Button */
        .signin-btn.primary {
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            color: white;
        }

        /* Secondary Sign-in Button */
        .signin-btn.secondary {
            background: transparent;
            color: #0e7a0d;
            border: 2px solid #0e7a0d;
        }

        /* Sign-in Button Hover */
        .signin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 122, 13, 0.3);
        }

        .user-info {
            color: #0e7a0d;
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
            border: 2px solid rgba(14, 122, 13, 0.3);
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
            background: rgba(14, 122, 13, 0.1);
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
            color: #0e7a0d;
            font-weight: bold;
        }

        /* Navigation Container */
        .container{
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 999;
            backdrop-filter: blur(10px);
            background-color: rgba(13, 13, 13, 0.9);
            position: fixed;
            width: 100%;
            top: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 5%;
        }

        /* Navigation Links */
        .container a{
            text-decoration: none;
        }

        /* Logo Styling */
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(45deg, #0e7a0d, #6bb26a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        /* Navigation Button Hover Effect */
        .nav-buttons a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(14, 122, 13, 0.3), transparent);
            transition: left 0.5s ease;
        }

        /* Navigation Button Hover Animation */
        .nav-buttons a:hover::before {
            left: 100%;
        }

        /* Navigation Button Hover State */
        .nav-buttons a:hover {
            background: rgba(14, 122, 13, 0.2);
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
            background: linear-gradient(145deg, #0e7a0d, #004c00);
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

        /* User Button Hover */
        .user-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 20px rgba(14, 122, 13, 0.4);
        }

        /* Search Container */
        .search-container {
            position: relative;
        }

        /* Search Input Box */
        .search-box {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(14, 122, 13, 0.3);
            border-radius: 25px;
            padding: 10px 20px;
            color: white;
            outline: none;
            width: 250px;
            transition: all 0.3s ease;
        }

        /* Search Box Focus State */
        .search-box:focus {
            border-color: #0e7a0d;
            box-shadow: 0 0 20px rgba(14, 122, 13, 0.3);
            background: rgba(255, 255, 255, 0.15);
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
        
        /* Platform Content Container */
        .platform-content{
            max-width: 600px;
        }
        
        /* Platform Main Heading */
        .platform h1{
            font-size: clamp(2.5rem ,  5vw , 4rem);
            line-height: 1.2;
            margin-bottom: 1.45rem;
            color: var(--xbox-green);
        }
        
        /* Platform Description */
        .platform p{
            font-size: 1.3rem;
            margin-bottom: 1.85rem;
            color: var(--light-text);
        }
        
        /* Platform Image Container */
        .platform-img{
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            height: 450px;
            width: 550px;
            box-shadow: var(--glow-shadow);
        }
        
        /* Platform Image */
        .platform-img img {
            border-radius: 20px;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        /* Platform Image Hover Effect */
        .platform-img:hover img{
            transform: scale(1.05);
        }
        
        /* Buy Button */
        .buy-btn {
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            margin:40px 0 0 0;
            font-size: 1.2rem;
            box-shadow: 0 10px 20px rgba(14, 122, 13, 0.3);
            display: inline-block;
        }

        /* Buy Button Hover */
        .buy-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(14, 122, 13, 0.4);
        }

        /* Games Section Container */
        .games-section {
            padding: 6rem 5%;
            background: linear-gradient(135deg, #0d0d0d, #1a2e1a);
        }

        /* Games Section Title */
        .games-section h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: var(--xbox-green);
            text-shadow: 0 0 20px rgba(14, 122, 13, 0.5);
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
            border-radius: 20px;
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
            background: rgba(26, 46, 26, 0.8);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            flex: 0 0 220px;
            height: 320px;
            display: flex;
            flex-direction: column;
        }

        /* Game Card Hover Effect */
        .game-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: #0e7a0d;
            box-shadow: 0 15px 35px rgba(14, 122, 13, 0.3);
            z-index: 10;
            position: relative;
        }

        /* Game Card Image Section */
        .game-card-image {
            width: 100%;
            height: 120px;
            background: linear-gradient(45deg, #1a2e1a, #16213e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Game Card Image Hover Animation */
        .game-card-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(14, 122, 13, 0.1), transparent);
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
        }

        /* Game Card Title */
        .game-card-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #0e7a0d;
            margin-bottom: 0.3rem;
            text-shadow: 0 0 8px rgba(14, 122, 13, 0.5);
            height: 28px;
            display: flex;
            align-items: center;
        }

        /* Game Card Price */
        .game-card-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 0.8rem;
            text-shadow: 0 0 8px rgba(255, 107, 107, 0.5);
            height: 30px;
            display: flex;
            align-items: center;
        }

        /* Game Card Description */
        .game-card-description {
            color: #a0a0a0;
            line-height: 1.4;
            font-size: 0.85rem;
            height: 56px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            margin-bottom: 1rem;
        }

        /* Game Card Add to Cart Button */
        .game-card-btn {
            width: 100%;
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            margin-top: auto;
        }

        /* Game Card Button Hover Effect */
        .game-card-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 122, 13, 0.4);
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
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Slider Button Hover */
        .slider-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(14, 122, 13, 0.4);
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
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Active Slider Dot */
        .dot.active {
            background: #0e7a0d;
            box-shadow: 0 0 10px rgba(14, 122, 13, 0.5);
        }

        /* Notification Popup */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(145deg, #0e7a0d, #004c00);
            color: white;
            padding: 15px 25px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(14, 122, 13, 0.4);
            z-index: 2000;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.5s ease;
            border: 2px solid rgba(255, 255, 255, 0.2);
            font-weight: bold;
            min-width: 250px;
            text-align: center;
        }

        /* Show Notification */
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        /* Notification Checkmark Icon */
        .notification::before {
            content: '✓';
            display: inline-block;
            margin-right: 8px;
            font-size: 1.2em;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
        }

        /* Cart Notification */
        .cart-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(145deg, #0e7a0d, #004c00);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
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

            .games-grid {
                flex-wrap: wrap;
                gap: 30px;
            }
            
            .game-card {
                width: 120px;
                height: 120px;
                font-size: 16px;
            }

            .platform {
                flex-direction: column;
                text-align: center;
                gap: 2rem;
            }

            .platform-img {
                width: 90%;
                height: 300px;
            }

            .cart-dropdown {
                width: 300px;
                right: -50px;
            }
        }
  </style>
<body>
    <!-- Cart Notification -->
    <!-- <div class="cart-notification" id="cartNotification">
        Game added to cart! 🎮
    </div> -->

    <!-- Sign-in Modal Dialog -->
    <div class="signin-modal" id="signinModal">
        <div class="signin-card">
            <button class="signin-close" onclick="closeSigninModal()">&times;</button>
            <div class="signin-logo">🎮</div>
            <div class="signin-title">Welcome Back!</div>
            <div class="signin-subtitle">Join the ultimate Xbox gaming community</div>
            
            <form class="signin-form" id="loginForm">
                <input type="email" class="signin-input" id="loginEmail" placeholder="Email" required>
                <input type="password" class="signin-input" id="loginPassword" placeholder="Password" required>
                <button type="submit" class="signin-btn primary" style="width: 100%;">Sign In</button>
            </form>
            
            <div class="signin-buttons">
                <button class="signin-btn secondary" onclick="window.location.href='index.php'">Sign UP</button>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation Bar -->
    <nav class="container">
        <div class="logo">Xbox Store</div>
        <div class="nav-buttons">
            <a href="../Secoand-Page.php">Home</a>
            <a href="#games">Games</a>
            <a href="#consol">Consoles</a>
            <a href="retro.php">Retro</a>
        </div>
        <div class="right-section">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</div>
                <button class="user-btn" onclick="logout()" title="Logout">🚪</button>
            <?php else: ?>
                <button class="user-btn" onclick="showSigninModal()">👤</button>
            <?php endif; ?>
            
            <div class="cart-container">
                <button class="cart-btn" onclick="toggleCart()">
                    🛒
                    <div class="cart-count" id="cartCount"><?php echo $cart_count; ?></div>
                </button>
                
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
    </nav>
    
    <!-- Hero/Platform Section -->
    <section class="platform" id="consol">
        <div class="platform-content">
            <h1>Power Your Dreams</h1>
            <p>Experience gaming at its best with the most powerful Xbox ever. Discover worlds beyond imagination with lightning-fast loading and stunning 4K visuals.</p>
            <h2 id="h2">just for $499.99</h2>
            <br>
            <button class="buy-btn" onclick="addToCart('Xbox Series X', 499.99)">Add to Cart</button>
        </div>
        <div class="platform-img">
            <img src="https://images.pexels.com/photos/14541069/pexels-photo-14541069.jpeg?cs=srgb&dl=pexels-alan-quirv%C3%A1n-14541069.jpg&fm=jpg" alt="Xbox Series X Console" style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px;">
        </div>
    </section>

    <!-- Games Gallery Section -->
    <section class="games-section" id="games">
        <h2>Xbox Games</h2>
        <div class="games-container">
            <div class="games-slider">
                <div class="games-grid" id="gamesGrid">
                    <?php foreach ($xbox_games as $game): ?>
                    <div class="game-card">
                        <div class="game-card-image"><?php echo $game['emoji']; ?></div>
                        <div class="game-card-content">
                            <div class="game-card-title"><?php echo htmlspecialchars($game['game_name']); ?></div>
                            <div class="game-card-price">$<?php echo number_format($game['price'], 2); ?></div>
                            <div class="game-card-description"><?php echo htmlspecialchars($game['description']); ?></div>
                            <button class="game-card-btn" onclick="addToCart('<?php echo addslashes($game['game_name']); ?>', <?php echo $game['price']; ?>)">Add to Cart</button>
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

    <!-- Notification Toast -->
    <div class="notification" id="notification"></div>

    <script>
        // Cart Array to Store Items (for local display)
        let cart = [];
        let searchTimeout;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadUserCart();
            updateCartDisplay();
            initializeSlider();
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

        // Function to Add Item to Cart (PHP Integration)
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
                    showNotification(gameName + ' added to cart!');
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
                // alert('Failed to add item to cart');
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
                } else {
                    alert(data.message || 'Error removing item');
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

        // Function to Update Cart Display
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
                            <div class="cart-item-price">${parseFloat(item.price).toFixed(2)} × ${item.quantity}</div>
                        </div>
                        <button class="cart-item-remove" onclick="removeFromCart(${item.id})">×</button>
                    </div>
                `).join('');

                const total = cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.quantity)), 0);
                cartTotalAmount.textContent = `$${total.toFixed(2)}`;
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
            alert(`Thank you for your purchase! Total: $${total.toFixed(2)}`);
            
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
                            <div class="search-result-item" onclick="addToCart('${game.name.replace(/'/g, "\\'")}', ${game.price})">
                                <div class="search-result-info">
                                    <span class="search-result-emoji">${game.emoji}</span>
                                    <span class="search-result-name">${game.name}</span>
                                </div>
                                <span class="search-result-price">$${game.price.toFixed(2)}</span>
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

        // Close dropdowns when clicking outside
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

        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
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

        // Slider Configuration Variables
        let currentSlide = 0;
        const cardsPerView = 4;
        let totalCards = 0;
        let maxSlides = 0;

        // Function to Initialize Slider
        function initializeSlider() {
            totalCards = document.querySelectorAll('.game-card').length;
            maxSlides = Math.ceil(totalCards / cardsPerView);
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