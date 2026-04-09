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

// Load plans from database or use defaults
require_once __DIR__ . '/../src/config/database.php';
$plans = [];
try {
    $database = new Database();
    $conn = $database->connect();
    $stmt = $conn->query("SELECT * FROM payment_plans WHERE is_active = 1 ORDER BY sort_order");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use default plans if DB not available
    $plans = [
        [
            'plan_key' => 'basic',
            'name' => 'Basic · Single Device',
            'price_cny' => 9.90,
            'traffic_gb' => 80,
            'device_limit' => 1,
            'features' => json_encode(['monthly_traffic_80gb', 'peak_rate_300mbps', 'single_device', 'netflix_gpt', 'regions_hk_jp_us'])
        ],
        [
            'plan_key' => 'regular',
            'name' => 'Regular · Phone + Computer',
            'price_cny' => 16.90,
            'traffic_gb' => 180,
            'device_limit' => 2,
            'features' => json_encode(['monthly_traffic_180gb', 'peak_rate_1000mbps', 'phone_pc', 'netflix_gpt', 'regions_hk_jp_us'])
        ],
        [
            'plan_key' => 'standard',
            'name' => 'Standard · High Bandwidth',
            'price_cny' => 24.90,
            'traffic_gb' => 499,
            'device_limit' => 3,
            'features' => json_encode(['monthly_traffic_499gb', 'peak_rate_10000mbps', 'high_bandwidth', 'netflix_gpt', 'regions_hk_jp_us'])
        ],
        [
            'plan_key' => 'premium',
            'name' => 'Premium · Advance Node',
            'price_cny' => 49.90,
            'traffic_gb' => 1024,
            'device_limit' => 5,
            'features' => json_encode(['monthly_traffic_1tb', 'peak_rate_20000mbps', 'premium_node', 'ultra_speed', 'netflix_gpt', 'regions_hk_jp_us'])
        ]
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Subscription - Cybte VPN</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">

<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="assets/css/payment.css?v=<?php echo time(); ?>">
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
<a href="vpn_pricing.php" class="active">Purchase</a>
<a href="vpn_servers.php">Servers</a>
<a href="vpn_security.php">Security</a>
<a href="<?php echo $authSecondaryLink; ?>" class="sign-up-btn"><?php echo $authSecondaryText; ?></a>
<a href="<?php echo $authPrimaryLink; ?>" class="sign-in-btn"><?php echo $authPrimaryText; ?></a>
</nav>
</div>
</div>
</header>

<section class="vpn-pricing" id="vpn-pricing">
<div class="container">
<h2 class="vpn-section-title"><i class="fas fa-shopping-cart"></i> Purchase Subscription</h2>
<div class="vpn-pricing-grid cny-pricing">

<?php foreach($plans as $index => $plan): 
    $features = json_decode($plan['features'] ?? '[]', true);
    $isPopular = $plan['plan_key'] === 'regular';
?>
<div class="vpn-price-card <?php echo $isPopular ? 'featured' : ''; ?>" data-plan="<?php echo htmlspecialchars($plan['plan_key']); ?>" data-price="<?php echo $plan['price_cny']; ?>">
    <?php if($isPopular): ?>
    <div class="vpn-popular">POPULAR</div>
    <?php endif; ?>
    
    <div class="plan-header">
        <span class="plan-icon"><?php echo $index === 0 ? '🥉' : ($index === 1 ? '🥈' : ($index === 2 ? '🥇' : '🏆')); ?></span>
        <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
        <?php if($plan['plan_key'] === 'regular'): ?><span class="fire-icon">🔥</span><?php endif; ?>
    </div>
    
    <div class="vpn-price">
        <span class="currency">¥</span><?php echo number_format($plan['price_cny'], 2); ?> <span class="period">CNY / Monthly</span>
    </div>
    
    <ul class="plan-features">
        <?php if($plan['plan_key'] === 'basic'): ?>
        <li><span class="badge student">¥9.9 for Student · Lite 🎓</span></li>
        <?php elseif($plan['plan_key'] === 'regular'): ?>
        <li><span class="badge recommend">Recommend for Phone & PC User 🔥</span></li>
        <li><span class="badge regular">¥16.9 for Regular Plan</span></li>
        <?php elseif($plan['plan_key'] === 'standard'): ?>
        <li><span class="badge high-bw">High Bandwidth Phone & PC User</span></li>
        <li><span class="badge standard">17.90 for Standard Plan</span></li>
        <?php else: ?>
        <li><span class="badge premium">Premium | Advance Node | Ultra Speed</span></li>
        <li><span class="badge premium-price">49.90 for Premium Plan</span></li>
        <?php endif; ?>
        
        <li><i class="fas fa-cloud"></i> Monthly traffic: <?php echo $plan['traffic_gb'] >= 1024 ? '1TB+' : $plan['traffic_gb'] . ' GB'; ?> <?php echo $plan['plan_key'] === 'premium' ? '**' : ''; ?></li>
        <li><i class="fas fa-bolt"></i> Peak rate: <?php echo $plan['plan_key'] === 'basic' ? '300' : ($plan['plan_key'] === 'regular' ? '1000' : ($plan['plan_key'] === 'standard' ? '10000' : '20000')); ?> Mbps</li>
        <li><i class="fas fa-desktop"></i> Number of device limits: <?php echo $plan['device_limit']; ?></li>
        <li><i class="fas fa-film"></i> Unblock streaming NETFLIX & GPT</li>
        <li><span class="no-share">🈲</span> Abuse sharing link is not allowed</li>
        <li><i class="fas fa-globe"></i> Regions: HK, JP, US, KR, SG, AU, IN</li>
        <li><i class="fas fa-book"></i> Detailed use Watch <a href="#" class="tutorial-link">Tutorial</a></li>
        <li><i class="fas fa-paper-plane"></i> Join the <a href="#" class="telegram-link">Telegram group</a> to get long-term after-sales support ✈️</li>
    </ul>
    
    <button class="vpn-buy subscribe-btn" onclick="openPaymentModal('<?php echo $plan['plan_key']; ?>', <?php echo $plan['price_cny']; ?>, '<?php echo htmlspecialchars($plan['name']); ?>')">
        <i class="fas fa-credit-card"></i> Subscribe Now
    </button>
</div>
<?php endforeach; ?>

</div>
</div>
</section>

<!-- Payment Modal -->
<div id="paymentModal" class="payment-modal">
    <div class="payment-modal-content">
        <div class="payment-modal-header">
            <h3><i class="fas fa-lock"></i> Secure Payment</h3>
            <button class="close-modal" onclick="closePaymentModal()">&times;</button>
        </div>
        
        <div class="payment-modal-body">
            <div class="order-summary">
                <h4>Order Summary</h4>
                <div class="order-details">
                    <span id="orderPlanName">Plan Name</span>
                    <span class="order-price">¥<span id="orderAmount">0.00</span> CNY</span>
                </div>
            </div>
            
            <div class="payment-methods">
                <h4>Select Payment Method</h4>
                <div class="payment-options">
                    <div class="pay-card" onclick="selectPayment('alipay')" id="alipayCard">
                        <div class="pay-icon">
                            <svg viewBox="0 0 24 24" width="32" height="32">
                                <rect fill="#1677FF" width="24" height="24" rx="4"/>
                                <text fill="white" x="12" y="17" text-anchor="middle" font-size="12" font-weight="bold">支</text>
                            </svg>
                        </div>
                        <div class="pay-info">
                            <span class="pay-name">Alipay</span>
                            <span class="pay-desc">支付宝</span>
                        </div>
                        <div class="pay-check"><i class="fas fa-check-circle"></i></div>
                    </div>
                    
                    <div class="pay-card" onclick="selectPayment('wechat')" id="wechatCard">
                        <div class="pay-icon">
                            <svg viewBox="0 0 24 24" width="32" height="32">
                                <rect fill="#07C160" width="24" height="24" rx="4"/>
                                <path fill="white" d="M8.5 11a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                            </svg>
                        </div>
                        <div class="pay-info">
                            <span class="pay-name">WeChat Pay</span>
                            <span class="pay-desc">微信支付</span>
                        </div>
                        <div class="pay-check"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            
            <div class="qr-section" id="qrSection" style="display: none;">
                <div class="qr-container">
                    <img id="paymentQR" src="" alt="Scan to pay" width="220" height="220">
                    <p class="qr-instruction">Scan with <span id="qrApp">App</span> to complete payment</p>
                    <div class="qr-timer">
                        <i class="fas fa-clock"></i> Expires in <span id="qrTimer">15:00</span>
                    </div>
                </div>
                <div class="payment-status" id="paymentStatus">
                    <div class="status-pending">
                        <i class="fas fa-spinner fa-spin"></i> Waiting for payment...
                    </div>
                </div>
                <div class="simulate-section" id="simulateSection" style="display: none;">
                    <p class="simulate-hint">Testing mode: Click below to simulate a successful payment</p>
                    <button class="simulate-btn" onclick="simulatePayment()">
                        <i class="fas fa-vial"></i> Simulate Payment (Test Only)
                    </button>
                </div>
            </div>
        </div>
        
        <div class="payment-modal-footer">
            <button class="pay-confirm-btn" id="confirmBtn" onclick="createPayment()">
                <i class="fas fa-qrcode"></i> Generate QR Code
            </button>
            <button class="pay-cancel-btn" onclick="closePaymentModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
let selectedPlan = null;
let selectedAmount = 0;
let selectedMethod = 'alipay';
let orderId = null;
let pollingInterval = null;
let timerInterval = null;

function openPaymentModal(plan, amount, name) {
    selectedPlan = plan;
    selectedAmount = amount;
    document.getElementById('orderPlanName').textContent = name;
    document.getElementById('orderAmount').textContent = amount.toFixed(2);
    document.getElementById('paymentModal').style.display = 'flex';
    selectPayment('alipay');
    resetQR();
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    stopPolling();
    stopTimer();
}

function selectPayment(method) {
    selectedMethod = method;
    document.querySelectorAll('.pay-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById(method + 'Card').classList.add('selected');
    document.getElementById('qrApp').textContent = method === 'alipay' ? 'Alipay' : 'WeChat';
}

function resetQR() {
    document.getElementById('qrSection').style.display = 'none';
    document.getElementById('confirmBtn').style.display = 'block';
    document.getElementById('confirmBtn').innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
}

function createPayment() {
    const btn = document.getElementById('confirmBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    fetch('api/payment/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            method: selectedMethod,
            plan: selectedPlan,
            amount: selectedAmount
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            orderId = data.order_id;
            document.getElementById('paymentQR').src = data.qr_url;
            document.getElementById('qrSection').style.display = 'block';
            document.getElementById('confirmBtn').style.display = 'none';
            
            // Show simulate button if in fake/test mode
            if (data.mode === 'fake') {
                document.getElementById('simulateSection').style.display = 'block';
            }
            
            startPolling(orderId);
            startTimer(900); // 15 minutes
        } else {
            alert('Payment creation failed: ' + (data.error || 'Unknown error'));
            btn.innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
        btn.innerHTML = '<i class="fas fa-qrcode"></i> Generate QR Code';
        btn.disabled = false;
    });
}

function startPolling(orderId) {
    stopPolling();
    pollingInterval = setInterval(() => {
        fetch('api/payment/status.php?order_id=' + orderId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'paid') {
                stopPolling();
                stopTimer();
                document.getElementById('paymentStatus').innerHTML = 
                    '<div class="status-success"><i class="fas fa-check"></i> Payment successful! Redirecting...</div>';
                setTimeout(() => {
                    window.location.href = 'vpn_dashboard.php?payment=success';
                }, 2000);
            } else if (data.status === 'failed') {
                stopPolling();
                document.getElementById('paymentStatus').innerHTML = 
                    '<div class="status-failed"><i class="fas fa-times"></i> Payment failed</div>';
            }
        });
    }, 3000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

function startTimer(seconds) {
    stopTimer();
    let remaining = seconds;
    const timerEl = document.getElementById('qrTimer');
    
    timerInterval = setInterval(() => {
        remaining--;
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        if (remaining <= 0) {
            stopTimer();
            timerEl.textContent = 'Expired';
            stopPolling();
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function simulatePayment() {
    if (!orderId) return;
    
    const simulateBtn = document.querySelector('.simulate-btn');
    simulateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    simulateBtn.disabled = true;
    
    fetch('api/payment/status.php?order_id=' + orderId + '&simulate_paid=1')
    .then(res => res.json())
    .then(data => {
        if (data.status === 'paid') {
            stopPolling();
            stopTimer();
            document.getElementById('paymentStatus').innerHTML = 
                '<div class="status-success"><i class="fas fa-check"></i> Payment successful! Redirecting...</div>';
            document.getElementById('simulateSection').style.display = 'none';
            setTimeout(() => {
                window.location.href = 'vpn_dashboard.php?payment=success';
            }, 1500);
        } else {
            simulateBtn.innerHTML = '<i class="fas fa-vial"></i> Simulate Payment (Test Only)';
            simulateBtn.disabled = false;
            alert('Simulation failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        simulateBtn.innerHTML = '<i class="fas fa-vial"></i> Simulate Payment (Test Only)';
        simulateBtn.disabled = false;
        alert('Error: ' + err.message);
    });
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
}
</script>

</body>
</html>
