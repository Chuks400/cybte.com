<?php

require_once __DIR__ . '/../src/controllers/AuthController.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new AuthController();
    $auth->login($email, $password, 'vpn_dashboard.php');
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Log In - Cybte VPN</title>

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
<a href="vpn.php"><img src="assets/images/logo.png" alt="Cybte AI Logo" class="logo-img"></a>
</div>
<nav>
<a href="vpn.php">Home</a>
<a href="vpn_download.php">Download</a>
<a href="vpn_pricing.php">Price</a>
<a href="vpn_servers.php">Servers</a>
<a href="vpn_security.php">Security</a>
<a href="vpn_signup.php" class="sign-up-btn">Sign Up</a>
<a href="vpn_login.php" class="sign-in-btn active">Log In</a>
</nav>
</div>
</div>
</header>

<section class="vpn-backend">
<div class="container">
<div class="vpn-backend-card">
<h2>Log In</h2>
<p class="vpn-backend-sub">Access your Cybte VPN dashboard.</p>

<form method="POST" class="contact-form" style="max-width: 520px; margin: 0 auto;">
<div class="form-group">
<i class="fas fa-envelope"></i>
<input type="email" name="email" placeholder="Email" required>
</div>
<div class="form-group password-group">
<i class="fas fa-lock"></i>
<input type="password" name="password" id="password" placeholder="Password" required>
<i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
</div>
<button type="submit" class="submit-btn"><i class="fas fa-right-to-bracket"></i> Log In</button>
</form>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<style>
.password-group {
    position: relative;
}
.password-group .toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #00d4ff;
    font-size: 16px;
    transition: color 0.3s;
}
.password-group .toggle-password:hover {
    color: #fff;
}
.password-group input {
    padding-right: 45px !important;
}
</style>

</div>
</div>
</section>

</body>
</html>
