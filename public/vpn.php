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
<title>TrustShield VPN - Privacy & Secure Access</title>

<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css?v=2">
</head>

<body>

<div class="starry-background"></div>
<div class="stars"></div>

<header>
<div class="container">
<div class="header-content">
<div class="logo">
<a href="index.php"><img src="assets/images/logo.jpg" alt="TrustShield AI Logo" class="logo-img"></a>
</div>
<nav>
<a href="vpn.php" class="active">Home</a>
<a href="vpn_download.php">Download</a>
<a href="vpn_pricing.php">Price</a>
<a href="vpn_servers.php">Servers</a>
<a href="vpn_security.php">Security</a>
<a href="<?php echo $authSecondaryLink; ?>" class="sign-up-btn"><?php echo $authSecondaryText; ?></a>
<a href="<?php echo $authPrimaryLink; ?>" class="sign-in-btn"><?php echo $authPrimaryText; ?></a>
</nav>
</div>
</div>
</header>

<section class="vpn-hero">
<div class="container">
<div class="vpn-hero-inner">
<div class="vpn-hero-text">
<h1 class="vpn-title">TrustShield VPN</h1>
<p class="vpn-subtitle">Encrypted tunnels for privacy, secure public Wi-Fi, and global access.</p>
<div class="vpn-cta-row">
<a class="cta-button" href="<?php echo $authPrimaryLink; ?>"><?php echo $authPrimaryText; ?></a>
<a class="vpn-secondary-btn" href="#vpn-pricing">View Pricing</a>
</div>
<div class="vpn-pill-row">
<div class="vpn-pill"><i class="fas fa-lock"></i><span>Strong Encryption</span></div>
<div class="vpn-pill"><i class="fas fa-globe"></i><span>Global Locations</span></div>
<div class="vpn-pill"><i class="fas fa-wifi"></i><span>Public Wi-Fi Safety</span></div>
</div>
</div>
<div class="vpn-hero-card">
<div class="vpn-card-top">
<div class="vpn-card-title">VPN Status</div>
<div class="vpn-card-badge">READY</div>
</div>
<div class="vpn-card-body">
<div class="vpn-stat">
<div class="vpn-stat-label">Available Regions</div>
<div class="vpn-stat-value">15+</div>
</div>
<div class="vpn-stat">
<div class="vpn-stat-label">Multi-Device</div>
<div class="vpn-stat-value">Supported</div>
</div>
<div class="vpn-stat">
<div class="vpn-stat-label">Setup</div>
<div class="vpn-stat-value">Minutes</div>
</div>
</div>
</div>
</div>
</div>
</section>

<section class="vpn-features" id="vpn-features">
<div class="container">
<h2 class="vpn-section-title">Why TrustShield VPN</h2>
<div class="vpn-features-grid">
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-shield-alt"></i></div>
<h3>Privacy Protection</h3>
<p>Keep your browsing private with encrypted traffic that helps protect your identity on any network.</p>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-bolt"></i></div>
<h3>Fast Connections</h3>
<p>Optimized routing for smooth browsing, streaming and work connections across global locations.</p>
</div>
<div class="vpn-feature">
<div class="vpn-feature-icon"><i class="fas fa-laptop"></i></div>
<h3>Multi-Device Access</h3>
<p>Use one subscription across your devices with simple onboarding and easy configuration.</p>
</div>
</div>
</div>
</section>

<section class="vpn-pricing" id="vpn-pricing">
<div class="container">
<h2 class="vpn-section-title">VPN Plans</h2>
<div class="vpn-pricing-grid">
<div class="vpn-price-card">
<h3>Starter</h3>
<div class="vpn-price">$3<span>/mo</span></div>
<ul>
<li><i class="fas fa-check-circle"></i> 1 device</li>
<li><i class="fas fa-check-circle"></i> Standard regions</li>
<li><i class="fas fa-check-circle"></i> Basic support</li>
</ul>
<a class="vpn-buy" href="<?php echo $authPrimaryLink; ?>">Get Started</a>
</div>

<div class="vpn-price-card featured">
<div class="vpn-popular">POPULAR</div>
<h3>Pro</h3>
<div class="vpn-price">$7<span>/mo</span></div>
<ul>
<li><i class="fas fa-check-circle"></i> 3 devices</li>
<li><i class="fas fa-check-circle"></i> Premium regions</li>
<li><i class="fas fa-check-circle"></i> Priority support</li>
</ul>
<a class="vpn-buy" href="<?php echo $authPrimaryLink; ?>">Start Pro</a>
</div>

<div class="vpn-price-card">
<h3>Team</h3>
<div class="vpn-price">$15<span>/mo</span></div>
<ul>
<li><i class="fas fa-check-circle"></i> 10 devices</li>
<li><i class="fas fa-check-circle"></i> All regions</li>
<li><i class="fas fa-check-circle"></i> Team support</li>
</ul>
<a class="vpn-buy" href="<?php echo $authPrimaryLink; ?>">Start Team</a>
</div>
</div>
</div>
</section>

<footer>
<div class="container">
<div class="footer-bottom">
<p>&copy; 2024 TrustShield AI. All rights reserved. | TrustShield VPN</p>
</div>
</div>
</footer>

</body>
</html>
