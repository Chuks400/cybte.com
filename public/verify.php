<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/models/Identity.php';

security_start_session();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$identity = new Identity();
$error = security_flash('identity_error') ?? '';
$success = security_flash('identity_success') ?? '';

$documentTypes = [
    'passport' => 'Passport',
    'national_id' => 'National ID',
    'drivers_license' => 'Driver License',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        security_flash('identity_error', 'Your session expired. Please try again.');
    } elseif (!security_rate_limit('identity_submit', 5, 3600)) {
        security_flash('identity_error', 'Too many identity submissions. Please wait before trying again.');
    } else {
        $documentType = strtolower(trim((string)($_POST['document_type'] ?? '')));
        $documentNumber = trim((string)($_POST['document_number'] ?? ''));

        if (!isset($documentTypes[$documentType])) {
            security_flash('identity_error', 'Choose a supported document type.');
        } elseif (mb_strlen($documentNumber) < 4 || mb_strlen($documentNumber) > 100) {
            security_flash('identity_error', 'Enter a valid document number.');
        } else {
            try {
                if ($identity->create($userId, $documentType, $documentNumber)) {
                    security_clear_rate_limit('identity_submit');
                    security_flash('identity_success', 'Identity case submitted successfully.');
                } else {
                    security_flash('identity_error', 'We could not submit the identity case right now.');
                }
            } catch (Throwable $e) {
                error_log('Identity submission error: ' . $e->getMessage());
                security_flash('identity_error', 'Identity verification is not available right now.');
            }
        }
    }

    header('Location: verify.php');
    exit();
}

try {
    $cases = $identity->listByUser($userId, 10);
} catch (Throwable $e) {
    error_log('Identity history error: ' . $e->getMessage());
    $cases = [];
    if ($error === '') {
        $error = 'Identity case history could not be loaded.';
    }
}

function identity_type_label(string $type): string
{
    return match ($type) {
        'passport' => 'Passport',
        'national_id' => 'National ID',
        'drivers_license' => 'Driver License',
        default => 'Identity document',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Identity Verification — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{margin:0;background:#040913;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.workspace{min-height:100vh;display:grid;grid-template-columns:245px 1fr}.ws-sidebar{position:sticky;top:0;height:100vh;padding:28px 20px;border-right:1px solid rgba(255,255,255,.07);background:#06101c}.ws-logo img{width:96px}.ws-nav{display:grid;gap:7px;margin-top:40px}.ws-nav a{display:flex;gap:11px;align-items:center;padding:11px 12px;border-radius:8px;color:#8196ac;text-decoration:none;font-size:.86rem}.ws-nav a.active,.ws-nav a:hover{background:rgba(59,231,255,.075);color:#e9faff}.ws-nav i{width:18px;color:#3be7ff}.ws-bottom{position:absolute;left:20px;right:20px;bottom:25px}.ws-main{padding:38px 44px 70px;max-width:1400px;width:100%}.ws-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start}.ws-head h1{font-size:2rem;letter-spacing:-.035em;margin:0}.ws-head p{color:#7f93aa;margin:8px 0 0;max-width:720px;line-height:1.7}.badge{padding:9px 12px;border:1px solid rgba(59,231,255,.14);background:rgba(59,231,255,.05);border-radius:8px;color:#8edff0;font-size:.76rem}.notice{margin:24px 0;padding:14px 16px;border-radius:10px;font-size:.82rem}.notice.success{border:1px solid rgba(84,230,165,.18);background:rgba(84,230,165,.06);color:#8fe8be}.notice.error{border:1px solid rgba(255,91,91,.18);background:rgba(255,91,91,.06);color:#ffaaaa}.identity-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:18px;margin-top:28px}.panel{padding:24px;border-radius:14px;border:1px solid rgba(123,196,255,.12);background:#081523}.panel h2{font-size:1.05rem;margin:0 0 7px}.panel>p{color:#8194aa;font-size:.79rem;line-height:1.65;margin:0 0 20px}.identity-form label{display:block;color:#aebed0;font-size:.76rem;margin:14px 0 7px}.identity-form select,.identity-form input{width:100%;padding:14px 15px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#07111f;color:#fff;box-sizing:border-box}.identity-form select:focus,.identity-form input:focus{outline:none;border-color:#3be7ff}.submit-btn{margin-top:20px;width:100%;border:0;border-radius:9px;padding:14px;background:linear-gradient(135deg,#3be7ff,#67a7ff);color:#03111a;font-weight:800;cursor:pointer}.privacy-note{margin-top:14px;padding:12px;border:1px solid rgba(255,255,255,.06);border-radius:8px;background:rgba(255,255,255,.02);color:#657b92;font-size:.72rem;line-height:1.6}.case-list{display:grid;gap:10px;margin-top:18px}.case{display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center;padding:14px 15px;border:1px solid rgba(255,255,255,.06);border-radius:10px;background:#07111f}.case strong{display:block;font-size:.84rem}.case small{display:block;color:#687e95;margin-top:4px}.status{padding:6px 9px;border-radius:999px;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.status.pending{background:rgba(255,190,71,.08);color:#e1bd76;border:1px solid rgba(255,190,71,.16)}.status.approved,.status.verified{background:rgba(84,230,165,.08);color:#83e4b6;border:1px solid rgba(84,230,165,.16)}.status.rejected{background:rgba(255,91,91,.08);color:#ff9999;border:1px solid rgba(255,91,91,.16)}.empty{padding:36px 15px;text-align:center;color:#667d94}.empty i{font-size:1.7rem;color:#3be7ff;margin-bottom:10px}.roadmap{margin-top:18px;padding:18px;border:1px solid rgba(59,231,255,.1);border-radius:12px;background:rgba(59,231,255,.025)}.roadmap h3{margin:0 0 9px;font-size:.9rem}.roadmap p{margin:0;color:#70859b;font-size:.76rem;line-height:1.65}@media(max-width:900px){.identity-grid{grid-template-columns:1fr}}@media(max-width:780px){.workspace{display:block}.ws-sidebar{position:static;height:auto;padding:16px 18px}.ws-logo{display:flex;justify-content:center}.ws-nav{display:flex;overflow-x:auto;margin-top:16px}.ws-nav a{white-space:nowrap}.ws-bottom{position:static;margin-top:8px}.ws-main{padding:28px 18px 60px}.ws-head{display:block}.badge{display:inline-block;margin-top:14px}}
</style>
</head>
<body>
<div class="workspace">
<aside class="ws-sidebar">
<div class="ws-logo"><a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI"></a></div>
<nav class="ws-nav">
<a href="dashboard.php"><i class="fas fa-grid-2"></i> Overview</a>
<a href="fraud.php"><i class="fas fa-wave-square"></i> Fraud Intelligence</a>
<a class="active" href="verify.php"><i class="fas fa-fingerprint"></i> Identity</a>
<a href="scan.php"><i class="fas fa-shield-virus"></i> Cyber Protection</a>
<a href="vault.php"><i class="fas fa-vault"></i> Secure Vault</a>
<a href="vpn_dashboard.php"><i class="fas fa-lock"></i> Cybte VPN</a>
</nav>
<div class="ws-bottom"><nav class="ws-nav"><a href="index.php"><i class="fas fa-globe"></i> Public site</a><a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</a></nav></div>
</aside>
<main class="ws-main">
<div class="ws-head"><div><h1>Identity verification</h1><p>Create and track KYC identity cases from your Cybte AI workspace. This MVP records a verification request; automated provider checks, sanctions screening and liveness verification are not yet connected.</p></div><div class="badge"><i class="fas fa-id-card"></i> KYC / AML workspace</div></div>

<?php if ($success !== ''): ?><div class="notice success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="notice error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="identity-grid">
<section class="panel">
<h2>Start an identity case</h2>
<p>Submit a supported document reference for review. Cybte does not retain the raw document number in this MVP.</p>
<form class="identity-form" method="post" action="verify.php" autocomplete="off">
<?php echo csrf_input(); ?>
<label for="document_type">Document type</label>
<select id="document_type" name="document_type" required>
<?php foreach ($documentTypes as $value => $label): ?><option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?>
</select>
<label for="document_number">Document number</label>
<input id="document_number" type="text" name="document_number" maxlength="100" required autocomplete="off" placeholder="Enter document number">
<button class="submit-btn" type="submit"><i class="fas fa-shield-check"></i> Submit identity case</button>
</form>
<div class="privacy-note"><i class="fas fa-lock"></i> The submitted document number is converted to a keyed cryptographic fingerprint before database storage. Do not submit scans or regulated identity documents until a production KYC provider and document-security controls are integrated.</div>
</section>

<section class="panel">
<h2>Recent identity cases</h2>
<p>Only cases associated with your authenticated Cybte AI account are shown here.</p>
<?php if ($cases): ?><div class="case-list">
<?php foreach ($cases as $case): $status = strtolower((string)($case['status'] ?? 'pending')); ?>
<div class="case"><div><strong><?php echo htmlspecialchars(identity_type_label((string)$case['document_type'])); ?></strong><small>Case #<?php echo (int)$case['id']; ?> · <?php echo htmlspecialchars(date('M j, Y H:i', strtotime((string)$case['created_at']))); ?></small></div><span class="status <?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span></div>
<?php endforeach; ?>
</div><?php else: ?><div class="empty"><i class="fas fa-id-card-clip"></i><p>No identity cases submitted yet.</p></div><?php endif; ?>
<div class="roadmap"><h3><i class="fas fa-circle-info"></i> Enterprise roadmap</h3><p>Production KYC/AML should integrate a specialist identity provider for document authenticity, liveness, sanctions/PEP screening, audit evidence and jurisdiction-specific compliance workflows. This page does not claim those capabilities are active yet.</p></div>
</section>
</div>
</main>
</div>
</body>
</html>
