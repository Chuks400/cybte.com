<?php

require_once __DIR__ . '/../src/controllers/AuthController.php';

$error = '';
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new AuthController();
    $result = $auth->login($email, $password);
    
    if (!$result) {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cybte AI</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(13, 25, 48, 0.85);
            border: 1px solid rgba(0, 229, 255, 0.15);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(12px);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .login-title {
            font-size: 28px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 8px;
        }
        .login-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
        .login-form .form-group {
            margin-bottom: 20px;
        }
        .login-form input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .login-form input:focus {
            outline: none;
            border-color: #00e5ff;
            background: rgba(255, 255, 255, 0.08);
        }
        .login-form input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00e5ff, #00b8d4);
            border: none;
            border-radius: 10px;
            color: #0a1628;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 229, 255, 0.3);
        }
        .login-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
        }
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.15);
        }
        .login-divider span {
            padding: 0 15px;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        .login-footer p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin-bottom: 10px;
        }
        .login-footer a {
            color: #00e5ff;
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .signup-btn {
            display: inline-block;
            padding: 12px 24px;
            background: transparent;
            border: 1px solid rgba(0, 229, 255, 0.3);
            border-radius: 10px;
            color: #00e5ff;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .signup-btn:hover {
            background: rgba(0, 229, 255, 0.1);
            border-color: #00e5ff;
        }
        .error-message {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #00e5ff;
        }
    </style>
</head>
<body>
    <div class="starry-background"></div>
    <div class="stars"></div>
    
    <a href="vpn.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/logo.png" alt="Cybte AI" class="login-logo">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access your VPN dashboard</p>
            </div>

            <?php if($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email address" required autofocus>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>

            <div class="login-divider">
                <span>OR</span>
            </div>

            <div class="login-footer">
                <p>New to Cybte?</p>
                <a href="vpn_signup.php" class="signup-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
        </div>
    </div>

    <script src="assets/js/stars.js"></script>
</body>
</html>