<?php
session_start();

$authPrimaryLink = 'vpn_login.php';
$authPrimaryText = 'Log In';
$authSecondaryLink = 'vpn_signup.php';
$authSecondaryText = 'Sign Up';

if(isset($_SESSION['user_id'])){
    $authPrimaryLink = 'vpn_dashboard.php';
    $authPrimaryText = 'Dashboard';
    $authSecondaryLink = 'dashboard.php';
    $authSecondaryText = 'Main App';
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download - Cybte VPN</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div class="starry-background"></div>
<div class="stars"></div>

<header>
<div class="container">
<div class="header-content">
<div class="logo">
<a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI Logo" class="logo-img"></a>
</div>
<nav>
<a href="vpn.php">Home</a>
<a href="vpn_download.php" class="active">Download</a>
<a href="vpn_pricing.php">Price</a>
<a href="vpn_servers.php">Servers</a>
<a href="vpn_security.php">Security</a>
<a href="<?php echo $authSecondaryLink; ?>" class="sign-up-btn"><?php echo $authSecondaryText; ?></a>
<a href="<?php echo $authPrimaryLink; ?>" class="sign-in-btn"><?php echo $authPrimaryText; ?></a>
</nav>
</div>
</div>
</header>

<section class="vpn-features">
<div class="container">
<h2 class="vpn-section-title">Download</h2>
<div class="vpn-features-grid">
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fab fa-windows"></i></div>
<h3>Windows App</h3>
<p>Download the Cybte VPN client for Windows 10/11.</p>
<a class="vpn-buy" href="#">Download</a>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fab fa-apple"></i></div>
<h3>macOS App</h3>
<p>Download the Cybte VPN client for Intel and Apple Silicon.</p>
<a class="vpn-buy" href="#">Download</a>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fab fa-android"></i></div>
<h3>Android App</h3>
<p>Install Cybte VPN for Android phones and tablets.</p>
<a class="vpn-buy" href="#">Download</a>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fab fa-apple"></i></div>
<h3>iOS App</h3>
<p>Install Cybte VPN on iPhone and iPad.</p>
<a class="vpn-buy" href="#">Download</a>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fab fa-linux"></i></div>
<h3>Linux Setup</h3>
<p>Setup instructions for Linux using supported protocols.</p>
<a class="vpn-buy" href="#">View Guide</a>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-gears"></i></div>
<h3>Manual Configuration</h3>
<p>Use manual setup for V2Ray, OpenVPN, or WireGuard.</p>
<a class="vpn-buy" href="<?php echo $authPrimaryLink; ?>">Get Config</a>
</div>
</div>
</div>
</section>

</body>
</html>
