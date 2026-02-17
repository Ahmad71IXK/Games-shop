<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Web_project";

$error_message = "";
$success_message = "";

// Function to validate input
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password strength
function validatePassword($password) {
    // At least 8 characters, one uppercase, one lowercase, one number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        $error_message = "Connection failed. Please try again later.";
    } else {
        // Get form data
        $first_name = validateInput($_POST["first_name"]);
        $email = validateInput($_POST["email"]);
        $user_password = $_POST["password"];
        $repeat_password = $_POST["repeat_password"];
        
        $errors = array();
        
        // Validate first name
        if (empty($first_name)) {
            $errors[] = "First name is required";
        } elseif (strlen($first_name) < 2) {
            $errors[] = "First name must be at least 2 characters long";
        } elseif (!preg_match("/^[a-zA-Z\s]+$/", $first_name)) {
            $errors[] = "First name can only contain letters and spaces";
        }
        
        // Validate email
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!validateEmail($email)) {
            $errors[] = "Invalid email format";
        }
        
        // Validate password
        if (empty($user_password)) {
            $errors[] = "Password is required";
        } elseif (!validatePassword($user_password)) {
            $errors[] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number";
        }
        
        // Check if passwords match
        if ($user_password !== $repeat_password) {
            $errors[] = "Passwords do not match";
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "An account with this email already exists";
            }
            $stmt->close();
        }
        
        // If no errors, register the user
        if (empty($errors)) {
            // Hash the password
            $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
            
            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO users (first_name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $first_name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
                // Clear form data after successful registration
                $first_name = $email = "";
            } else {
                $error_message = "Registration failed. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = implode(", ", $errors);
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Page</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bitcount+Grid+Double:wght@100..900&family=Orbitron:wght@400..900&display=swap');
        *{
            padding: 0;
            margin: 0;
        }
        html{
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 1.20rem;
            color: #c770f0;
            text-align: center;
        }
        body{
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460, #2980b9, #ff8c42);
            background-size: cover;
            background-position:center ;
            overflow: hidden;
            display: flex;
            width: 100vw;
            height: 100vh;
        }
        #wrapper{
            box-sizing: border-box;
        background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460, #2980b9, #ff8c42);
        width: 50%;
        height: 100vh;
        padding: 11.5px;
        border-radius: 0px 15px 15px 0px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
        }
        h3{
            font-size: 2rem;
            font-weight:880;
            text-transform: uppercase;
            color: #ff8c42;
        }
        form{
            width: min(425px , 100%);
            margin-top: 2.5px;
            margin-bottom: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        form > div{
            width: 100%;
            display: flex;
            justify-content: center;
        }
        form label{
            flex-shrink:0px;
            width: 50px;
            height: 50px;
            background-color: #28376b;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 5px 0px 0px 5px;
        }
        form input{
            font-family:'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif ;
            box-sizing: border-box;
            flex-grow: 1;
            min-width: 0;
            height: 50px;
            padding: 1.1rem;
            border: 3.5px solid #0f3460;
            border-left: none;
            background-color: #1a1a2e;
            color: #c770f0;
            transition: 150ms ease;
            border-radius: 0px 10px 10px 0px;

        }
        form input:hover{
            border-color:  hsl(303, 38%, 21%);
        }
        form input:focus{
            outline: none;
        }
        div:has(input:focus) > label{
            background-color: #1b264f;
        }
        button{
            font-size: 1.2em;
            border-radius: 10px ;
            border:none;
            width: 35%;
            padding: 13.5px;
            background: linear-gradient(135deg, #ff8c42, #e74c3c);
            color: white;
        }
        button:hover{
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            font-size: 1.350rem;
            cursor: pointer;    
        }
        a{
            text-decoration: none;
            color: rgb(224, 229, 233);
        }
        a:hover{
            font-size: 1.2rem;
            color: hsl(207, 30%, 76%);
        }
        p{
            color: rgb(0, 0, 0);
        }

        /* Error and Success Messages */
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
            max-width: 425px;
            word-wrap: break-word;
        }
        .error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        .success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }

        /* Right side - Gaming Shop Slideshow */
        .right-side {
            width: 50%;
            position: relative;
            overflow: hidden;
        }

        /* Fullscreen background image */
        .img1 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        /* Dark overlay with gradient */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.7),  /* Dark at top */
                rgba(0, 0, 0, 0.3),  /* Lighter in middle */
                rgba(0, 0, 0, 0.7)   /* Dark at bottom */
            );
            z-index: 0;
        }

        /* Text container */
        .text-container {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 3;
        }

        /* Individual text slides */
        .text-slide {
            position: absolute;
            text-align: center;
            color: #c770f0;
            max-width: 70%;
            padding: 1.2rem;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.3), rgba(22, 33, 62, 0.3), rgba(15, 52, 96, 0.3), rgba(41, 128, 185, 0.3), rgba(255, 140, 66, 0.3));
            backdrop-filter: blur(15px);
            border-radius: 10px;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            font-family: 'Orbitron', monospace;
            border: 2px solid rgba(41, 128, 185, 0.5);
        }

        .text-slide h1 {
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            font-size: 2.2rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
            margin-bottom: 1rem;
            letter-spacing: 1px;
            color: #ff8c42;
        }

        .text-slide p {
            font-family: 'Orbitron', monospace;
            font-weight: 400;
            font-size: 1rem;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.8);
            line-height: 1.4;
            letter-spacing: 0.5px;
            color: #2980b9;
        }

        /* Active slide */
        .text-slide.active {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <h3>Sign Up</h3>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form id="main-form" method="POST" action="">
        <div id="first-name-div">
            <label for="first_name">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Z"/></svg>
            </label>
            <input type="text" name="first_name" id="first_name" required placeholder="First Name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
        </div>
        <div id="email-div">
            <label for="email">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M760-80q-66 0-113-47t-47-113v-180q0-42 29-71t71-29q42 0 71 29t29 71v180h-80v-180q0-8-6-14t-14-6q-8 0-14 6t-6 14v180q0 33 23.5 56.5T760-160q33 0 56.5-23.5T840-240v-160h80v160q0 66-47 113T760-80ZM120-240q-33 0-56.5-23.5T40-320v-480q0-33 23.5-56.5T120-880h640q33 0 56.5 23.5T840-800v240H700q-58 0-99 41t-41 99v180H120Zm320-280 320-200v-80L440-600 120-800v80l320 200Z"/></svg>
            </label>
        <input type="email" name="email" placeholder="Email@gmail.com" id="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>
        <div id="password-div">
            <label for="password">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/></svg>
            </label>
        <input type="password" name="password" placeholder="Password" id="password" required>
        </div>

        <div id="repeat-password-div">
                    <label for="repeat_password">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/></svg>
        </label>
        <input type="password" name="repeat_password" placeholder="Repeat Password" id="repeat_password" required>
        </div>
        <button type="submit" class="signin-btn primary">Sign up</button>
        <p>Have an account ? <a href="loogin.php">Login</a>
        </p>
        </form>
    </div>

    <!-- Right side - Gaming Shop Slideshow -->
    <div class="right-side">
        <img src="../images/1.jpg" class="img1" alt="Gaming Background">
        <div class="overlay"></div>
        <div class="text-container">
            <div class="text-slide active">
                <h1>Epic Gaming Collection</h1>
                <p>Discover the latest games and gear at unbeatable prices.</p>
            </div>
            <div class="text-slide">
                <h1>Level Up Your Setup</h1>
                <p>Premium gaming accessories for the ultimate experience.</p>
            </div>
            <div class="text-slide">
                <h1>Join the Gaming Community</h1>
                <p>Connect with millions of players worldwide and dominate the leaderboards.</p>
            </div>
        </div>
    </div>

    <script>
        // Get all text slides
        const slides = document.querySelectorAll('.text-slide');
        let currentSlide = 0;

        // Function to show next slide
        function showNextSlide() {
            // Hide current slide
            slides[currentSlide].classList.remove('active');
            
            // Move to next slide (loop back to 0 if at end)
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Show new slide
            slides[currentSlide].classList.add('active');
        }

        // Change slide every 3 seconds (3000ms)
        setInterval(showNextSlide, 3000);

        // Form validation
        document.getElementById('main-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const repeatPassword = document.getElementById('repeat_password').value;
            
            if (password !== repeatPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            // Additional client-side validation
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            const firstName = document.getElementById('first_name').value.trim();
            if (firstName.length < 2) {
                e.preventDefault();
                alert('First name must be at least 2 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>