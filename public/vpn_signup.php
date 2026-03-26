<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/VPN/VPNService.php';

use TrustShield\VPN\VPNService;

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

                // Create real VPN account via 3x-ui API
                $vpnService = new VPNService($conn);
                $vpnAccount = $vpnService->createAccount($newUserId, null, 'trial');
                
                if (!$vpnAccount) {
                    // VPN creation failed but user was created - log error for admin
                    error_log('VPNService: Failed to create VPN account for user ' . $newUserId);
                    // Continue anyway - user can be assigned manually later
                }

                session_start();
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;

                header('Location: vpn_dashboard.php');
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

<title>Sign Up - TrustShield VPN</title>

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
<a href="vpn.php"><img src="assets/images/logo.jpg" alt="TrustShield AI Logo" class="logo-img"></a>
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
<p class="vpn-backend-sub">Create your TrustShield VPN account to generate your subscription link.</p>

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
<div class="form-group">
<i class="fas fa-lock"></i>
<input type="password" name="password" placeholder="Password" required>
</div>
<div class="form-group">
<i class="fas fa-lock"></i>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>
</div>
<button type="submit" class="submit-btn"><i class="fas fa-user-plus"></i> Create Account</button>
</form>

</div>
</div>
</section>

</body>
</html>
