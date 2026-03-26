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

$flagMap = [
    'USA' => 'assets/images/USA.png',
    'UK' => 'assets/images/UK.png',
    'Germany' => 'assets/images/Germany.png',
    'Singapore' => 'assets/images/Singapore.png',
    'Netherlands' => 'assets/images/Netherlands.png',
    'Canada' => 'assets/images/Canada.png',
    'Japan' => 'assets/images/Japan.png',
    'Australia' => 'assets/images/Australia.png',
];

$servers = [
    ['country' => 'USA', 'city' => 'New York', 'ping' => '40ms', 'load' => '32%', 'status' => 'Online'],
    ['country' => 'UK', 'city' => 'London', 'ping' => '55ms', 'load' => '28%', 'status' => 'Online'],
    ['country' => 'Germany', 'city' => 'Frankfurt', 'ping' => '32ms', 'load' => '45%', 'status' => 'Online'],
    ['country' => 'Singapore', 'city' => 'Singapore', 'ping' => '18ms', 'load' => '51%', 'status' => 'Online'],
    ['country' => 'Netherlands', 'city' => 'Amsterdam', 'ping' => '48ms', 'load' => '22%', 'status' => 'Online'],
    ['country' => 'Canada', 'city' => 'Toronto', 'ping' => '62ms', 'load' => '37%', 'status' => 'Online'],
    ['country' => 'Japan', 'city' => 'Tokyo', 'ping' => '44ms', 'load' => '26%', 'status' => 'Online'],
    ['country' => 'Australia', 'city' => 'Sydney', 'ping' => '68ms', 'load' => '34%', 'status' => 'Online'],
];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Servers - TrustShield VPN</title>

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
<a href="index.php"><img src="assets/images/logo.jpg" alt="TrustShield AI Logo" class="logo-img"></a>
</div>
<nav>
<a href="vpn.php">Home</a>
<a href="vpn_download.php">Download</a>
<a href="vpn_pricing.php">Price</a>
<a href="vpn_servers.php" class="active">Servers</a>
<a href="vpn_security.php">Security</a>
<a href="<?php echo $authSecondaryLink; ?>" class="sign-up-btn"><?php echo $authSecondaryText; ?></a>
<a href="<?php echo $authPrimaryLink; ?>" class="sign-in-btn"><?php echo $authPrimaryText; ?></a>
</nav>
</div>
</div>
</header>

<section class="vpn-features">
<div class="container">
<h2 class="vpn-section-title">Servers</h2>
<div class="vpn-features-grid">
<?php foreach($servers as $server): ?>
<div class="vpn-feature">
<?php $flagSrc = $flagMap[$server['country']] ?? null; ?>
<div class="vpn-server-title">
<div class="vpn-flag-wrap">
<?php if($flagSrc): ?>
<img class="vpn-flag" src="<?php echo htmlspecialchars($flagSrc); ?>" alt="<?php echo htmlspecialchars($server['country']); ?> flag">
<?php else: ?>
<div class="vpn-feature-icon"><i class="fas fa-globe"></i></div>
<?php endif; ?>
</div>
<h3><?php echo htmlspecialchars($server['country']); ?> - <?php echo htmlspecialchars($server['city']); ?></h3>
</div>
<p>Ping: <?php echo htmlspecialchars($server['ping']); ?> | Load: <?php echo htmlspecialchars($server['load']); ?> | Status: <?php echo htmlspecialchars($server['status']); ?></p>
<a class="vpn-buy" href="<?php echo $authPrimaryLink; ?>">Connect</a>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

</body>
</html>
