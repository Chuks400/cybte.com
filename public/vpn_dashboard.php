<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';

require_login('login.php');

$userId = (int)($_SESSION['user_id'] ?? 0);
$user = null;
$subscription = null;
$dbError = '';

try {
    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user) {
        $stmt = $conn->prepare("SELECT plan, status, start_date, expiry_date, created_at FROM subscriptions WHERE user_id = :user_id AND service_type = 'vpn' ORDER BY id DESC LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    error_log('VPN workspace database error: ' . $e->getMessage());
    $dbError = 'VPN account data could not be loaded right now.';
}

if (!$user && $dbError === '') {
    session_destroy();
    header('Location: login.php');
    exit();
}

$name = $user['name'] ?? ($_SESSION['user_name'] ?? 'Cybte user');
$email = $user['email'] ?? '';
$plan = $subscription['plan'] ?? 'No plan';
$status = strtolower((string)($subscription['status'] ?? 'not activated'));
$statusLabel = $subscription ? ucfirst((string)$subscription['status']) : 'Not activated';
$expiry = !empty($subscription['expiry_date']) ? date('M j, Y', strtotime((string)$subscription['expiry_date'])) : '—';
$memberSince = !empty($user['created_at']) ? date('M Y', strtotime((string)$user['created_at'])) : '—';
$active = $status === 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Cybte VPN — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{margin:0;background:#040913;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.workspace{min-height:100vh;display:grid;grid-template-columns:245px 1fr}.ws-sidebar{position:sticky;top:0;height:100vh;padding:28px 20px;border-right:1px solid rgba(255,255,255,.07);background:#06101c}.ws-logo img{width:96px}.ws-nav{display:grid;gap:7px;margin-top:40px}.ws-nav a{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:8px;color:#8196ac;text-decoration:none;font-size:.86rem}.ws-nav a.active,.ws-nav a:hover{background:rgba(59,231,255,.075);color:#e9faff}.ws-nav i{width:18px;color:#3be7ff}.ws-bottom{position:absolute;left:20px;right:20px;bottom:25px}.ws-main{padding:38px 44px 70px;max-width:1400px;width:100%}.ws-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}.ws-head h1{font-size:2rem;letter-spacing:-.035em;margin:0}.ws-head p{color:#7f93aa;margin:8px 0 0;max-width:760px;line-height:1.7}.badge{padding:9px 12px;border:1px solid rgba(59,231,255,.14);background:rgba(59,231,255,.05);border-radius:8px;color:#8edff0;font-size:.76rem}.notice{margin:24px 0;padding:14px 16px;border-radius:10px;font-size:.82rem}.notice.error{border:1px solid rgba(255,91,91,.18);background:rgba(255,91,91,.06);color:#ffaaaa}.vpn-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:28px}.card{padding:23px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.card small{color:#6e849b;text-transform:uppercase;letter-spacing:.08em;font-size:.61rem}.card strong{display:block;font-size:1.45rem;margin-top:10px}.card p{color:#8194aa;font-size:.77rem;line-height:1.65;margin:8px 0 0}.state{display:inline-block;margin-top:12px;padding:6px 9px;border-radius:999px;font-size:.68rem;font-weight:800;text-transform:uppercase}.state.active{color:#83e4b6;background:rgba(84,230,165,.08)}.state.inactive{color:#e1bd76;background:rgba(255,190,71,.08)}.wide{grid-column:span 3}.roadmap{display:grid;grid-template-columns:1.1fr .9fr;gap:18px;margin-top:18px}.panel{padding:24px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.panel h2{font-size:1.05rem;margin:0 0 10px}.panel p{color:#8194aa;font-size:.78rem;line-height:1.7}.steps{display:grid;gap:10px;margin-top:16px}.step{display:flex;gap:12px;align-items:flex-start;padding:13px;border:1px solid rgba(255,255,255,.06);border-radius:10px;background:#07111f}.step i{color:#3be7ff;margin-top:3px}.step b{display:block;font-size:.8rem}.step span{display:block;color:#70859b;font-size:.73rem;line-height:1.55;margin-top:3px}.kv{display:flex;justify-content:space-between;gap:20px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.055);font-size:.78rem}.kv:last-child{border-bottom:0}.kv span{color:#70859b}.kv b{text-align:right;color:#c7d3df}.security-note{margin-top:18px;padding:16px;border:1px solid rgba(59,231,255,.1);border-radius:12px;background:rgba(59,231,255,.025);color:#70859b;font-size:.75rem;line-height:1.65}@media(max-width:950px){.vpn-grid{grid-template-columns:1fr 1fr}.wide{grid-column:span 2}.roadmap{grid-template-columns:1fr}}@media(max-width:780px){.workspace{display:block}.ws-sidebar{position:static;height:auto;padding:16px 18px}.ws-logo{display:flex;justify-content:center}.ws-nav{display:flex;overflow-x:auto;margin-top:16px}.ws-nav a{white-space:nowrap}.ws-bottom{position:static;margin-top:8px}.ws-main{padding:28px 18px 60px}.ws-head{display:block}.badge{display:inline-block;margin-top:14px}}@media(max-width:560px){.vpn-grid{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>
</head>
<body>
<div class="workspace">
<aside class="ws-sidebar">
<div class="ws-logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a></div>
<nav class="ws-nav">
<a href="dashboard.php"><i class="fas fa-grid-2"></i> Overview</a>
<a href="fraud.php"><i class="fas fa-wave-square"></i> Fraud Intelligence</a>
<a href="verify.php"><i class="fas fa-fingerprint"></i> Identity</a>
<a href="scan.php"><i class="fas fa-shield-virus"></i> Cyber Protection</a>
<a href="vault.php"><i class="fas fa-vault"></i> Secure Vault</a>
<a class="active" href="vpn_dashboard.php"><i class="fas fa-lock"></i> Cybte VPN</a>
</nav>
<div class="ws-bottom"><nav class="ws-nav"><a href="index.php"><i class="fas fa-globe"></i> Public site</a><a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</a></nav></div>
</aside>
<main class="ws-main">
<div class="ws-head"><div><h1>Cybte VPN</h1><p>Manage secure-connectivity entitlement from the same Cybte AI account. VPN tunnel provisioning is shown only when a verified subscription and production VPN backend are configured.</p></div><div class="badge"><i class="fas fa-lock"></i> Secure connectivity workspace</div></div>
<?php if ($dbError !== ''): ?><div class="notice error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($dbError); ?></div><?php endif; ?>

<div class="vpn-grid">
<div class="card"><small>Subscription</small><strong><?php echo htmlspecialchars($statusLabel); ?></strong><span class="state <?php echo $active ? 'active' : 'inactive'; ?>"><?php echo $active ? 'Active' : 'Not active'; ?></span></div>
<div class="card"><small>Plan</small><strong><?php echo htmlspecialchars((string)$plan); ?></strong><p>Account-backed plan value only.</p></div>
<div class="card"><small>Expiry</small><strong><?php echo htmlspecialchars($expiry); ?></strong><p>No fabricated renewal date is shown.</p></div>
<div class="card wide"><small>Provisioning state</small><strong><?php echo $active ? 'Subscription recorded' : 'VPN not provisioned'; ?></strong><p><?php echo $active ? 'A subscription exists, but tunnel credentials should only be issued after the production VPN control plane confirms provisioning.' : 'No VPN subscription is active for this account. This workspace will not self-activate a plan or generate credentials without a verified provisioning flow.'; ?></p></div>
</div>

<div class="roadmap">
<section class="panel"><h2>Production connectivity flow</h2><p>Cybte VPN should use a verified subscription and a hardened VPN control plane rather than generating access directly from a browser request.</p><div class="steps">
<div class="step"><i class="fas fa-credit-card"></i><div><b>1. Verified entitlement</b><span>Confirm a valid plan through the approved billing or enterprise entitlement service.</span></div></div>
<div class="step"><i class="fas fa-server"></i><div><b>2. Controlled provisioning</b><span>Create the VPN account through the configured backend with server-side authorization and audit logging.</span></div></div>
<div class="step"><i class="fas fa-key"></i><div><b>3. Secure credential delivery</b><span>Expose connection credentials only to the authenticated account after provisioning succeeds.</span></div></div>
<div class="step"><i class="fas fa-chart-line"></i><div><b>4. Usage and revocation</b><span>Show real traffic, expiry and revocation state only when returned by the VPN backend.</span></div></div>
</div></section>
<section class="panel"><h2>Account</h2><div class="kv"><span>Name</span><b><?php echo htmlspecialchars((string)$name); ?></b></div><div class="kv"><span>Email</span><b><?php echo htmlspecialchars((string)$email); ?></b></div><div class="kv"><span>Member since</span><b><?php echo htmlspecialchars($memberSince); ?></b></div><div class="kv"><span>VPN status</span><b><?php echo htmlspecialchars($statusLabel); ?></b></div><div class="security-note"><i class="fas fa-shield-halved"></i> This refresh deliberately removed demo server latency/load numbers, browser-triggered subscription activation and verbose backend error display. Real server telemetry will appear only when the production VPN service supplies it.</div></section>
</div>
</main>
</div>
</body>
</html>
