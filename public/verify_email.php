<?php
/**
 * Email Verification Page
 * Handles the verification link from email
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';

$message = '';
$type = 'info'; // info, success, error

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $emailVerify = new EmailVerification($conn);
        $result = $emailVerify->verifyToken($token);
        
        if ($result['success']) {
            $message = '✓ Your email has been verified successfully! You can now log in to your Cybte VPN account.';
            $type = 'success';
        } else {
            $message = '✗ ' . $result['error'] . '. Please request a new verification link.';
            $type = 'error';
        }
    } catch (Exception $e) {
        $message = '✗ An error occurred. Please try again later.';
        $type = 'error';
    }
} else {
    $message = '✗ Invalid verification link. Please check your email for the correct link.';
    $type = 'error';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification - Cybte VPN</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            padding: 40px;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 20px;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }
        .verification-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .success .verification-icon { color: #44cc44; }
        .error .verification-icon { color: #ff4444; }
        .info .verification-icon { color: #00d4ff; }
        .verification-message {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
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
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="starry-background"></div>
    <div class="stars"></div>
    
    <div class="verification-container <?php echo $type; ?>">
        <div class="verification-icon">
            <?php if ($type === 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php elseif ($type === 'error'): ?>
                <i class="fas fa-times-circle"></i>
            <?php else: ?>
                <i class="fas fa-info-circle"></i>
            <?php endif; ?>
        </div>
        
        <h2><?php echo $type === 'success' ? 'Email Verified!' : 'Verification Status'; ?></h2>
        
        <p class="verification-message"><?php echo htmlspecialchars($message); ?></p>
        
        <?php if ($type === 'success'): ?>
            <a href="vpn_login.php" class="btn-primary"><i class="fas fa-right-to-bracket"></i> Log In Now</a>
        <?php else: ?>
            <a href="vpn_login.php" class="btn-primary"><i class="fas fa-envelope"></i> Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
