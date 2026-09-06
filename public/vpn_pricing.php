<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/config/database.php';
security_start_session();

$isLoggedIn = !empty($_SESSION['user_id']);
$csrfToken = csrf_token();
$plans = [];
$pricingUnavailable = false;

try {
    $database = new Database();
    $conn = $database->connect();
    $stmt = $conn->query('SELECT plan_key, name, price_cny, traffic_gb, device_limit, duration_days FROM payment_plans WHERE is_active = 1 ORDER BY sort_order');
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$plans) {
        $pricingUnavailable = true;
    }
} catch (Throwable $e) {
    error_log('VPN pricing load error: ' . $e->getMessage());
    $pricingUnavailable = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cybte VPN Plans — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="assets/css/payment.css?v=<?php echo filemtime(__DIR__ . '/assets/css/payment.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.checkout-note{max-width:850px;margin:0 auto 30px;padding:14px 18px;border:1px solid rgba(59,231,255,.16);background:rgba(59,231,255,.045);border-radius:10px;color:#8ea4b9;font-size:.82rem}.pricing-error{max-width:800px;margin:60px auto;padding:26px;border:1px solid rgba(255,91,91,.2);background:rgba(255,91,91,.06);border-radius:14px;text-align:center}.price-secure-badge{display:inline-flex;align-items:center;gap:7px;font-size:.72rem;color:#72e9b8;margin-top:8px}.login-required{padding:18px;text-align:center;color:#91a3ba}.login-required a{color:#3be7ff}.payment-error{display:none;margin:14px 0;padding:11px 13px;border-radius:8px;color:#ffaaaa;background:rgba(255,91,91,.07);border:1px solid rgba(255,91,91,.16)}
</style>
</head>
<body>
<div class="starry-background"></div><div class="stars"></div>
<header><div class="container"><div class="header-content"><div class="logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI" class="logo-img"></a></div><nav><a href="vpn.php">VPN Home</a><a href="vpn_download.php">Download</a><a href="vpn_pricing.php" class="active">Plans</a><a href="vpn_security.php">Security</a><?php if ($isLoggedIn): ?><a href="dashboard.php">Main Dashboard</a><a href="vpn_dashboard.php" class="sign-in-btn">VPN Dashboard</a><?php else: ?><a href="signup.php">Create Account</a><a href="login.php" class="sign-in-btn">Sign In</a><?php endif; ?></nav></div></div></header>

<section class="vpn-pricing" id="vpn-pricing"><div class="container">
<h2 class="vpn-section-title"><i class="fas fa-shield-halved"></i> Cybte VPN Plans</h2>
<div class="checkout-note"><i class="fas fa-lock"></i> Checkout prices are validated on the Cybte server. Values sent by the browser are never treated as the authoritative payment amount.</div>
<?php if ($pricingUnavailable): ?>
<div class="pricing-error"><h3>Plans are temporarily unavailable</h3><p>We could not load current pricing. Please try again later rather than relying on cached or fallback prices.</p></div>
<?php else: ?>
<div class="vpn-pricing-grid cny-pricing">
<?php foreach ($plans as $index => $plan): $popular = $plan['plan_key'] === 'regular'; ?>
<div class="vpn-price-card <?php echo $popular ? 'featured' : ''; ?>">
<?php if ($popular): ?><div class="vpn-popular">POPULAR</div><?php endif; ?>
<div class="plan-header"><span class="plan-icon"><i class="fas fa-shield-halved"></i></span><h3><?php echo htmlspecialchars((string)$plan['name']); ?></h3></div>
<div class="vpn-price"><span class="currency">¥</span><?php echo number_format((float)$plan['price_cny'], 2); ?> <span class="period">CNY / <?php echo (int)($plan['duration_days'] ?? 30); ?> days</span></div>
<ul class="plan-features">
<li><i class="fas fa-cloud"></i> <?php echo (int)$plan['traffic_gb'] >= 1024 ? '1 TB+' : (int)$plan['traffic_gb'] . ' GB'; ?> traffic allowance</li>
<li><i class="fas fa-display"></i> Up to <?php echo (int)$plan['device_limit']; ?> device<?php echo (int)$plan['device_limit'] === 1 ? '' : 's'; ?></li>
<li><i class="fas fa-lock"></i> Encrypted VPN connectivity</li>
<li><i class="fas fa-gauge-high"></i> Service access subject to server availability</li>
</ul>
<span class="price-secure-badge"><i class="fas fa-circle-check"></i> Server-validated price</span>
<?php if ($isLoggedIn): ?><button class="vpn-buy subscribe-btn" type="button" onclick='openPaymentModal(<?php echo json_encode((string)$plan['plan_key']); ?>, <?php echo json_encode((float)$plan['price_cny']); ?>, <?php echo json_encode((string)$plan['name']); ?>)'><i class="fas fa-credit-card"></i> Continue to payment</button><?php else: ?><div class="login-required"><a href="login.php">Sign in</a> to purchase this plan.</div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></section>

<?php if ($isLoggedIn && !$pricingUnavailable): ?>
<div id="paymentModal" class="payment-modal" aria-hidden="true"><div class="payment-modal-content">
<div class="payment-modal-header"><h3><i class="fas fa-lock"></i> Secure Payment</h3><button class="close-modal" type="button" onclick="closePaymentModal()">&times;</button></div>
<div class="payment-modal-body">
<div class="order-summary"><h4>Order Summary</h4><div class="order-details"><span id="orderPlanName">Plan</span><span class="order-price">¥<span id="orderAmount">0.00</span> CNY</span></div></div>
<div id="paymentError" class="payment-error"></div>
<div class="payment-methods"><h4>Select Payment Method</h4><div class="payment-options">
<div class="pay-card" onclick="selectPayment('alipay')" id="alipayCard"><div class="pay-icon"><i class="fas fa-qrcode"></i></div><div class="pay-info"><span class="pay-name">Alipay</span><span class="pay-desc">支付宝</span></div><div class="pay-check"><i class="fas fa-check-circle"></i></div></div>
<div class="pay-card" onclick="selectPayment('wechat')" id="wechatCard"><div class="pay-icon"><i class="fas fa-qrcode"></i></div><div class="pay-info"><span class="pay-name">WeChat Pay</span><span class="pay-desc">微信支付</span></div><div class="pay-check"><i class="fas fa-check-circle"></i></div></div>
<div class="pay-card" onclick="selectPayment('paypal')" id="paypalCard"><div class="pay-icon"><i class="fab fa-paypal"></i></div><div class="pay-info"><span class="pay-name">PayPal</span><span class="pay-desc">PayPal checkout</span></div><div class="pay-check"><i class="fas fa-check-circle"></i></div></div>
</div></div>
<div class="qr-section" id="qrSection" style="display:none"><div class="qr-container"><img id="paymentQR" src="" alt="Payment QR code" width="220" height="220"><p class="qr-instruction">Complete payment using <span id="qrApp">your payment app</span>.</p><div class="qr-timer"><i class="fas fa-clock"></i> Expires in <span id="qrTimer">15:00</span></div></div><div class="payment-status" id="paymentStatus"><div class="status-pending"><i class="fas fa-spinner fa-spin"></i> Waiting for provider confirmation…</div></div></div>
</div>
<div class="payment-modal-footer"><button class="pay-confirm-btn" id="confirmBtn" type="button" onclick="createPayment()"><i class="fas fa-arrow-right"></i> Continue</button><button class="pay-cancel-btn" type="button" onclick="closePaymentModal()">Cancel</button></div>
</div></div>
<script>
const csrfToken=<?php echo json_encode($csrfToken); ?>;
let selectedPlan=null,selectedAmount=0,selectedMethod='alipay',orderId=null,pollingInterval=null,timerInterval=null;
function openPaymentModal(plan,amount,name){selectedPlan=plan;selectedAmount=Number(amount);document.getElementById('orderPlanName').textContent=name;document.getElementById('orderAmount').textContent=selectedAmount.toFixed(2);document.getElementById('paymentModal').style.display='flex';document.getElementById('paymentModal').setAttribute('aria-hidden','false');selectPayment('alipay');resetPayment();}
function closePaymentModal(){document.getElementById('paymentModal').style.display='none';document.getElementById('paymentModal').setAttribute('aria-hidden','true');stopPolling();stopTimer();}
function selectPayment(method){selectedMethod=method;document.querySelectorAll('.pay-card').forEach(c=>c.classList.remove('selected'));const card=document.getElementById(method+'Card');if(card)card.classList.add('selected');const names={alipay:'Alipay',wechat:'WeChat Pay',paypal:'PayPal'};document.getElementById('qrApp').textContent=names[method]||'payment provider';}
function showError(message){const el=document.getElementById('paymentError');el.textContent=message;el.style.display='block';}
function resetPayment(){document.getElementById('paymentError').style.display='none';document.getElementById('qrSection').style.display='none';const b=document.getElementById('confirmBtn');b.style.display='block';b.disabled=false;b.innerHTML='<i class="fas fa-arrow-right"></i> Continue';}
async function createPayment(){const btn=document.getElementById('confirmBtn');btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Processing…';document.getElementById('paymentError').style.display='none';try{const res=await fetch('api/payment/create.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({method:selectedMethod,plan:selectedPlan})});const data=await res.json();if(!res.ok||!data.success)throw new Error(data.error||'Payment could not be created');orderId=data.order_id;if(Number(data.amount)!==selectedAmount){selectedAmount=Number(data.amount);document.getElementById('orderAmount').textContent=selectedAmount.toFixed(2);}if(selectedMethod==='paypal'&&data.qr_url&&/^https:\/\//i.test(data.qr_url)){window.location.assign(data.qr_url);return;}if(!data.qr_url)throw new Error('The payment provider did not return a checkout code.');document.getElementById('paymentQR').src=data.qr_url;document.getElementById('qrSection').style.display='block';btn.style.display='none';startPolling(orderId);startTimer(900);}catch(err){showError(err.message||'Payment error');btn.disabled=false;btn.innerHTML='<i class="fas fa-arrow-right"></i> Try again';}}
function startPolling(id){stopPolling();pollingInterval=setInterval(async()=>{try{const res=await fetch('api/payment/status.php?order_id='+encodeURIComponent(id),{cache:'no-store'});const data=await res.json();if(data.status==='paid'){stopPolling();stopTimer();document.getElementById('paymentStatus').innerHTML='<div class="status-success"><i class="fas fa-check"></i> Payment confirmed. Redirecting…</div>';setTimeout(()=>window.location.href='vpn_dashboard.php?payment=success',1200);}else if(['failed','expired'].includes(data.status)){stopPolling();stopTimer();document.getElementById('paymentStatus').innerHTML='<div class="status-failed"><i class="fas fa-times"></i> Payment '+data.status+'</div>';}}catch(e){}},4000);}
function stopPolling(){if(pollingInterval){clearInterval(pollingInterval);pollingInterval=null;}}
function startTimer(seconds){stopTimer();let remaining=seconds;timerInterval=setInterval(()=>{remaining--;const m=Math.floor(remaining/60),s=remaining%60;document.getElementById('qrTimer').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');if(remaining<=0){stopTimer();stopPolling();document.getElementById('qrTimer').textContent='Expired';}},1000);}
function stopTimer(){if(timerInterval){clearInterval(timerInterval);timerInterval=null;}}
window.addEventListener('click',e=>{if(e.target===document.getElementById('paymentModal'))closePaymentModal();});
</script>
<?php endif; ?>
</body>
</html>
