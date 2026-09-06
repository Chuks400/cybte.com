<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/controllers/FraudController.php';
require_once __DIR__ . '/../src/models/RiskScore.php';

security_start_session();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$error = security_flash('fraud_error') ?? '';
$success = security_flash('fraud_success') ?? '';
$result = $_SESSION['fraud_result'] ?? null;
unset($_SESSION['fraud_result']);
$assessments = [];
$serviceAvailable = true;

try {
    $riskModel = new RiskScore();
    $fraud = new FraudController($riskModel);
} catch (Throwable $e) {
    error_log('Fraud workspace init error: ' . $e->getMessage());
    $serviceAvailable = false;
    $error = 'Fraud Intelligence is temporarily unavailable. Check the database service and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$serviceAvailable) {
        security_flash('fraud_error', 'Fraud Intelligence is temporarily unavailable.');
    } elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {
        security_flash('fraud_error', 'Your session expired. Please try again.');
    } elseif (!security_rate_limit('fraud_assessment', 20, 900)) {
        security_flash('fraud_error', 'Too many assessments. Please wait before trying again.');
    } else {
        $transactionId = trim((string)($_POST['transaction_id'] ?? ''));
        $amountRaw = $_POST['amount'] ?? null;
        $location = (string)($_POST['location'] ?? 'known');
        $device = (string)($_POST['device'] ?? 'trusted');
        $amount = filter_var($amountRaw, FILTER_VALIDATE_FLOAT);

        if ($transactionId === '' || mb_strlen($transactionId) > 100) {
            security_flash('fraud_error', 'Enter a valid transaction reference.');
        } elseif ($amount === false || $amount < 0 || $amount > 1000000000) {
            security_flash('fraud_error', 'Enter a valid transaction amount.');
        } elseif (!in_array($location, ['known', 'unknown'], true) || !in_array($device, ['trusted', 'new'], true)) {
            security_flash('fraud_error', 'Invalid risk-signal selection.');
        } else {
            try {
                $analysis = $fraud->analyzeTransaction($userId, $transactionId, (float)$amount, $location, $device);
                $_SESSION['fraud_result'] = $analysis;
                security_clear_rate_limit('fraud_assessment');
                security_flash('fraud_success', 'Transaction assessment completed.');
            } catch (Throwable $e) {
                error_log('Fraud assessment error: ' . $e->getMessage());
                security_flash('fraud_error', 'The assessment could not be completed right now.');
            }
        }
    }
    header('Location: fraud.php');
    exit();
}

if ($serviceAvailable) {
    try {
        $assessments = $riskModel->listByUser($userId, 10);
    } catch (Throwable $e) {
        error_log('Fraud history error: ' . $e->getMessage());
        $error = $error ?: 'Recent fraud assessments could not be loaded.';
    }
}

function fraud_status_class(string $status): string
{
    $status = strtolower($status);
    if (str_contains($status, 'high')) return 'high';
    if (str_contains($status, 'medium')) return 'medium';
    return 'low';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Fraud Intelligence — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{margin:0;background:#040913;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.workspace{min-height:100vh;display:grid;grid-template-columns:245px 1fr}.ws-sidebar{position:sticky;top:0;height:100vh;padding:28px 20px;border-right:1px solid rgba(255,255,255,.07);background:#06101c}.ws-logo img{width:96px}.ws-nav{display:grid;gap:7px;margin-top:40px}.ws-nav a{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:8px;color:#8196ac;text-decoration:none;font-size:.86rem}.ws-nav a.active,.ws-nav a:hover{background:rgba(59,231,255,.075);color:#e9faff}.ws-nav i{width:18px;color:#3be7ff}.ws-bottom{position:absolute;left:20px;right:20px;bottom:25px}.ws-main{padding:38px 44px 70px;max-width:1400px;width:100%}.ws-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}.ws-head h1{font-size:2rem;letter-spacing:-.035em;margin:0}.ws-head p{color:#7f93aa;margin:8px 0 0;max-width:760px;line-height:1.7}.badge{padding:9px 12px;border:1px solid rgba(59,231,255,.14);background:rgba(59,231,255,.05);border-radius:8px;color:#8edff0;font-size:.76rem}.notice{margin:24px 0;padding:14px 16px;border-radius:10px;font-size:.82rem}.notice.success{border:1px solid rgba(84,230,165,.18);background:rgba(84,230,165,.06);color:#8fe8be}.notice.error{border:1px solid rgba(255,91,91,.18);background:rgba(255,91,91,.06);color:#ffaaaa}.fraud-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:18px;margin-top:28px}.panel{padding:24px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.panel h2{font-size:1.05rem;margin:0 0 7px}.panel>p{color:#8194aa;font-size:.79rem;line-height:1.65;margin:0 0 20px}.fraud-form label{display:block;color:#aebed0;font-size:.76rem;margin:14px 0 7px}.fraud-form input,.fraud-form select{width:100%;padding:14px 15px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#07111f;color:#fff;box-sizing:border-box}.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}.submit-btn{margin-top:20px;width:100%;border:0;border-radius:9px;padding:14px;background:linear-gradient(135deg,#3be7ff,#67a7ff);color:#03111a;font-weight:800;cursor:pointer}.result{margin-top:18px;padding:18px;border:1px solid rgba(59,231,255,.12);border-radius:12px;background:#07111f}.score{font-size:2rem;font-weight:900}.risk-pill{display:inline-block;margin-top:8px;padding:6px 9px;border-radius:999px;font-size:.68rem;font-weight:800;text-transform:uppercase}.risk-pill.low{color:#7de2b1;background:rgba(84,230,165,.08)}.risk-pill.medium{color:#e1bd76;background:rgba(255,190,71,.08)}.risk-pill.high{color:#ff9c9c;background:rgba(255,91,91,.08)}.history{display:grid;gap:10px}.item{display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;padding:14px 15px;border:1px solid rgba(255,255,255,.06);border-radius:10px;background:#07111f}.item small{display:block;color:#687e95;margin-top:4px}.note{margin-top:18px;padding:16px;border:1px solid rgba(59,231,255,.1);border-radius:12px;color:#70859b;font-size:.75rem;line-height:1.65;background:rgba(59,231,255,.025)}@media(max-width:900px){.fraud-grid{grid-template-columns:1fr}}@media(max-width:780px){.workspace{display:block}.ws-sidebar{position:static;height:auto;padding:16px 18px}.ws-logo{display:flex;justify-content:center}.ws-nav{display:flex;overflow-x:auto;margin-top:16px}.ws-nav a{white-space:nowrap}.ws-bottom{position:static;margin-top:8px}.ws-main{padding:28px 18px 60px}.ws-head{display:block}.badge{display:inline-block;margin-top:14px}.two{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="workspace">
<aside class="ws-sidebar">
<div class="ws-logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a></div>
<nav class="ws-nav">
<a href="dashboard.php"><i class="fas fa-grid-2"></i> Overview</a>
<a class="active" href="fraud.php"><i class="fas fa-wave-square"></i> Fraud Intelligence</a>
<a href="verify.php"><i class="fas fa-fingerprint"></i> Identity</a>
<a href="scan.php"><i class="fas fa-shield-virus"></i> Cyber Protection</a>
<a href="vault.php"><i class="fas fa-vault"></i> Secure Vault</a>
<a href="vpn_dashboard.php"><i class="fas fa-lock"></i> Cybte VPN</a>
</nav>
<div class="ws-bottom"><nav class="ws-nav"><a href="index.php"><i class="fas fa-globe"></i> Public site</a><a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</a></nav></div>
</aside>
<main class="ws-main">
<div class="ws-head"><div><h1>Fraud intelligence</h1><p>Assess transaction risk signals inside your Cybte AI workspace. The current MVP uses transparent deterministic rules; production AI models and external transaction feeds are not connected yet.</p></div><div class="badge"><i class="fas fa-wave-square"></i> Risk assessment workspace</div></div>
<?php if ($success): ?><div class="notice success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="fraud-grid">
<section class="panel"><h2>Analyze a transaction</h2><p>Use test data only. A score is calculated from amount, location familiarity and device trust.</p>
<form class="fraud-form" method="post" action="fraud.php" autocomplete="off"><?php echo csrf_input(); ?>
<label>Transaction reference</label><input type="text" name="transaction_id" maxlength="100" placeholder="e.g. TEST-TXN-2026-001" required>
<label>Transaction amount</label><input type="number" name="amount" min="0" step="0.01" placeholder="0.00" required>
<div class="two"><div><label>Location</label><select name="location"><option value="known">Known location</option><option value="unknown">Unknown location</option></select></div><div><label>Device</label><select name="device"><option value="trusted">Trusted device</option><option value="new">New device</option></select></div></div>
<button class="submit-btn" type="submit"><i class="fas fa-shield-halved"></i> Analyze transaction</button></form>
<?php if (is_array($result)): $rc = fraud_status_class((string)$result['status']); ?><div class="result"><div class="score"><?php echo (int)$result['risk_score']; ?>/100</div><span class="risk-pill <?php echo $rc; ?>"><?php echo htmlspecialchars((string)$result['status']); ?></span><?php if (!empty($result['signals'])): ?><p style="color:#8194aa;font-size:.76rem;line-height:1.6">Signals: <?php echo htmlspecialchars(implode(', ', (array)$result['signals'])); ?></p><?php endif; ?></div><?php endif; ?>
<div class="note"><i class="fas fa-circle-info"></i> This MVP is a rules engine, not a production fraud-detection model. Before enterprise use, connect transaction feeds, feature pipelines, model monitoring, explainability, case management and human review.</div>
</section>
<section class="panel"><h2>Recent assessments</h2><p>Only assessments created by your authenticated Cybte AI account are shown.</p>
<?php if ($assessments): ?><div class="history"><?php foreach ($assessments as $assessment): $rc=fraud_status_class((string)$assessment['status']); ?><div class="item"><div><strong><?php echo htmlspecialchars((string)$assessment['transaction_id']); ?></strong><small>Score <?php echo (int)$assessment['risk_score']; ?> · <?php echo htmlspecialchars(date('M j, Y H:i', strtotime((string)$assessment['created_at']))); ?></small></div><span class="risk-pill <?php echo $rc; ?>"><?php echo htmlspecialchars((string)$assessment['status']); ?></span></div><?php endforeach; ?></div><?php else: ?><div style="text-align:center;padding:48px 15px;color:#667d94"><i class="fas fa-chart-line" style="font-size:1.8rem;color:#3be7ff"></i><p>No fraud assessments yet.</p></div><?php endif; ?>
</section>
</div>
</main></div></body></html>
