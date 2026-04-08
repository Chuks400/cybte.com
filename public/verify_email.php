<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';

$message = '';
$type = 'info';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $emailVerify = new EmailVerification($conn);
        $result = $emailVerify->verifyToken($token);
        
        if ($result['success']) {
            $message = '✓ Your email has been verified! You can now log in.';
            $type = 'success';
        } else {
            $message = '✗ ' . $result['error'];
            $type = 'error';
        }
    } catch (Exception $e) {
        $message = '✗ An error occurred.';
        $type = 'error';
    }
} else {
    $message = '✗ Invalid verification link.';
    $type = 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel=" icon type=image/jpeg href=ssets/images/favicon.png>\n<link rel=shortcut icon type=image/jpeg href=ssets/images/favicon.png>\n\n<link rel="icon" type="image/jpeg" href="assets/images/favicon.png">
<link rel="shortcut icon" type="image/jpeg" href="assets/images/favicon.png">

<title>Email Verification - TrustShield VPN</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verification-container { max-width: 600px; margin: 100px auto; text-align: center; padding: 40px; background: rgba(15, 23, 42, 0.9); border-radius: 20px; border: 1px solid rgba(0, 212, 255, 0.2); }
        .success { color: #44cc44; font-size: 80px; }
        .error { color: #ff4444; font-size: 80px; }
        .btn-primary { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #00d4ff, #0099cc); color: #0a1929; text-decoration: none; border-radius: 30px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="starry-background"></div>
    <div class="stars"></div>
    <div class="verification-container">
        <div class="<?php echo $type; ?>"><?php echo $type === 'success' ? '✓' : '✗'; ?></div>
        <h2><?php echo $type === 'success' ? 'Email Verified!' : 'Verification Failed'; ?></h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="vpn_login.php" class="btn-primary">Log In</a>
    </div>
</body>
</html>
