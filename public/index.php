<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Cybte AI is a unified cybersecurity and digital trust platform for fraud intelligence, identity verification, vulnerability protection, secure data storage and private connectivity.">
<title>Cybte AI — Unified Cybersecurity & Digital Trust</title>
<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="enterprise-home">
<div class="starry-background"></div>
<div class="stars"></div>

<header class="enterprise-header">
  <div class="container">
    <div class="header-content">
      <a href="index.php" class="brand-lockup" aria-label="Cybte AI home">
        <img src="assets/images/logo.png" alt="Cybte AI" class="logo-img">
      </a>
      <nav class="enterprise-nav">
        <a href="#platform">Platform</a>
        <a href="#products">Products</a>
        <a href="#enterprise">Enterprise</a>
        <a href="#about">Company</a>
        <a href="#contact">Contact</a>
        <?php if ($isLoggedIn): ?>
          <a href="dashboard.php" class="sign-in-btn">Dashboard</a>
        <?php else: ?>
          <a href="login.php">Sign In</a>
          <a href="vpn_signup.php" class="sign-in-btn">Create Account</a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>

<main>
<section class="enterprise-hero" id="platform">
  <div class="container enterprise-hero-grid">
    <div class="enterprise-hero-copy">
      <div class="eyebrow"><span></span> AI-powered cybersecurity & digital trust</div>
      <h1>One security ecosystem for your <em>digital world.</em></h1>
      <p>Cybte AI brings fraud intelligence, identity verification, vulnerability protection, secure data storage and encrypted connectivity into one integrated platform for individuals and organizations.</p>
      <div class="hero-actions">
        <a href="#products" class="primary-action">Explore the platform <i class="fas fa-arrow-right"></i></a>
        <a href="#contact" class="secondary-action">Partner with Cybte</a>
      </div>
      <div class="trust-strip">
        <span><i class="fas fa-shield-halved"></i> Security-first architecture</span>
        <span><i class="fas fa-layer-group"></i> Unified dashboard</span>
        <span><i class="fas fa-code"></i> API-ready vision</span>
      </div>
    </div>
    <div class="command-visual" aria-label="Cybte AI security platform preview">
      <div class="command-top">
        <div><span class="status-dot"></span> CYBTE SECURITY CORE</div>
        <span>ONLINE</span>
      </div>
      <div class="command-score">
        <div class="score-ring"><strong>5</strong><span>security layers</span></div>
        <div>
          <small>Unified protection</small>
          <h3>Protect. Verify.<br>Detect. Store. Connect.</h3>
        </div>
      </div>
      <div class="signal-list">
        <div><i class="fas fa-wave-square"></i><span>Fraud intelligence</span><b>MONITOR</b></div>
        <div><i class="fas fa-id-card"></i><span>Identity trust</span><b>VERIFY</b></div>
        <div><i class="fas fa-bug-slash"></i><span>Cyber risk</span><b>ASSESS</b></div>
        <div><i class="fas fa-vault"></i><span>Secure Vault</span><b>PROTECT</b></div>
        <div><i class="fas fa-globe"></i><span>Private access</span><b>CONNECT</b></div>
      </div>
    </div>
  </div>
</section>

<section class="platform-intro">
  <div class="container">
    <div class="section-kicker">THE CYBTE ECOSYSTEM</div>
    <div class="section-heading-row">
      <h2>Five products. One security platform.</h2>
      <p>Designed to reduce fragmented security tooling and give customers a clearer view of identities, transactions, sensitive data, infrastructure and digital connections.</p>
    </div>
  </div>
</section>

<section class="product-suite" id="products">
  <div class="container product-grid">
    <article class="product-card featured-product">
      <div class="product-number">01</div>
      <div class="product-icon"><i class="fas fa-chart-line"></i></div>
      <h3>AI Fraud Detection</h3>
      <p>Analyze transactions and digital activity, surface unusual patterns and support risk-based fraud investigation through intelligent scoring and alerts.</p>
      <ul><li>Transaction intelligence</li><li>Anomaly detection</li><li>Risk scoring & alerts</li></ul>
      <a href="fraud.php">Explore fraud protection <i class="fas fa-arrow-up-right-from-square"></i></a>
    </article>

    <article class="product-card">
      <div class="product-number">02</div>
      <div class="product-icon"><i class="fas fa-fingerprint"></i></div>
      <h3>Identity Verification</h3>
      <p>A secure onboarding and verification layer designed around identity checks, document workflows, liveness signals and compliance screening.</p>
      <ul><li>Customer onboarding</li><li>Document verification</li><li>KYC/AML workflow vision</li></ul>
      <a href="verify.php">Explore identity trust <i class="fas fa-arrow-right"></i></a>
    </article>

    <article class="product-card">
      <div class="product-number">03</div>
      <div class="product-icon"><i class="fas fa-shield-virus"></i></div>
      <h3>Cybersecurity Protection</h3>
      <p>Security assessment, vulnerability visibility, alerts and risk reporting designed to help organizations identify weaknesses before they become incidents.</p>
      <ul><li>Vulnerability assessment</li><li>Threat monitoring</li><li>Risk reporting</li></ul>
      <a href="scan.php">Explore cyber protection <i class="fas fa-arrow-right"></i></a>
    </article>

    <article class="product-card vault-card">
      <div class="new-badge">NEW</div>
      <div class="product-number">04</div>
      <div class="product-icon"><i class="fas fa-vault"></i></div>
      <h3>Cybte Secure Vault</h3>
      <p>A protected environment for confidential documents and sensitive business data, designed around encryption, access controls, secure retrieval and auditable activity.</p>
      <ul><li>Encrypted storage design</li><li>Role-based access</li><li>Audit logging</li></ul>
      <a href="vault.php">Discover Secure Vault <i class="fas fa-arrow-right"></i></a>
    </article>

    <article class="product-card">
      <div class="product-number">05</div>
      <div class="product-icon"><i class="fas fa-lock"></i></div>
      <h3>Cybte VPN</h3>
      <p>Secure connectivity for encrypted internet traffic and additional privacy when users work, browse or connect through untrusted networks.</p>
      <ul><li>Encrypted tunnels</li><li>Multi-device access</li><li>Private connectivity</li></ul>
      <a href="vpn.php">Explore Cybte VPN <i class="fas fa-arrow-right"></i></a>
    </article>
  </div>
</section>

<section class="enterprise-section" id="enterprise">
  <div class="container enterprise-panel">
    <div>
      <div class="section-kicker">BUILT FOR INTEGRATION</div>
      <h2>Security capabilities that can grow with your organization.</h2>
      <p>The Cybte AI roadmap includes secure APIs so banks, fintechs, SMEs, e-commerce platforms and digital businesses can integrate selected security services directly into their own products and workflows.</p>
      <div class="enterprise-points">
        <span><i class="fas fa-building"></i> Enterprise collaboration</span>
        <span><i class="fas fa-plug"></i> Secure API integrations</span>
        <span><i class="fas fa-users-gear"></i> Centralized administration</span>
        <span><i class="fas fa-file-lines"></i> Security reporting</span>
      </div>
    </div>
    <div class="api-window">
      <div class="api-bar"><span></span><span></span><span></span><b>api.cybte.com</b></div>
      <pre><code><span>POST</span> /v1/risk/analyze
{
  "signal": "transaction",
  "identity": "verified",
  "policy": "enterprise"
}

→ risk_score: 0.18
→ action: "allow"</code></pre>
      <small>Illustrative API experience — product roadmap</small>
    </div>
  </div>
</section>

<section class="company-section" id="about">
  <div class="container company-grid">
    <div>
      <div class="section-kicker">ABOUT CYBTE AI</div>
      <h2>Building digital trust from Africa for a connected world.</h2>
    </div>
    <div>
      <p>Cybte AI is being developed as a unified cybersecurity and digital trust platform focused on practical protection for identities, transactions, sensitive information, systems and digital connections.</p>
      <p>Our direction is simple: make advanced security capabilities easier for organizations to access, manage and integrate from one trusted ecosystem.</p>
    </div>
  </div>
</section>

<section class="contact enterprise-contact" id="contact">
  <div class="container">
    <div class="contact-shell">
      <div class="contact-pitch">
        <div class="section-kicker">PARTNERSHIPS & ENTERPRISE</div>
        <h2>Build a more trusted digital experience with Cybte AI.</h2>
        <p>For pilots, strategic partnerships, security integrations and enterprise collaboration, speak with the Cybte AI team.</p>
        <div class="contact-meta">
          <span><i class="fas fa-envelope"></i> security@cybte.com</span>
          <span><i class="fas fa-globe-africa"></i> Nigeria · Global collaboration</span>
        </div>
      </div>
      <div class="contact-form enterprise-form">
        <form method="post" action="#">
          <div class="form-row">
            <div class="form-group"><input type="text" name="name" placeholder="Full name" required></div>
            <div class="form-group"><input type="email" name="email" placeholder="Business email" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><input type="text" name="company" placeholder="Company / organization"></div>
            <div class="form-group"><input type="text" name="role" placeholder="Your role"></div>
          </div>
          <div class="form-group"><textarea name="message" rows="5" placeholder="Tell us about your collaboration or security needs" required></textarea></div>
          <button type="submit" class="submit-btn">Request a conversation <i class="fas fa-arrow-right"></i></button>
        </form>
        <small class="form-note">This form interface is being prepared for production messaging integration.</small>
      </div>
    </div>
  </div>
</section>
</main>

<footer class="enterprise-footer">
  <div class="container">
    <div class="footer-top">
      <div><img src="assets/images/logo.png" alt="Cybte AI"><p>Unified cybersecurity & digital trust.</p></div>
      <div><h4>Platform</h4><a href="#products">Products</a><a href="vault.php">Secure Vault</a><a href="vpn.php">Cybte VPN</a></div>
      <div><h4>Company</h4><a href="#about">About</a><a href="#contact">Partnerships</a><a href="login.php">Customer login</a></div>
      <div><h4>Security</h4><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Responsible disclosure</a></div>
    </div>
    <div class="footer-bottom"><p>&copy; <?php echo date('Y'); ?> Cybte AI. All rights reserved.</p><p>Protect. Verify. Detect. Store. Connect Securely.</p></div>
  </div>
</footer>
</body>
</html>