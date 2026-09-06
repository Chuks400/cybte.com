<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';

require_login('login.php');

$user = null;
$identityCount = 0;
$identityLatest = null;
$vaultCount = 0;
$vpnSubscription = null;
$dbError = false;

try {
    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare('SELECT id, name, email, role, email_verified, email_verified_at, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user) {
        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM identity_verifications WHERE user_id = :user_id');
            $stmt->execute([':user_id' => (int)$user['id']]);
            $identityCount = (int)$stmt->fetchColumn();

            $stmt = $conn->prepare('SELECT status, created_at FROM identity_verifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
            $stmt->execute([':user_id' => (int)$user['id']]);
            $identityLatest = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $identityCount = 0;
        }

        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM vault_documents WHERE user_id = :user_id');
            $stmt->execute([':user_id' => (int)$user['id']]);
            $vaultCount = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $vaultCount = 0;
        }

        try {
            $stmt = $conn->prepare("SELECT plan, status, expiry_date FROM subscriptions WHERE user_id = :user_id AND service_type = 'vpn' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([':user_id' => (int)$user['id']]);
            $vpnSubscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $vpnSubscription = null;
        }
    }
} catch (Throwable $e) {
    error_log('Dashboard database error: ' . $e->getMessage());
    $dbError = true;
}

if (!$user && !$dbError) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$name = $user['name'] ?? ($_SESSION['user_name'] ?? 'Cybte user');
$email = $user['email'] ?? '';
$emailVerified = !empty($user['email_verified']);
$memberSince = !empty($user['created_at']) ? date('M Y', strtotime((string)$user['created_at'])) : '—';
$vpnStatus = $vpnSubscription ? ucfirst((string)$vpnSubscription['status']) : 'Not activated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Security Workspace — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{margin:0;background:#040913;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.workspace{min-height:100vh;display:grid;grid-template-columns:245px 1fr}.ws-sidebar{position:sticky;top:0;height:100vh;padding:28px 20px;border-right:1px solid rgba(255,255,255,.07);background:#06101c}.ws-logo img{width:96px}.ws-nav{display:grid;gap:7px;margin-top:40px}.ws-nav a{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:8px;color:#8196ac;text-decoration:none;font-size:.86rem}.ws-nav a.active,.ws-nav a:hover{background:rgba(59,231,255,.075);color:#e9faff}.ws-nav i{width:18px;color:#3be7ff}.ws-bottom{position:absolute;left:20px;right:20px;bottom:25px}.ws-main{padding:38px 44px 70px;max-width:1500px;width:100%}.ws-head{display:flex;justify-content:space-between;gap:25px;align-items:center}.ws-head h1{font-size:2rem;letter-spacing:-.035em;margin:0}.ws-head p{color:#7f93aa;margin-top:8px}.account-pill{padding:9px 12px;border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#9fb0c2;font-size:.78rem}.status-banner{margin:25px 0;padding:14px 16px;border-radius:10px;border:1px solid rgba(84,230,165,.16);background:rgba(84,230,165,.05);color:#8fe8be;font-size:.83rem}.status-banner.warn{border-color:rgba(255,190,71,.18);background:rgba(255,190,71,.05);color:#d8bd87}.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:28px 0}.metric{padding:20px;border-radius:13px;border:1px solid rgba(123,196,255,.12);background:linear-gradient(180deg,rgba(11,27,45,.9),rgba(7,17,29,.9))}.metric small{color:#6e849b;text-transform:uppercase;letter-spacing:.08em;font-size:.61rem}.metric strong{display:block;font-size:1.65rem;margin-top:9px}.metric span{display:block;color:#8194aa;font-size:.74rem;margin-top:5px}.section-title{display:flex;justify-content:space-between;align-items:end;margin:38px 0 14px}.section-title h2{font-size:1.15rem;margin:0}.section-title span{font-size:.7rem;color:#667b91}.service-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.service-card{padding:23px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523;min-height:235px;display:flex;flex-direction:column}.service-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:10px;color:#3be7ff;background:rgba(59,231,255,.08);border:1px solid rgba(59,231,255,.12);margin-bottom:20px}.service-card h3{font-size:1rem;margin:0 0 9px}.service-card p{color:#8396aa;font-size:.79rem;line-height:1.65;margin:0}.service-meta{margin-top:auto;padding-top:20px;display:flex;justify-content:space-between;align-items:center;gap:10px}.service-meta span{font-size:.68rem;color:#71869d}.service-link{color:#3be7ff;text-decoration:none;font-size:.76rem;font-weight:800}.account-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:14px}.account-card{padding:23px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.account-row{display:flex;justify-content:space-between;gap:20px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.055);font-size:.78rem}.account-row:last-child{border-bottom:0}.account-row span{color:#70859b}.account-row b{font-weight:650;color:#c7d3df;text-align:right}.empty-note{color:#6d8299;font-size:.76rem;line-height:1.6}.db-alert{padding:16px;border-radius:10px;border:1px solid rgba(255,91,91,.18);background:rgba(255,91,91,.06);color:#ffaaaa;margin:22px 0}@media(max-width:1100px){.metric-grid{grid-template-columns:1fr 1fr}.service-grid{grid-template-columns:1fr 1fr}}@media(max-width:780px){.workspace{display:block}.ws-sidebar{position:static;height:auto;padding:16px 18px}.ws-logo{display:flex;justify-content:center}.ws-nav{display:flex;overflow-x:auto;margin-top:16px}.ws-nav a{white-space:nowrap}.ws-bottom{position:static;margin-top:8px}.ws-main{padding:28px 18px 60px}.ws-head{display:block}.account-pill{display:inline-block;margin-top:13px}.service-grid,.account-grid{grid-template-columns:1fr}}@media(max-width:480px){.metric-grid{grid-template-columns:1fr}.service-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="workspace">
<aside class="ws-sidebar">
<div class="ws-logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a></div>
<nav class="ws-nav">
<a class="active" href="dashboard.php"><i class="fas fa-grid-2"></i> Overview</a>
<a href="fraud.php"><i class="fas fa-wave-square"></i> Fraud Intelligence</a>
<a href="verify.php"><i class="fas fa-fingerprint"></i> Identity</a>
<a href="scan.php"><i class="fas fa-shield-virus"></i> Cyber Protection</a>
<a href="vault.php"><i class="fas fa-vault"></i> Secure Vault</a>
<a href="vpn_dashboard.php"><i class="fas fa-lock"></i> Cybte VPN</a>
</nav>
<div class="ws-bottom"><nav class="ws-nav"><a href="index.php"><i class="fas fa-globe"></i> Public site</a><a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</a></nav></div>
</aside>
<main class="ws-main">
<div class="ws-head"><div><h1>Security workspace</h1><p>Welcome back, <?php echo htmlspecialchars((string)$name); ?>.</p></div><div class="account-pill"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars((string)$email); ?></div></div>
<?php if ($dbError): ?><div class="db-alert"><i class="fas fa-triangle-exclamation"></i> Account data could not be loaded. Check the server database environment before production deployment.</div><?php elseif ($emailVerified): ?><div class="status-banner"><i class="fas fa-circle-check"></i> Account email verified. Your Cybte AI workspace is active.</div><?php else: ?><div class="status-banner warn"><i class="fas fa-circle-info"></i> Verify your email address to complete account setup.</div><?php endif; ?>

<div class="metric-grid">
<div class="metric"><small>Secure Vault</small><strong><?php echo $vaultCount; ?></strong><span>encrypted documents</span></div>
<div class="metric"><small>Identity cases</small><strong><?php echo $identityCount; ?></strong><span><?php echo $identityLatest ? htmlspecialchars((string)$identityLatest['status']) : 'no verification submitted'; ?></span></div>
<div class="metric"><small>Cybte VPN</small><strong><?php echo htmlspecialchars($vpnStatus); ?></strong><span><?php echo $vpnSubscription && !empty($vpnSubscription['expiry_date']) ? 'until ' . htmlspecialchars(date('M j, Y', strtotime((string)$vpnSubscription['expiry_date']))) : 'subscription status'; ?></span></div>
<div class="metric"><small>Member since</small><strong><?php echo htmlspecialchars($memberSince); ?></strong><span>Cybte AI account</span></div>
</div>

<div class="section-title"><h2>Your Cybte AI products</h2><span>Availability depends on service configuration</span></div>
<div class="service-grid">
<article class="service-card"><div class="service-icon"><i class="fas fa-chart-line"></i></div><h3>AI Fraud Detection</h3><p>Analyze transaction signals and review risk assessments through the fraud intelligence service.</p><div class="service-meta"><span>Service module</span><a class="service-link" href="fraud.php">Open <i class="fas fa-arrow-right"></i></a></div></article>
<article class="service-card"><div class="service-icon"><i class="fas fa-fingerprint"></i></div><h3>Identity Verification</h3><p>Manage identity checks and verification records tied to your Cybte AI account.</p><div class="service-meta"><span><?php echo $identityCount; ?> case<?php echo $identityCount === 1 ? '' : 's'; ?></span><a class="service-link" href="verify.php">Open <i class="fas fa-arrow-right"></i></a></div></article>
<article class="service-card"><div class="service-icon"><i class="fas fa-shield-virus"></i></div><h3>Cybersecurity Protection</h3><p>Access vulnerability assessment and cyber-risk tooling configured for the platform.</p><div class="service-meta"><span>Service module</span><a class="service-link" href="scan.php">Open <i class="fas fa-arrow-right"></i></a></div></article>
<article class="service-card"><div class="service-icon"><i class="fas fa-vault"></i></div><h3>Cybte Secure Vault</h3><p>Upload supported files into an encrypted, account-scoped document workspace.</p><div class="service-meta"><span><?php echo $vaultCount; ?> document<?php echo $vaultCount === 1 ? '' : 's'; ?></span><a class="service-link" href="vault.php">Open <i class="fas fa-arrow-right"></i></a></div></article>
<article class="service-card"><div class="service-icon"><i class="fas fa-lock"></i></div><h3>Cybte VPN</h3><p>Manage private connectivity and VPN subscription access where your plan enables it.</p><div class="service-meta"><span><?php echo htmlspecialchars($vpnStatus); ?></span><a class="service-link" href="vpn_dashboard.php">Open <i class="fas fa-arrow-right"></i></a></div></article>
<article class="service-card"><div class="service-icon"><i class="fas fa-code"></i></div><h3>Enterprise Integrations</h3><p>API and organization integrations remain part of the Cybte AI enterprise roadmap.</p><div class="service-meta"><span>Roadmap</span><a class="service-link" href="index.php#contact">Discuss access <i class="fas fa-arrow-right"></i></a></div></article>
</div>

<div class="section-title"><h2>Account</h2><span>No fabricated security statistics</span></div>
<div class="account-grid">
<div class="account-card"><div class="account-row"><span>Name</span><b><?php echo htmlspecialchars((string)$name); ?></b></div><div class="account-row"><span>Email</span><b><?php echo htmlspecialchars((string)$email); ?></b></div><div class="account-row"><span>Email verification</span><b><?php echo $emailVerified ? 'Verified' : 'Pending'; ?></b></div><div class="account-row"><span>Role</span><b><?php echo htmlspecialchars((string)($user['role'] ?? 'user')); ?></b></div></div>
<div class="account-card"><h3 style="margin-top:0">Platform note</h3><p class="empty-note">This dashboard intentionally shows account-backed values only. Fraud, vulnerability and enterprise statistics will appear here only after those services expose user-scoped production data. This avoids presenting demo numbers as real customer telemetry.</p></div>
</div>
</main>
</div>
</body>
</html>
