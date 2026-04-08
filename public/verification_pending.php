<?php
$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <link rel=" icon type=image/jpeg href=ssets/images/favicon.png>\n<link rel=shortcut icon type=image/jpeg href=ssets/images/favicon.png>\n\n<link rel="icon" type="image/jpeg" href="assets/images/favicon.png">
<link rel="shortcut icon" type="image/jpeg" href="assets/images/favicon.png">

<title>Verify Your Email - TrustShield VPN</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pending-container { max-width: 600px; margin: 100px auto; text-align: center; padding: 50px; background: rgba(15, 23, 42, 0.9); border-radius: 20px; border: 1px solid rgba(0, 212, 255, 0.2); }
        .email-icon { font-size: 80px; color: #00d4ff; margin-bottom: 30px; }
        .btn-primary { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #00d4ff, #0099cc); color: #0a1929; text-decoration: none; border-radius: 30px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="starry-background"></div>
    <div class="stars"></div>
    <div class="pending-container">
        <div class="email-icon">✉️</div>
        <h2>Check Your Email!</h2>
        <p>We've sent a verification link to:<br><strong style="color: #00d4ff;"><?php echo htmlspecialchars($email); ?></strong></p>
        <p>Please click the link in your email to activate your account.</p>
        <p style="font-size: 14px; color: #ffaa44; margin-top: 20px;">Can't find it? Check your spam folder!</p>
        <a href="vpn_login.php" class="btn-primary">Go to Login</a>
    </div>
</body>
</html>
