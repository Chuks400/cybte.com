<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Cybte Secure Vault — secure document and sensitive-data protection within the Cybte AI ecosystem.">
<title>Cybte Secure Vault — Protected Data Storage</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="enterprise-home vault-page">
<div class="starry-background"></div><div class="stars"></div>
<header class="enterprise-header"><div class="container"><div class="header-content">
<a href="index.php"><img src="assets/images/logo.png" alt="Cybte AI" class="logo-img"></a>
<nav class="enterprise-nav"><a href="index.php">Home</a><a href="#security">Security</a><a href="#use-cases">Use Cases</a><a href="#architecture">Architecture</a><a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'login.php'; ?>" class="sign-in-btn"><?php echo $isLoggedIn ? 'Dashboard' : 'Sign In'; ?></a></nav>
</div></div></header>

<main>
<section class="vault-hero">
<div class="container vault-hero-grid">
<div>
<div class="eyebrow"><span></span> CYBTE SECURE VAULT</div>
<h1>Your sensitive data deserves a <em>security perimeter of its own.</em></h1>
<p>Cybte Secure Vault is being designed as a protected environment for confidential documents and sensitive organizational data, with encryption, strong authentication, controlled access and auditable retrieval at its core.</p>
<div class="hero-actions"><a href="#security" class="primary-action">Explore the security model <i class="fas fa-arrow-right"></i></a><a href="index.php#contact" class="secondary-action">Discuss enterprise access</a></div>
</div>
<div class="vault-visual">
<div class="vault-orbit orbit-one"></div><div class="vault-orbit orbit-two"></div>
<div class="vault-core"><i class="fas fa-vault"></i><strong>SECURE<br>VAULT</strong><span>Encrypted workspace</span></div>
<div class="vault-chip chip-a"><i class="fas fa-key"></i> Access control</div>
<div class="vault-chip chip-b"><i class="fas fa-file-shield"></i> Protected files</div>
<div class="vault-chip chip-c"><i class="fas fa-clock-rotate-left"></i> Audit trail</div>
</div>
</div>
</section>

<section class="vault-security" id="security"><div class="container">
<div class="section-kicker">DESIGNED FOR CONFIDENTIALITY</div>
<div class="section-heading-row"><h2>Security controls around every document.</h2><p>The product roadmap is centered on reducing unauthorized access while making approved retrieval clear, controlled and traceable.</p></div>
<div class="vault-control-grid">
<article><i class="fas fa-lock"></i><h3>Encryption-first design</h3><p>Protect files in transit and at rest using modern encryption practices and secure key-management principles.</p></article>
<article><i class="fas fa-user-lock"></i><h3>Strong authentication</h3><p>Layer account authentication with access policy so sensitive content is available only to authorized users.</p></article>
<article><i class="fas fa-users-gear"></i><h3>Role-based access</h3><p>Organize permissions by user, team and responsibility instead of relying on shared credentials or open folders.</p></article>
<article><i class="fas fa-clipboard-list"></i><h3>Audit visibility</h3><p>Record critical upload, access, retrieval and administrative events to support accountability and review.</p></article>
<article><i class="fas fa-cloud-arrow-up"></i><h3>Secure retrieval</h3><p>Provide controlled document access without turning security into friction for legitimate business workflows.</p></article>
<article><i class="fas fa-building-shield"></i><h3>Organization workspaces</h3><p>Support protected data areas for teams that need clearer separation between users, projects and sensitive records.</p></article>
</div>
</div></section>

<section class="vault-use-cases" id="use-cases"><div class="container enterprise-panel">
<div><div class="section-kicker">BUSINESS USE CASES</div><h2>From sensitive records to critical business documents.</h2><p>Secure Vault is intended for organizations and professionals that need a stronger protection layer around information that should not live in ordinary shared storage.</p></div>
<div class="use-case-list"><span>Corporate documents</span><span>Customer records</span><span>Legal & compliance files</span><span>Security reports</span><span>Identity documents</span><span>Confidential project data</span></div>
</div></section>

<section class="vault-architecture" id="architecture"><div class="container">
<div class="section-kicker">PLATFORM ROADMAP</div><h2>Part of the wider Cybte AI trust layer.</h2>
<div class="architecture-flow">
<div><i class="fas fa-upload"></i><b>Upload</b><small>Controlled intake</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-lock"></i><b>Protect</b><small>Encryption layer</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-user-check"></i><b>Authorize</b><small>Access policy</small></div><i class="fas fa-arrow-right"></i>
<div><i class="fas fa-eye"></i><b>Audit</b><small>Recorded activity</small></div>
</div>
<p class="roadmap-note"><i class="fas fa-circle-info"></i> Cybte Secure Vault is presented as a product under development. Production security controls should be independently tested before sensitive customer data is accepted.</p>
</div></section>
</main>

<footer class="enterprise-footer"><div class="container"><div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Cybte AI.</p><p>Protect. Verify. Detect. Store. Connect Securely.</p></div></div></footer>
</body></html>