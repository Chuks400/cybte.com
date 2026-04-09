<?php
/**
 * Verification Pending Page
 * Shows after signup to inform user to check email
 */

$email = $_GET['email'] ?? '';
$sent = isset($_GET['sent']) ? (bool) $_GET['sent'] : true;
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email - Cybte VPN</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
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
                <li>Look for email from Cybte AI</li>
                <li>Click the verification link inside</li>
                <li>Your account will be activated instantly!</li>
            </ul>
        </div>
        
        <?php if ($status === 'resent'): ?>
        <div class="spam-notice" style="background: rgba(68, 204, 68, 0.1); border-color: rgba(68, 204, 68, 0.3); color: #a6ffb8;">
            <i class="fas fa-check-circle"></i>
            Verification email resent successfully. Please check your inbox.
        </div>
        <?php elseif ($status === 'already_verified'): ?>
        <div class="spam-notice" style="background: rgba(0, 212, 255, 0.1); border-color: rgba(0, 212, 255, 0.3); color: #c4f0ff;">
            <i class="fas fa-info-circle"></i>
            Your email has already been verified. Please log in to continue.
        </div>
        <?php elseif ($status === 'resent_failed' || $status === 'error'): ?>
        <div class="spam-notice" style="background: rgba(255, 68, 68, 0.08); border-color: rgba(255, 68, 68, 0.3); color: #ff9b9b;">
            <i class="fas fa-exclamation-circle"></i>
            We could not resend your verification email right now. Please try again later or contact support.
        </div>
        <?php elseif (!$sent): ?>
        <div class="spam-notice" style="background: rgba(255, 68, 68, 0.08); border-color: rgba(255, 68, 68, 0.3); color: #ff9b9b;">
            <i class="fas fa-exclamation-circle"></i>
            We were unable to send your verification email automatically. Please try resending or contact support.
        </div>
        <?php else: ?>
        <div class="spam-notice">
            <i class="fas fa-exclamation-triangle"></i>
            Can't find the email? Check your spam/junk folder!
        </div>
        <?php endif; ?>
        
        <br>
        <a href="resend_verification.php?email=<?php echo urlencode($email); ?>" class="btn-primary">
            <i class="fas fa-envelope-circle-check"></i> Resend Verification Email
        </a>
        <a href="vpn_login.php" class="btn-primary">
            <i class="fas fa-right-to-bracket"></i> Go to Login
        </a>
    </div>
</body>
</html>
