<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/controllers/ScanController.php';
require_once __DIR__ . '/../src/models/ScanResult.php';

security_start_session();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$error = security_flash('scan_error') ?? '';
$success = security_flash('scan_success') ?? '';
$result = $_SESSION['scan_result'] ?? null;
unset($_SESSION['scan_result']);
$history = [];
$serviceAvailable = true;

try {
    $scanModel = new ScanResult();
    $scanner = new ScanController($scanModel);
} catch (Throwable $e) {
    error_log('Cyber Protection workspace init error: ' . $e->getMessage());
    $serviceAvailable = false;
    $error = 'Cyber Protection is temporarily unavailable. Check the database service and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$serviceAvailable) {
        security_flash('scan_error', 'Cyber Protection is temporarily unavailable.');
    } elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {
        security_flash('scan_error', 'Your session expired. Please try again.');
    } elseif (!security_rate_limit('cyber_posture_assessment', 10, 900)) {
        security_flash('scan_error', 'Too many assessments. Please wait before trying again.');
    } else {
        $targetUrl = trim((string)($_POST['target_url'] ?? ''));
        try {
            $analysis = $scanner->assessTarget($userId, $targetUrl);
            $_SESSION['scan_result'] = $analysis;
            security_clear_rate_limit('cyber_posture_assessment');
            security_flash('scan_success', 'Security posture assessment completed.');
        } catch (InvalidArgumentException $e) {
            security_flash('scan_error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Cyber Protection assessment error: ' . $e->getMessage());
            security_flash('scan_error', 'The assessment could not be completed right now.');
        }
    }
    header('Location: scan.php');
    exit();
}

if ($serviceAvailable) {
    try {
        $history = $scanModel->listByUser($userId, 10);
    } catch (Throwable $e) {
        error_log('Cyber Protection history error: ' . $e->getMessage());
        $error = $error ?: 'Recent security assessments could not be loaded.';
    }
}

function scan_severity_class(string $severity): string
{
    return strtolower($severity) === 'medium' ? 'medium' : 'low';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Cyber Protection — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{margin:0;background:#040913;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.workspace{min-height:100vh;display:grid;grid-template-columns:245px 1fr}.ws-sidebar{position:sticky;top:0;height:100vh;padding:28px 20px;border-right:1px solid rgba(255,255,255,.07);background:#06101c}.ws-logo img{width:96px}.ws-nav{display:grid;gap:7px;margin-top:40px}.ws-nav a{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:8px;color:#8196ac;text-decoration:none;font-size:.86rem}.ws-nav a.active,.ws-nav a:hover{background:rgba(59,231,255,.075);color:#e9faff}.ws-nav i{width:18px;color:#3be7ff}.ws-bottom{position:absolute;left:20px;right:20px;bottom:25px}.ws-main{padding:38px 44px 70px;max-width:1400px;width:100%}.ws-head{display:flex;justify-content:space-between;gap:24px}.ws-head h1{font-size:2rem;margin:0}.ws-head p{color:#7f93aa;max-width:760px;line-height:1.7}.badge{padding:9px 12px;border:1px solid rgba(59,231,255,.14);background:rgba(59,231,255,.05);border-radius:8px;color:#8edff0;font-size:.76rem;height:max-content}.notice{margin:24px 0;padding:14px 16px;border-radius:10px;font-size:.82rem}.success{border:1px solid rgba(84,230,165,.18);background:rgba(84,230,165,.06);color:#8fe8be}.error{border:1px solid rgba(255,91,91,.18);background:rgba(255,91,91,.06);color:#ffaaaa}.grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:18px;margin-top:28px}.panel{padding:24px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.panel h2{font-size:1.05rem;margin:0 0 7px}.panel>p{color:#8194aa;font-size:.79rem;line-height:1.65}.form label{display:block;color:#aebed0;font-size:.76rem;margin:18px 0 7px}.form input{width:100%;padding:14px 15px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#07111f;color:#fff;box-sizing:border-box}.submit{margin-top:18px;width:100%;border:0;border-radius:9px;padding:14px;background:linear-gradient(135deg,#3be7ff,#67a7ff);font-weight:800;cursor:pointer}.result{margin-top:18px;padding:18px;border:1px solid rgba(59,231,255,.12);border-radius:12px;background:#07111f}.pill{display:inline-block;padding:6px 9px;border-radius:999px;font-size:.68rem;font-weight:800;text-transform:uppercase}.pill.low{color:#7de2b1;background:rgba(84,230,165,.08)}.pill.medium{color:#e1bd76;background:rgba(255,190,71,.08)}.history{display:grid;gap:10px}.item{display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;padding:14px 15px;border:1px solid rgba(255,255,255,.06);border-radius:10px;background:#07111f}.item small{display:block;color:#687e95;margin-top:4px}.note{margin-top:18px;padding:16px;border:1px solid rgba(59,231,255,.1);border-radius:12px;color:#70859b;font-size:.75rem;line-height:1.65;background:rgba(59,231,255,.025)}@media(max-width:900px){.grid{grid-template-columns:1fr}}@media(max-width:780px){.workspace{display:block}.ws-sidebar{position:static;height:auto}.ws-nav{display:flex;overflow-x:auto}.ws-bottom{position:static}.ws-main{padding:28px 18px}.ws-head{display:block}}
</style>
</head>
<body>
<div class="workspace">
<aside class="ws-sidebar">
<div class="ws-logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a></div>
<nav class="ws-nav">
<a href="dashboard.php"><i class="fas fa-border-all"></i> Overview</a>
<a href="fraud.php"><i class="fas fa-wave-square"></i> Fraud Intelligence</a>
<a href="verify.php"><i class="fas fa-fingerprint"></i> Identity</a>
<a class="active" href="scan.php"><i class="fas fa-shield-halved"></i> Cyber Protection</a>
<a href="vault.php"><i class="fas fa-vault"></i> Secure Vault</a>
<a href="vpn_dashboard.php"><i class="fas fa-lock"></i> Cybte VPN</a>
</nav>
<div class="ws-bottom"><nav class="ws-nav"><a href="index.php"><i class="fas fa-globe"></i> Public site</a><a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</a></nav></div>
</aside>
<main class="ws-main">
<div class="ws-head"><div><h1>Cyber protection</h1><p>Review basic website security posture without launching intrusive scans. This MVP performs local URL analysis only; it does not connect to, probe or exploit the submitted target.</p></div><div class="badge"><i class="fas fa-shield-halved"></i> Passive posture workspace</div></div>
<?php if ($success): ?><div class="notice success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="grid">
<section class="panel"><h2>Assess a website reference</h2><p>Use a public website you own or are authorized to assess. No network request is made by this MVP.</p>
<form class="form" method="post" action="scan.php" autocomplete="off"><?php echo csrf_input(); ?><label for="target_url">Website URL</label><input id="target_url" type="url" name="target_url" maxlength="2048" placeholder="https://example.com" required><button class="submit" type="submit"><i class="fas fa-shield"></i> Assess security posture</button></form>
<?php if (is_array($result)): $sc=scan_severity_class((string)$result['severity']); ?><div class="result"><strong><?php echo htmlspecialchars((string)$result['target_url']); ?></strong><div style="margin-top:10px"><span class="pill <?php echo $sc; ?>"><?php echo htmlspecialchars((string)$result['severity']); ?> posture</span></div><ul style="color:#8194aa;line-height:1.8"><?php foreach ((array)$result['findings'] as $finding): ?><li><?php echo htmlspecialchars((string)$finding); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="note"><i class="fas fa-circle-info"></i> Production vulnerability management should use an authorized scanner with asset ownership verification, safe scan policies, rate controls, evidence capture and human review. Cybte does not claim those active-scanning capabilities are enabled here.</div>
</section>
<section class="panel"><h2>Recent posture assessments</h2><p>Only assessments associated with your authenticated Cybte AI account are shown.</p>
<?php if ($history): ?><div class="history"><?php foreach ($history as $item): $sc=scan_severity_class((string)$item['severity']); ?><div class="item"><div><strong><?php echo htmlspecialchars((string)$item['target_url']); ?></strong><small><?php echo htmlspecialchars(date('M j, Y H:i', strtotime((string)$item['created_at']))); ?></small></div><span class="pill <?php echo $sc; ?>"><?php echo htmlspecialchars((string)$item['severity']); ?></span></div><?php endforeach; ?></div><?php else: ?><div style="text-align:center;padding:48px 15px;color:#667d94"><i class="fas fa-shield" style="font-size:1.8rem;color:#3be7ff"></i><p>No cyber posture assessments yet.</p></div><?php endif; ?>
</section>
</div></main></div></body></html>
