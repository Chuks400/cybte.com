<?php
/**
 * Verification Pending Page
 * Shows after signup to inform user to check email
 */

$email = $_GET['email'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email - TrustShield VPN</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pending-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            padding: 50px;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 20px;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }
        .email-icon {
            font-size: 100px;
            color: #00d4ff;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .pending-title {
            font-size: 28px;
            margin-bottom: 20px;
            color: #fff;
        }
        .pending-message {
            font-size: 16px;
            line-height: 1.8;
            color: #a0aec0;
            margin-bottom: 30px;
        }
        .email-highlight {
            color: #00d4ff;
            font-weight: 600;
        }
        .instructions {
            background: rgba(0, 212, 255, 0.1);
            border-left: 4px solid #00d4ff;
            padding: 20px;
            text-align: left;
            margin: 30px 0;
            border-radius: 0 10px 10px 0;
        }
        .instructions h3 {
            color: #00d4ff;
            margin-bottom: 15px;
        }
        .instructions ul {
            list-style: none;
            padding: 0;
        }
        .instructions li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .instructions li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #44cc44;
        }
        .btn-primary {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: #0a1929;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }
        .spam-notice {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 170, 68, 0.1);
            border: 1px solid rgba(255, 170, 68, 0.3);
            border-radius: 10px;
            font-size: 14px;
            color: #ffaa44;
        }
    </style>
</head>
<body>
    <div class="starry-background"></div>
    <div class="stars"></div>
    
    <div class="pending-container">
        <div class="email-icon">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        
        <h2 class="pending-title">Check Your Email!</h2>
        
        <p class="pending-message">
            We've sent a verification link to<br>
            <span class="email-highlight"><?php echo htmlspecialchars($email); ?></span>
        </p>
        
        <div class="instructions">
            <h3><i class="fas fa-list-check"></i> Next Steps:</h3>
            <ul>
                <li>Open your email inbox</li>
                <li>Look for email from TrustShield AI</li>
                <li>Click the verification link inside</li>
                <li>Your account will be activated instantly!</li>
            </ul>
        </div>
        
        <div class="spam-notice">
            <i class="fas fa-exclamation-triangle"></i>
            Can't find the email? Check your spam/junk folder!
        </div>
        
        <br><br>
        
        <a href="vpn_login.php" class="btn-primary">
            <i class="fas fa-right-to-bracket"></i> Go to Login
        </a>
    </div>
</body>
</html>
