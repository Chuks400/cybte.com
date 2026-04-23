<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/VPN/VPNService.php';

use Cybte\VPN\VPNService;

require_role(['vpn_user', 'admin'], 'vpn_login.php');

$database = new Database();
$dbError = '';
$conn = null;
try {
    $conn = $database->connect();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'vpn_user';
$isAdmin = in_array($userRole, ['admin', 'owner']);

// Admin/Owner bypass - always active subscription
$subscriptionPlan = $isAdmin ? 'owner' : 'trial';
$subscriptionStatus = $isAdmin ? 'active' : 'inactive';
$subscriptionExpiry = null;
$subscriptionLink = '';
$userEmail = '';
$vpnAccount = null;
$trafficTotalGb = 10;  // Default trial limit
$trafficUsedGb = 0;
$trafficPercent = 0;
$serverInfo = null;

// Handle subscription reset via VPN service
if($conn && isset($_POST['reset_link']) && $userId > 0){
    $vpnService = new VPNService($conn);

    // Auto-create subscription if none exists (for trial users) - skip for admin
    if($subscriptionStatus !== 'active' && !$isAdmin){
        $startDate = date('Y-m-d H:i:s');
        $expiryDate = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 days for trial
        $newStatus = 'active';
        $plan = 'trial';

        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status) VALUES (:user_id, :plan, 'vpn', :start_date, :expiry_date, :status)");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':plan', $plan);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':expiry_date', $expiryDate);
        $stmt->bindParam(':status', $newStatus);
        $stmt->execute();

        // Refresh subscription status
        $subscriptionStatus = 'active';
        $subscriptionPlan = $plan;
        $subscriptionExpiry = $expiryDate;
    }

    // Check if user has an existing account
    $existingAccount = $vpnService->getUserAccount($userId);

    if($existingAccount){
        // Reset existing link
        $newAccount = $vpnService->resetSubscriptionLink($userId);
    } else {
        // No account exists - create one based on subscription plan
        $newAccount = $vpnService->createAccount($userId, null, $subscriptionPlan);
    }

    if($newAccount){
        $subscriptionLink = $newAccount['subscription_link'];
        header('Location: vpn_dashboard.php');
        exit();
    } else {
        // Get detailed error from error log
        $errorDetails = error_get_last();
        $dbError = 'Failed to generate subscription link.<br>';
        
        // Check if servers exist
        $servers = $vpnService->getServers();
        if(empty($servers)){
            $dbError .= '<strong>Error:</strong> No active VPN servers configured.<br>';
        } else {
            $dbError .= '<strong>Servers found:</strong> ' . count($servers) . '<br>';
        }
        
        $dbError .= '<strong>User ID:</strong> ' . $userId . '<br>';
        $dbError .= '<strong>Role:</strong> ' . $userRole . '<br>';
        $dbError .= '<strong>Check:</strong> VPN service logs for details.';
    }
}

// Handle subscription activation/renewal
if($conn && isset($_POST['activate']) && $userId > 0){
    $plan = $_POST['plan'] ?? 'trial';
    $startDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    $newStatus = 'active';
    
    // Update subscription
    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status) VALUES (:user_id, :plan, 'vpn', :start_date, :expiry_date, :status)");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':plan', $plan);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':expiry_date', $expiryDate);
    $stmt->bindParam(':status', $newStatus);
    $stmt->execute();
    
    // Create VPN account if not exists
    $vpnService = new VPNService($conn);
    $account = $vpnService->getUserAccount($userId);
    
    if(!$account){
        $vpnAccount = $vpnService->createAccount($userId, null, $plan);
    }
    
    header('Location: vpn_dashboard.php');
    exit();
}

// Load user data and VPN account
if($conn && $userId > 0){
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if($user && !empty($user['email'])){
        $userEmail = $user['email'];
    }
    
    // Get subscription info
    $stmt = $conn->prepare("SELECT id, plan, status, expiry_date FROM subscriptions WHERE user_id = :user_id AND service_type = 'vpn' ORDER BY id DESC LIMIT 1");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($sub){
        $subscriptionPlan = $sub['plan'] ?: $subscriptionPlan;
        $subscriptionStatus = $sub['status'] ?: $subscriptionStatus;
        $subscriptionExpiry = $sub['expiry_date'] ?? null;
    }
    
    // Get payment history
    $paymentStmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $paymentStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $paymentStmt->execute();
    $paymentHistory = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get VPN account from 3x-ui backend
    $vpnService = new VPNService($conn);
    $vpnAccount = $vpnService->getUserAccount($userId);
    
    if($vpnAccount){
        $subscriptionLink = $vpnAccount['subscription_link'];
        $trafficTotalGb = $vpnAccount['traffic_limit_gb'] ?? $trafficTotalGb;
        $trafficUsedGb = $vpnAccount['traffic_used_gb'] ?? 0;
        
        if($trafficTotalGb > 0){
            $trafficPercent = min(100, ($trafficUsedGb / $trafficTotalGb) * 100);
        }
        
        $serverInfo = [
            'name' => $vpnAccount['server_name'] ?? 'Unknown',
            'country' => $vpnAccount['server_country'] ?? 'US',
            'flag' => $vpnAccount['server_flag'] ?? '🇺🇸'
        ];
    }
}

// Check for payment success
$paymentSuccess = isset($_GET['payment']) && $_GET['payment'] === 'success';

?>

<!DOCTYPE html>
<html>

<head>

<title>VPN Dashboard - Cybte AI</title>

<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

<div class="starry-background"></div>
<div class="stars"></div>

<div class="vpn-app">
<header class="vpn-topbar">
<div class="vpn-topbar-left">
<a href="vpn.php" class="vpn-topbar-logo">
<img src="assets/images/logo.png" alt="Cybte AI Logo" class="vpn-topbar-logo-img">
</a>
<div class="vpn-topbar-title">User Center</div>
</div>
<div class="vpn-topbar-right">
<div class="vpn-topbar-lang"><i class="fas fa-globe"></i> English</div>
<div class="vpn-topbar-user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($userEmail ?: ($_SESSION['user_name'] ?? 'User')); ?></div>
<a class="vpn-topbar-logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
</header>

<div class="vpn-shell">
<aside class="vpn-sidebar">
<a class="vpn-side-item active" href="vpn_dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
<a class="vpn-side-item" href="vpn_servers.php"><i class="fas fa-server"></i><span>VPN Servers</span></a>
<a class="vpn-side-item" href="vpn_pricing.php"><i class="fas fa-credit-card"></i><span>Purchase Plan</span></a>
<a class="vpn-side-item" href="vpn_security.php"><i class="fas fa-shield-alt"></i><span>User Center</span></a>
<a class="vpn-side-item" href="dashboard.php"><i class="fas fa-layer-group"></i><span>Main App</span></a>
</aside>

<main class="vpn-main">
<div class="vpn-main-inner">
<div class="vpn-welcome">
<div class="vpn-welcome-left">
<div class="vpn-welcome-hello">Hello, <?php echo htmlspecialchars($userEmail ?: ($_SESSION['user_name'] ?? 'User')); ?></div>
<div class="vpn-welcome-sub">Manage your subscription, connection link, and settings.</div>
</div>
<div class="vpn-welcome-right">
<div class="vpn-quick-stats">
<div class="vpn-stat"><div class="vpn-stat-label">Plan</div><div class="vpn-stat-value"><?php echo htmlspecialchars($subscriptionPlan); ?></div></div>
<div class="vpn-stat"><div class="vpn-stat-label">Status</div><div class="vpn-stat-value <?php echo strtolower($subscriptionStatus); ?>"><?php echo $subscriptionStatus; ?></div></div>
<div class="vpn-stat"><div class="vpn-stat-label">Member Since</div><div class="vpn-stat-value"><?php echo date('Y-m-d'); ?></div></div>
</div>
</div>
</div>

<section class="vpn-cards">

<?php if($dbError): ?>
<div class="vpn-note" style="border-color: rgba(255, 68, 68, 0.35); background: rgba(255, 68, 68, 0.08);">
<strong>Error:</strong> <?php echo htmlspecialchars($dbError); ?>
</div>
<?php endif; ?>

<?php if($paymentSuccess): ?>
<div class="vpn-note" style="border-color: rgba(0, 255, 136, 0.35); background: rgba(0, 255, 136, 0.08);">
    <i class="fas fa-check-circle"></i> <strong>Payment successful!</strong> Your subscription has been activated. Thank you for your purchase!
</div>
<?php endif; ?>

<div class="vpn-card vpn-card-highlight">
<div class="vpn-card-head">
<div class="vpn-card-title">Server Locations</div>
<i class="fas fa-globe-americas vpn-card-icon"></i>
</div>
<div class="vpn-card-body">
<div class="vpn-server-grid">
<div class="vpn-server-item">
<img src="assets/images/USA.png" alt="USA" class="vpn-server-flag">
<div class="vpn-server-info">
<div class="vpn-server-name">United States</div>
<div class="vpn-server-meta">12 ms • 45% load</div>
</div>
</div>
<div class="vpn-server-item">
<img src="assets/images/UK.png" alt="UK" class="vpn-server-flag">
<div class="vpn-server-info">
<div class="vpn-server-name">United Kingdom</div>
<div class="vpn-server-meta">18 ms • 38% load</div>
</div>
</div>
<div class="vpn-server-item">
<img src="assets/images/Germany.png" alt="Germany" class="vpn-server-flag">
<div class="vpn-server-info">
<div class="vpn-server-name">Germany</div>
<div class="vpn-server-meta">22 ms • 52% load</div>
</div>
</div>
<div class="vpn-server-item">
<img src="assets/images/Singapore.png" alt="Singapore" class="vpn-server-flag">
<div class="vpn-server-info">
<div class="vpn-server-name">Singapore</div>
<div class="vpn-server-meta">35 ms • 41% load</div>
</div>
</div>
</div>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">Subscription Status</div>
</div>
<div class="vpn-card-body">
<div class="vpn-kv">
<div class="vpn-k">Email</div>
<div class="vpn-v"><?php echo htmlspecialchars($userEmail ?: ''); ?></div>
</div>
<div class="vpn-kv">
<div class="vpn-k">Plan</div>
<div class="vpn-v"><?php echo htmlspecialchars($subscriptionPlan); ?></div>
</div>
<div class="vpn-kv">
<div class="vpn-k">Status</div>
<div class="vpn-v"><span class="vpn-status <?php echo strtolower($subscriptionStatus); ?>"><?php echo $subscriptionStatus; ?></span></div>
</div>
<div class="vpn-kv">
<div class="vpn-k">Expiration Date</div>
<div class="vpn-v">
<?php if($subscriptionExpiry): ?>
<?php echo htmlspecialchars(date('Y-m-d', strtotime($subscriptionExpiry))); ?>
<?php else: ?>
<span class="vpn-muted">Not active</span>
<?php endif; ?>
</div>
</div>

<form method="POST" class="vpn-card-actions">
<input type="hidden" name="plan" value="<?php echo htmlspecialchars($subscriptionPlan); ?>">
<button class="cta-button" type="submit" name="activate">Renew Subscription</button>
</form>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">VPN Connection</div>
</div>
<div class="vpn-card-body">
<?php if($serverInfo): ?>
<div class="vpn-kv">
<div class="vpn-k">Connected Server</div>
<div class="vpn-v"><?php echo htmlspecialchars($serverInfo['flag'] . ' ' . $serverInfo['name']); ?></div>
</div>
<?php endif; ?>
<div class="vpn-connection-row">
<div class="vpn-connection-label">Subscription Link</div>
<div class="vpn-connection-value">
<?php if($subscriptionLink): ?>
<code class="vpn-code" id="vpnSubLink"><?php echo htmlspecialchars($subscriptionLink); ?></code>
<?php else: ?>
<span class="vpn-muted">Not generated yet</span>
<?php endif; ?>
</div>
</div>

<div class="vpn-connection-actions">
<?php if($subscriptionLink): ?>
<button class="vpn-secondary-btn" type="button" id="copyVpnLink">Copy Link</button>
<?php endif; ?>
<form method="POST" style="display:inline;">
<button class="vpn-secondary-btn" type="submit" name="reset_link">Reset Link</button>
</form>
</div>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">Traffic Usage</div>
<i class="fas fa-chart-pie vpn-card-icon"></i>
</div>
<div class="vpn-card-body">
<div class="vpn-usage-header">
<div class="vpn-usage-label">Monthly Traffic</div>
<div class="vpn-usage-value"><?php echo htmlspecialchars(number_format($trafficUsedGb, 2)); ?>GB / <?php echo htmlspecialchars((string)$trafficTotalGb); ?>GB</div>
</div>
<div class="vpn-progress">
<div class="vpn-progress-bar" style="width: <?php echo (int)$trafficPercent; ?>%;"></div>
</div>
<div class="vpn-usage-footer">
<div class="vpn-usage-days-left"><?php echo (30 - (int)date('d')); ?> days left this month</div>
<div class="vpn-usage-percent"><?php echo (int)$trafficPercent; ?>% used</div>
</div>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">Notifications</div>
<i class="fas fa-bell vpn-card-icon"></i>
</div>
<div class="vpn-card-body">
<div class="vpn-toggle-row">
<div>
<div class="vpn-toggle-title">Expiration Email Reminder</div>
<div class="vpn-toggle-sub">Get an email reminder before your subscription expires.</div>
</div>
<label class="vpn-switch">
<input type="checkbox">
<span class="vpn-slider"></span>
</label>
</div>

<div class="vpn-toggle-row">
<div>
<div class="vpn-toggle-title">Traffic Email Reminder</div>
<div class="vpn-toggle-sub">Get an email reminder when traffic is running low.</div>
</div>
<label class="vpn-switch">
<input type="checkbox">
<span class="vpn-slider"></span>
</label>
</div>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">Security Settings</div>
<i class="fas fa-shield-alt vpn-card-icon"></i>
</div>
<div class="vpn-card-body">
<div class="vpn-setting-row">
<div>
<div class="vpn-toggle-title">Change Password</div>
<div class="vpn-toggle-sub">If your password is leaked, you can reset it here.</div>
</div>
<button class="vpn-secondary-btn" type="button" onclick="alert('Change password page coming next');">Change Password</button>
</div>

<div class="vpn-setting-row">
<div>
<div class="vpn-toggle-title">Reset Subscription Link</div>
<div class="vpn-toggle-sub">Reset your subscription link if it is leaked or misused.</div>
</div>
<form method="POST" style="margin:0;">
<button class="vpn-danger-btn" type="submit" name="reset_link">Reset</button>
</form>
</div>
</div>
</div>

<div class="vpn-card">
<div class="vpn-card-head">
<div class="vpn-card-title">Payment History</div>
<i class="fas fa-receipt vpn-card-icon"></i>
</div>
<div class="vpn-card-body">
<?php if(!empty($paymentHistory)): ?>
<div class="payment-history">
<?php foreach($paymentHistory as $payment): ?>
<div class="payment-item">
<div class="payment-info">
<span class="payment-plan"><?php echo htmlspecialchars($payment['plan_name']); ?></span>
<span class="payment-method">
<?php if($payment['method'] === 'alipay'): ?>
<i class="fab fa-alipay" style="color: #1677FF;"></i> Alipay
<?php else: ?>
<i class="fab fa-weixin" style="color: #07C160;"></i> WeChat
<?php endif; ?>
</span>
</div>
<div class="payment-details">
<span class="payment-amount">¥<?php echo number_format($payment['amount'], 2); ?></span>
<span class="payment-status <?php echo $payment['status']; ?>">
<?php echo $payment['status'] === 'paid' ? '✓ Paid' : ($payment['status'] === 'pending' ? '⏳ Pending' : '✗ Failed'); ?>
</span>
</div>
<div class="payment-date"><?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?></div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p class="vpn-muted">No payment history yet. <a href="vpn_pricing.php" style="color: #00E5FF;">Purchase a plan</a> to get started.</p>
<?php endif; ?>
</div>
</div>

</section>
</div>
</main>
</div>
</div>

<script>
const copyBtn = document.getElementById('copyVpnLink');
if(copyBtn){
    copyBtn.addEventListener('click', async () => {
        const el = document.getElementById('vpnSubLink');
        if(!el) return;

        const text = el.textContent || '';
        try {
            await navigator.clipboard.writeText(text);
            copyBtn.textContent = 'Copied';
            setTimeout(() => copyBtn.textContent = 'Copy Link', 1200);
        } catch (e) {
            const range = document.createRange();
            range.selectNodeContents(el);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    });
}
</script>

</body>
</html>
