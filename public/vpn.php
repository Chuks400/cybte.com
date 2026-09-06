<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
security_start_session();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Cybte VPN is the secure-connectivity layer of the Cybte AI cybersecurity platform.">
<title>Cybte VPN — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.vpn-product{min-height:100vh;background:#040913;color:#fff}.vpn-product .container{max-width:1180px;margin:auto;padding:0 24px}.vpn-nav{display:flex;align-items:center;justify-content:space-between;padding:24px 0;border-bottom:1px solid rgba(255,255,255,.07)}.vpn-nav img{width:90px}.vpn-nav-links{display:flex;gap:22px;align-items:center}.vpn-nav a{color:#92a5ba;text-decoration:none}.vpn-nav .cta{padding:11px 16px;border-radius:999px;background:#38dff5;color:#03111d;font-weight:800}.vpn-hero{display:grid;grid-template-columns:1.1fr .9fr;gap:48px;align-items:center;padding:90px 0}.vpn-kicker{color:#39e7ff;text-transform:uppercase;letter-spacing:.14em;font-weight:800;font-size:.76rem}.vpn-hero h1{font-size:clamp(3rem,6vw,5.7rem);line-height:.98;letter-spacing:-.055em;margin:18px 0 24px}.vpn-hero p{color:#8fa3ba;line-height:1.8;font-size:1.05rem;max-width:720px}.vpn-actions{display:flex;gap:14px;margin-top:30px;flex-wrap:wrap}.vpn-actions a{padding:14px 20px;border-radius:10px;text-decoration:none;font-weight:800}.vpn-actions .primary{background:linear-gradient(90deg,#35e2f3,#65a8ff);color:#04101b}.vpn-actions .secondary{border:1px solid rgba(255,255,255,.12);color:#d9e6f2}.vpn-visual{border:1px solid rgba(60,219,255,.14);background:#071523;border-radius:24px;padding:30px;min-height:350px;display:grid;place-items:center}.vpn-core{width:230px;height:230px;border:1px solid rgba(60,219,255,.35);border-radius:50%;display:grid;place-items:center;text-align:center;box-shadow:0 0 70px rgba(54,223,255,.08)}.vpn-core i{font-size:3rem;color:#39e7ff}.vpn-core strong{display:block;font-size:1.25rem;margin-top:12px}.vpn-core span{display:block;color:#7990a8;font-size:.78rem;margin-top:6px}.vpn-features{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding:0 0 90px}.vpn-feature{padding:24px;border:1px solid rgba(255,255,255,.08);background:#071421;border-radius:16px}.vpn-feature i{color:#39e7ff;font-size:1.3rem}.vpn-feature h3{font-size:1.05rem;margin:18px 0 8px}.vpn-feature p{color:#8094aa;line-height:1.7;font-size:.84rem}.vpn-roadmap{margin-bottom:90px;padding:28px;border:1px solid rgba(59,231,255,.12);border-radius:18px;background:rgba(59,231,255,.025)}.vpn-roadmap h2{margin-top:0}.vpn-roadmap p{color:#8196ac;line-height:1.75}.vpn-roadmap ul{color:#8ba0b5;line-height:1.9}.vpn-footer{padding:28px 0 38px;border-top:1px solid rgba(255,255,255,.07);color:#6f8399;font-size:.8rem}@media(max-width:850px){.vpn-hero{grid-template-columns:1fr;padding:60px 0}.vpn-features{grid-template-columns:1fr}.vpn-nav-links a:not(.cta){display:none}}
</style>
</head>
<body class="vpn-product">
<div class="container">
<nav class="vpn-nav">
<a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a>
<div class="vpn-nav-links"><a href="index.php#products">Products</a><a href="index.php#enterprise">Enterprise</a><?php if ($isLoggedIn): ?><a class="cta" href="vpn_dashboard.php">Open VPN workspace</a><?php else: ?><a href="login.php">Sign in</a><a class="cta" href="signup.php">Create account</a><?php endif; ?></div>
</nav>
<section class="vpn-hero">
<div><div class="vpn-kicker">Cybte secure connectivity</div><h1>Private connectivity inside one security ecosystem.</h1><p>Cybte VPN is the secure-connectivity layer of Cybte AI. The product direction is encrypted tunnel access, account-based entitlement, secure provisioning and auditable lifecycle management without exposing infrastructure controls directly to the browser.</p><div class="vpn-actions"><?php if ($isLoggedIn): ?><a class="primary" href="vpn_dashboard.php">Open your VPN workspace</a><?php else: ?><a class="primary" href="signup.php">Create Cybte account</a><a class="secondary" href="login.php">Sign in</a><?php endif; ?><a class="secondary" href="index.php#contact">Enterprise partnership</a></div></div>
<div class="vpn-visual"><div class="vpn-core"><div><i class="fas fa-shield-halved"></i><strong>CYBTE VPN</strong><span>Secure connectivity layer</span></div></div></div>
</section>
<section class="vpn-features">
<div class="vpn-feature"><i class="fas fa-lock"></i><h3>Encrypted tunnel vision</h3><p>Designed around modern encrypted connectivity with credentials delivered only after authenticated provisioning.</p></div>
<div class="vpn-feature"><i class="fas fa-user-shield"></i><h3>Account-backed access</h3><p>VPN entitlement belongs to the same Cybte AI identity used across the platform instead of a separate legacy VPN account.</p></div>
<div class="vpn-feature"><i class="fas fa-rotate"></i><h3>Lifecycle controls</h3><p>Production provisioning should support real status, expiry, revocation and usage data from the VPN backend.</p></div>
</section>
<section class="vpn-roadmap"><h2>Production roadmap</h2><p>The current refresh intentionally avoids fabricated server status, browser-triggered account activation and unverified credential generation.</p><ul><li>Verified subscription or enterprise entitlement</li><li>Hardened VPN control plane and server-side authorization</li><li>Secure credential issuance and revocation</li><li>Real telemetry only when returned by the VPN backend</li><li>Audit logs and operational monitoring before public launch</li></ul></section>
<footer class="vpn-footer">© <?php echo date('Y'); ?> Cybte AI · Protect. Verify. Detect. Store. Connect Securely.</footer>
</div>
</body>
</html>
