<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
security_start_session();
$isLoggedIn = isset($_SESSION['user_id']);

$vaultError = '';
$vaultSuccess = '';
$documents = [];

if ($isLoggedIn) {
    require_once __DIR__ . '/../src/config/database.php';
    require_once __DIR__ . '/../src/Services/VaultService.php';

    try {
        $db = new Database();
        $vault = new Cybte\Services\VaultService($db->connect());

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                $vaultError = 'Your session expired. Please try again.';
            } elseif (!security_rate_limit('vault_action', 12, 900)) {
                $vaultError = 'Too many vault actions. Please wait before trying again.';
            } else {
                $action = (string)($_POST['action'] ?? '');
                if ($action === 'upload') {
                    $result = $vault->upload((int)$_SESSION['user_id'], $_FILES['document'] ?? []);
                    $vaultSuccess = 'Encrypted upload complete: ' . $result['name'];
                } elseif ($action === 'delete') {
                    $documentId = filter_var($_POST['document_id'] ?? null, FILTER_VALIDATE_INT);
                    if (!$documentId) {
                        throw new RuntimeException('Invalid document request.');
                    }
                    $vault->delete((int)$_SESSION['user_id'], (int)$documentId);
                    $vaultSuccess = 'Document deleted from Secure Vault.';
                }
            }
        }

        $documents = $vault->listDocuments((int)$_SESSION['user_id']);
    } catch (Throwable $e) {
        error_log('Vault workspace error: ' . $e->getMessage());
        $vaultError = 'Secure Vault is not available right now. Confirm the database migration and APP_KEY are configured.';
    }
}

function vault_size(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Cybte Secure Vault — encrypted document and sensitive-data protection within the Cybte AI ecosystem.">
<title>Cybte Secure Vault — Protected Data Storage</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.vault-workspace{padding:40px 0 100px}.vault-workspace-shell{border:1px solid rgba(123,196,255,.14);border-radius:20px;background:rgba(7,18,31,.88);padding:32px}.vault-workspace-head{display:flex;justify-content:space-between;gap:24px;align-items:end;margin-bottom:28px}.vault-workspace-head p{color:#91a3ba;max-width:650px}.vault-upload{display:grid;grid-template-columns:1fr auto;gap:12px;padding:18px;border:1px dashed rgba(59,231,255,.28);border-radius:12px;background:rgba(59,231,255,.035)}.vault-upload input[type=file]{width:100%;padding:12px;color:#b9c7d6;background:#06101d;border:1px solid rgba(255,255,255,.08);border-radius:8px}.vault-upload button,.vault-action{border:0;border-radius:8px;background:linear-gradient(135deg,#3be7ff,#67a7ff);color:#04121a;font-weight:800;padding:12px 17px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px}.vault-table{width:100%;border-collapse:collapse;margin-top:28px}.vault-table th,.vault-table td{text-align:left;padding:14px 10px;border-bottom:1px solid rgba(255,255,255,.06);font-size:.82rem}.vault-table th{color:#6f8399;font-size:.68rem;text-transform:uppercase;letter-spacing:.08em}.vault-table td{color:#c1cedb}.vault-name{font-weight:700;color:#fff}.vault-hash{font:500 .66rem ui-monospace;color:#657b92}.vault-actions{display:flex;gap:7px;flex-wrap:wrap}.vault-actions form{margin:0}.vault-delete{background:rgba(255,91,91,.08)!important;color:#ff9999!important;border:1px solid rgba(255,91,91,.2)!important}.vault-state{padding:13px 15px;border-radius:9px;margin-bottom:18px;font-size:.84rem}.vault-state.success{color:#72e9b8;background:rgba(84,230,165,.07);border:1px solid rgba(84,230,165,.18)}.vault-state.error{color:#ffaaaa;background:rgba(255,91,91,.07);border:1px solid rgba(255,91,91,.18)}.vault-empty{text-align:center;padding:48px 20px;color:#71869d}.vault-empty i{font-size:2rem;color:#3be7ff;margin-bottom:12px}.vault-safety{margin-top:18px;color:#687d94;font-size:.72rem;line-height:1.6}@media(max-width:760px){.vault-workspace-head{display:block}.vault-upload{grid-template-columns:1fr}.vault-table thead{display:none}.vault-table,.vault-table tbody,.vault-table tr,.vault-table td{display:block}.vault-table tr{padding:12px 0;border-bottom:1px solid rgba(255,255,255,.07)}.vault-table td{border:0;padding:5px 0}.vault-table td:before{content:attr(data-label);display:block;color:#667d94;font-size:.62rem;text-transform:uppercase}}
</style>
</head>
<body class="enterprise-home vault-page">
<div class="starry-background"></div><div class="stars"></div>
<header class="enterprise-header"><div class="container"><div class="header-content">
<a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI" class="logo-img"></a>
<nav class="enterprise-nav"><a href="index.php">Home</a><a href="#security">Security</a><a href="#architecture">Architecture</a><?php if ($isLoggedIn): ?><a href="dashboard.php">Dashboard</a><a href="logout.php" class="sign-in-btn">Sign Out</a><?php else: ?><a href="login.php">Sign In</a><a href="signup.php" class="sign-in-btn">Create Account</a><?php endif; ?></nav>
</div></div></header>

<main>
<section class="vault-hero">
<div class="container vault-hero-grid">
<div>
<div class="eyebrow"><span></span> CYBTE SECURE VAULT</div>
<h1>Your sensitive data deserves a <em>security perimeter of its own.</em></h1>
<p>Cybte Secure Vault encrypts approved document types before storage and restricts retrieval to the authenticated account that uploaded them, with integrity checking and auditable access events.</p>
<div class="hero-actions"><?php if ($isLoggedIn): ?><a href="#workspace" class="primary-action">Open your vault <i class="fas fa-arrow-right"></i></a><?php else: ?><a href="login.php" class="primary-action">Sign in to Secure Vault <i class="fas fa-arrow-right"></i></a><a href="signup.php" class="secondary-action">Create account</a><?php endif; ?></div>
</div>
<div class="vault-visual">
<div class="vault-orbit orbit-one"></div><div class="vault-orbit orbit-two"></div>
<div class="vault-core"><i class="fas fa-vault"></i><strong>SECURE<br>VAULT</strong><span>AES-256-GCM</span></div>
<div class="vault-chip chip-a"><i class="fas fa-key"></i> Account-scoped access</div>
<div class="vault-chip chip-b"><i class="fas fa-file-shield"></i> Encrypted files</div>
<div class="vault-chip chip-c"><i class="fas fa-clock-rotate-left"></i> Audit events</div>
</div>
</div>
</section>

<?php if ($isLoggedIn): ?>
<section class="vault-workspace" id="workspace"><div class="container"><div class="vault-workspace-shell">
<div class="vault-workspace-head"><div><div class="section-kicker">YOUR SECURE WORKSPACE</div><h2>Encrypted documents</h2></div><p>Files are encrypted server-side before storage. The encrypted file directory is outside the public web root by default.</p></div>
<?php if ($vaultSuccess): ?><div class="vault-state success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($vaultSuccess); ?></div><?php endif; ?>
<?php if ($vaultError): ?><div class="vault-state error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($vaultError); ?></div><?php endif; ?>
<form class="vault-upload" method="post" enctype="multipart/form-data">
<?php echo csrf_input(); ?><input type="hidden" name="action" value="upload">
<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.txt,.csv,.doc,.docx,.xls,.xlsx" required>
<button type="submit"><i class="fas fa-lock"></i> Encrypt & upload</button>
</form>
<p class="vault-safety">Maximum file size: 10 MB. Supported formats: PDF, JPG, PNG, TXT, CSV, DOC/DOCX and XLS/XLSX. Do not use this MVP for regulated or mission-critical records until deployment security, backups, key management and recovery procedures have been independently reviewed.</p>
<?php if ($documents): ?>
<div style="overflow-x:auto"><table class="vault-table"><thead><tr><th>Document</th><th>Type</th><th>Size</th><th>Integrity</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($documents as $document): ?><tr>
<td data-label="Document"><span class="vault-name"><i class="fas fa-file-shield"></i> <?php echo htmlspecialchars($document['original_name']); ?></span></td>
<td data-label="Type"><?php echo htmlspecialchars($document['mime_type']); ?></td>
<td data-label="Size"><?php echo htmlspecialchars(vault_size((int)$document['size_bytes'])); ?></td>
<td data-label="Integrity"><span class="vault-hash"><?php echo htmlspecialchars(substr($document['sha256'], 0, 12)); ?>…</span></td>
<td data-label="Uploaded"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($document['created_at']))); ?></td>
<td data-label="Actions"><div class="vault-actions"><a class="vault-action" href="vault_download.php?id=<?php echo (int)$document['id']; ?>"><i class="fas fa-download"></i> Download</a><form method="post" onsubmit="return confirm('Delete this encrypted document permanently?');"><?php echo csrf_input(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="document_id" value="<?php echo (int)$document['id']; ?>"><button class="vault-action vault-delete" type="submit"><i class="fas fa-trash"></i></button></form></div></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php else: ?><div class="vault-empty"><i class="fas fa-folder-open"></i><p>No encrypted documents in your vault yet.</p></div><?php endif; ?>
</div></div></section>
<?php endif; ?>

<section class="vault-security" id="security"><div class="container">
<div class="section-kicker">CONFIDENTIALITY CONTROLS</div>
<div class="section-heading-row"><h2>Security controls around every document.</h2><p>The current MVP implements authenticated ownership checks, AES-256-GCM encryption, content-type restrictions, integrity verification and audit records.</p></div>
<div class="vault-control-grid">
<article><i class="fas fa-lock"></i><h3>Authenticated encryption</h3><p>AES-256-GCM protects file confidentiality and authenticity using a server-side application key.</p></article>
<article><i class="fas fa-user-lock"></i><h3>Account isolation</h3><p>Download and delete operations require an authenticated session and match documents to their owner.</p></article>
<article><i class="fas fa-file-circle-check"></i><h3>Integrity verification</h3><p>A SHA-256 digest is checked after decryption before a document is returned to the user.</p></article>
<article><i class="fas fa-clipboard-list"></i><h3>Audit visibility</h3><p>Upload, download and deletion events are recorded in a dedicated audit table.</p></article>
<article><i class="fas fa-filter-circle-xmark"></i><h3>Restricted uploads</h3><p>File size and server-detected MIME type are validated before content is accepted.</p></article>
<article><i class="fas fa-server"></i><h3>Non-public storage</h3><p>Encrypted payloads default to a storage directory outside the public document root.</p></article>
</div>
</div></section>

<section class="vault-architecture" id="architecture"><div class="container">
<div class="section-kicker">SECURE VAULT FLOW</div><h2>Controlled from upload to retrieval.</h2>
<div class="architecture-flow">
<div><i class="fas fa-upload"></i><b>Validate</b><small>Size & MIME checks</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-lock"></i><b>Encrypt</b><small>AES-256-GCM</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-user-check"></i><b>Authorize</b><small>Owner check</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-eye"></i><b>Audit</b><small>Recorded activity</small></div>
</div>
<p class="roadmap-note"><i class="fas fa-circle-info"></i> This is a functional MVP, not a certification claim. Before storing regulated or highly sensitive customer information, deploy centralized key management, backup/recovery controls, malware scanning, MFA and independent penetration testing.</p>
</div></section>
</main>

<footer class="enterprise-footer"><div class="container"><div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Cybte AI.</p><p>Protect. Verify. Detect. Store. Connect Securely.</p></div></div></footer>
</body></html>
