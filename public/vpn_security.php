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
<title>Security - Cybte VPN</title>

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
<a href="vpn_download.php">Download</a>
<a href="vpn_pricing.php">Price</a>
<a href="vpn_servers.php">Servers</a>
<a href="vpn_security.php" class="active">Security</a>
<a href="<?php echo $authSecondaryLink; ?>" class="sign-up-btn"><?php echo $authSecondaryText; ?></a>
<a href="<?php echo $authPrimaryLink; ?>" class="sign-in-btn"><?php echo $authPrimaryText; ?></a>
</nav>
</div>
</div>
</header>

<section class="vpn-features">
<div class="container">
<h2 class="vpn-section-title">Security</h2>
<div class="vpn-features-grid">
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-lock"></i></div>
<h3>Encryption</h3>
<p>AES-256 encryption, secure tunneling, and DNS protection help keep your traffic private and secure.</p>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-user-secret"></i></div>
<h3>Privacy</h3>
<p>No-logs policy positioning, IP masking, and secure browsing for public and private networks.</p>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-network-wired"></i></div>
<h3>Technology</h3>
<p>Powered by modern VPN infrastructure with V2Ray-compatible subscription delivery and secure authentication.</p>
</div>
</div>
<div class="vpn-note" style="margin-top: 20px;">
<strong>Powered by CYBTE AI cybersecurity technology.</strong>
</div>
</div>
</section>

</body>
</html>
