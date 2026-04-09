<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/VPN/VPNService.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';

use Cybte\VPN\VPNService;

$success = false;
$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if(!$email || !$password || !$confirm){
        $error = 'All fields are required.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Invalid email address.';
    } elseif($password !== $confirm){
        $error = 'Passwords do not match.';
    } elseif(strlen($password) < 6){
        $error = 'Password must be at least 6 characters.';
    } else {
        $database = new Database();
        try {
            $conn = $database->connect();
        } catch (Throwable $e) {
            $conn = null;
            $error = $e->getMessage();
        }

        if($conn){

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->fetch(PDO::FETCH_ASSOC)){
            $error = 'Email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $name = 'VPN User';

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
            $role = 'vpn_user';
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':role', $role);

            if($stmt->execute()){
                $newUserId = (int)$conn->lastInsertId();

                $plan = 'trial';
                $serviceType = 'vpn';
                $startDate = date('Y-m-d H:i:s');
                $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
                $status = 'active';

                $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status) VALUES (:user_id, :plan, :service_type, :start_date, :expiry_date, :status)");
                $stmt->bindParam(':user_id', $newUserId, PDO::PARAM_INT);
                $stmt->bindParam(':plan', $plan);
                $stmt->bindParam(':service_type', $serviceType);
                $stmt->bindParam(':start_date', $startDate);
                $stmt->bindParam(':expiry_date', $expiryDate);
                $stmt->bindParam(':status', $status);
                $stmt->execute();

                // Send email verification
                $emailVerify = new EmailVerification($conn);
                $token = $emailVerify->generateToken();
                
                if ($emailVerify->saveToken($newUserId, $email, $token)) {
                    $emailSent = $emailVerify->sendVerificationEmail($email, $token, $name);
                    
                    if (!$emailSent) {
                        error_log('EmailVerification: Failed to send verification email to ' . $email);
                    }
                }

                // Create real VPN account via 3x-ui API
                $vpnService = new VPNService($conn);
                $vpnAccount = $vpnService->createAccount($newUserId, null, 'trial');
                
                if (!$vpnAccount) {
                    error_log('VPNService: Failed to create VPN account for user ' . $newUserId);
                }

                // Redirect to verification pending page
                header('Location: verification_pending.php?email=' . urlencode($email));
                exit();
            } else {
                $error = 'Could not create account.';
            }
        }

        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Sign Up - Cybte VPN</title>
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
<a href="vpn_signup.php" class="sign-up-btn active">Sign Up</a>
<a href="vpn_login.php" class="sign-in-btn">Log In</a>
</nav>
</div>
</div>
</header>

<section class="vpn-backend">
<div class="container">
<div class="vpn-backend-card">
<h2>Create Account</h2>
<p class="vpn-backend-sub">Create your Cybte VPN account to generate your subscription link.</p>

<?php if($error): ?>
<div class="vpn-note" style="border-color: rgba(255, 68, 68, 0.35); background: rgba(255, 68, 68, 0.08);">
<strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<br>
<?php endif; ?>

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
<div class="password-strength" id="password-strength">
<div class="strength-bar"></div>
<span class="strength-text">Enter at least 8 characters with uppercase, lowercase, number, and symbol</span>
</div>
<div class="form-group password-group">
<i class="fas fa-lock"></i>
<input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
<i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
</div>
<button type="submit" class="submit-btn"><i class="fas fa-user-plus"></i> Create Account</button>
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

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    let strength = 0;
    let messages = [];
    
    if (password.length >= 8) strength++;
    else messages.push('8+ characters');
    
    if (/[A-Z]/.test(password)) strength++;
    else messages.push('uppercase letter');
    
    if (/[a-z]/.test(password)) strength++;
    else messages.push('lowercase letter');
    
    if (/[0-9]/.test(password)) strength++;
    else messages.push('number');
    
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    else messages.push('special symbol (!@#$%)');
    
    const colors = ['#ff4444', '#ff8844', '#ffaa44', '#88cc44', '#44cc44'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    
    strengthBar.style.width = (strength * 20) + '%';
    strengthBar.style.background = colors[strength - 1] || '#ff4444';
    
    if (strength === 5) {
        strengthText.textContent = '✓ Strong password!';
        strengthText.style.color = '#44cc44';
    } else {
        strengthText.textContent = 'Add: ' + messages.join(', ');
        strengthText.style.color = '#ffaa44';
    }
});
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
.password-strength {
    margin: -10px 0 15px 0;
    padding: 0 10px;
}
.strength-bar {
    height: 4px;
    width: 0%;
    background: #ff4444;
    transition: all 0.3s;
    border-radius: 2px;
    margin-bottom: 5px;
}
.strength-text {
    font-size: 12px;
    color: #ffaa44;
    display: block;
    margin-top: 5px;
}
</style>

</div>
</div>
</section>

</body>
</html>
