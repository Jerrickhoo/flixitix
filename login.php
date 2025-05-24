<?php
include 'db.php'; // Make sure this connects to your database

session_start();

$signup_error = '';
$signup_success = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $email = trim($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $confirm = $_POST['signup_confirm'];

    if ($password !== $confirm) {
        $signup_error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $signup_error = "Email already registered.";
        } else {
            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hashed);
            if ($stmt->execute()) {
                // Redirect to login page after successful signup
                $signup_success = "Registration successful! Please sign in.";
                // Optionally, you can clear the POST data here
                // Do not log in or redirect to homepage
            } else {
                $signup_error = "Error: " . $conn->error;
            }
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Login successful, redirect to homepage
            $_SESSION['user_email'] = $email;
            header("Location: Homepage/Homepage.php");
            exit();
        } else {
            $login_error = "Incorrect password.";
        }
    } else {
        $login_error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Flixtix</title>
    <style>
        /* ...your existing CSS here... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 0;
            z-index: -2;
            animation: pan 20s linear infinite alternate;
        }
        @keyframes pan {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.05) translate(-10px, -10px); }
        }
        .background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border: none;
            animation: float 10s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(255, 0, 0, 0.55), rgba(26, 26, 26, 1));
            z-index: -1;
        }
        .login-box,
        .signup-form {
            position: absolute;
            width: 350px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #222222;
            padding: 40px;
            border-radius: 8px;
            color: white;
            opacity: 0;
            pointer-events: none;
            transition: transform 0.5s ease, opacity 0.5s ease;
        }
        .login-box.active,
        .signup-form.active {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, -50%) translateX(0);
        }
        .login-box.slide-out-left {
            transform: translate(-150%, -50%);
            opacity: 0;
        }
        .signup-form.slide-in-right {
            transform: translate(50%, -50%);
            opacity: 0;
        }
        .login-box h2,
        .signup-form h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-box input[type="email"],
        .login-box input[type="password"],
        .signup-form input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #1A1A1A;
            border: none;
            border-radius: 4px;
            color: white;
            transition: background 0.3s;
        }
        .login-box input:focus,
        .signup-form input:focus {
            outline: none;
            background: #333;
        }
        .login-box button,
        .signup-form button {
            width: 100%;
            padding: 12px;
            background-color: #FF0000;
            border: none;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        .login-box button:hover,
        .signup-form button:hover {
            background-color: #cc0000;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.7);
        }
        .login-box .signup,
        .signup-form .back {
            margin-top: 20px;
            font-size: 14px;
            color: #878787;
            text-align: center;
        }
        .login-box .signup a,
        .signup-form .back a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }
        .signup-form .back a::before {
            content: '\2190';
            margin-right: 8px;
        }
        .login-box.slide-out-left {
            transform: translate(-150%, -50%);
            opacity: 0;
        }	
        .signup-form.slide-in-right {
            transform: translate(50%, -50%);
            opacity: 0;
        }
        .signup-form.slide-in-center,
        .login-box.slide-in-center {
            transform: translate(-50%, -50%);
            opacity: 1;
        }
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 10px;
        }
        .success-message {
            color: #4caf50;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
    <script>
    function showSignup() {
        const loginBox = document.querySelector('.login-box');
        const signupForm = document.querySelector('.signup-form');
        loginBox.classList.remove('active', 'slide-in-center');
        loginBox.classList.add('slide-out-left');
        signupForm.classList.remove('slide-in-right');
        signupForm.classList.add('active', 'slide-in-center');
    }
    function showLogin() {
        const loginBox = document.querySelector('.login-box');
        const signupForm = document.querySelector('.signup-form');
        signupForm.classList.remove('active', 'slide-in-center');
        signupForm.classList.add('slide-in-right');
        loginBox.classList.remove('slide-out-left');
        loginBox.classList.add('active', 'slide-in-center');
    }
    window.onload = () => {
        document.querySelector('.login-box').classList.add('active', 'slide-in-center');
    };
    </script>
</head>
<body>
    <div class="background">
        <img src="background/bad_boys_ride_or_die.jpg" alt="Poster 1">
        <img src="background/carryon.jpg" alt="Poster 2">
        <img src="background/conclave.jpg" alt="Poster 3">
        <img src="background/conjuring_last_rites.jpg" alt="Poster 4">
        <img src="background/deadpool_and_wolverine_ver6.jpg" alt="Poster 5">
        <img src="background/despicable_me_four.jpg" alt="Poster 6">
        <img src="background/didi.jpg" alt="Poster 7">
        <img src="background/dune_part_two.jpg" alt="Poster 8">
        <img src="background/fall_guy.jpg" alt="Poster 9">
        <img src="background/fear_street_prom_queen_ver7.jpg" alt="Poster 10">
        <img src="background/final_destination_bloodlines_ver8.jpg" alt="Poster 11">
        <img src="background/first_omen.jpg" alt="Poster 12">
        <img src="background/garfield_movie_ver2.jpg" alt="Poster 13">
        <img src="background/ghostbusters_afterlife_two_ver7.jpg" alt="Poster 14">
        <img src="background/gladiator_ii.jpg" alt="Poster 15">
        <img src="background/godzilla_x_kong_the_new_empire_ver4.jpg" alt="Poster 16">
        <img src="background/inside_out_two_ver2.jpg" alt="Poster 17">
        <img src="background/kingdom_of_the_planet_of_the_apes.jpg" alt="Poster 18">
        <img src="background/longlegs.jpg" alt="Poster 19">
        <img src="background/maxxxine_ver2.jpg" alt="Poster 20">
        <img src="background/minecraft_the_movie_ver3.jpg" alt="Poster 21">
        <img src="background/nobody_two_ver2.jpg" alt="Poster 22">
        <img src="background/smurfs_ver3.jpg" alt="Poster 23">
        <img src="background/superman_ver2.jpg" alt="Poster 24">
        <img src="background/thunderbolts_ver10.jpg" alt="Poster 25">
    </div>
    <div class="overlay"></div>

    <div class="login-box">
        <h2>Sign In</h2>
        <?php if ($login_error): ?>
            <div class="error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="email" name="login_email" placeholder="Email" required>
            <input type="password" name="login_password" placeholder="Password" required>
            <button type="submit" name="login">Sign In</button>
            <div class="signup">
                New to Flixtix? <a onclick="showSignup()">Sign up now</a>
            </div>
        </form>
    </div>

    <div class="signup-form">
        <h2>Sign Up</h2>
        <?php if ($signup_error): ?>
            <div class="error-message"><?php echo $signup_error; ?></div>
        <?php elseif ($signup_success): ?>
            <div class="success-message"><?php echo $signup_success; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="email" name="signup_email" placeholder="Email" required>
            <input type="password" name="signup_password" placeholder="Password" required>
            <input type="password" name="signup_confirm" placeholder="Confirm Password" required>
            <button type="submit" name="signup">Sign Up</button>
            <div class="back">
                <a onclick="showLogin()">Back to Sign In</a>
            </div>
        </form>
    </div>
</body>
</html>