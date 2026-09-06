<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

security_start_session();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!security_rate_limit('login', 8, 900)) {
        $error = 'Too many sign-in attempts. Please wait before trying again.';
    } else {
        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $auth = new AuthController();
        if (!$auth->login($email, $password)) {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Sign In — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.auth-shell{min-height:100vh;display:grid;grid-template-columns:1fr 1fr;background:#040913;color:#fff}.auth-brand{padding:8vw;display:flex;flex-direction:column;justify-content:center;background:radial-gradient(circle at 25% 25%,rgba(59,231,255,.13),transparent 34%)}.auth-brand img{width:115px;margin-bottom:42px}.auth-brand h1{font-size:clamp(2.8rem,5vw,5rem);line-height:1;letter-spacing:-.055em;max-width:650px}.auth-brand p{max-width:560px;color:#91a3ba;line-height:1.8;margin-top:22px}.auth-panel{display:grid;place-items:center;padding:40px;background:rgba(8,20,35,.78);border-left:1px solid rgba(123,196,255,.12)}.auth-card{width:100%;max-width:440px}.auth-card h2{font-size:2rem;margin-bottom:8px}.auth-card>p{color:#91a3ba;margin-bottom:28px}.auth-card label{display:block;font-size:.78rem;color:#aebed0;margin:15px 0 7px}.auth-card input{width:100%;padding:15px 16px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#07111f;color:#fff}.auth-card input:focus{outline:none;border-color:#3be7ff}.auth-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:9px;background:linear-gradient(135deg,#3be7ff,#67a7ff);color:#03111a;font-weight:800;cursor:pointer}.auth-error{padding:12px 14px;border:1px solid rgba(255,99,99,.25);background:rgba(255,99,99,.07);border-radius:8px;color:#ff9b9b;margin-bottom:18px}.auth-links{margin-top:24px;color:#8194aa;font-size:.9rem}.auth-links a{color:#3be7ff;text-decoration:none}.back-home{position:absolute;top:24px;left:28px;color:#9dafc2;text-decoration:none;font-size:.85rem}@media(max-width:800px){.auth-shell{grid-template-columns:1fr}.auth-brand{display:none}.auth-panel{min-height:100vh;border-left:0;padding:28px}.back-home{z-index:2}}
</style>
</head>
<body>
<a class="back-home" href="index.php"><i class="fas fa-arrow-left"></i> Cybte AI</a>
<div class="auth-shell">
<section class="auth-brand">
<img src="assets/images/logo.png" alt="Cybte AI">
<h1>Secure access to your digital trust workspace.</h1>
<p>Manage Cybte AI services from one account, including Secure Vault and Cybte VPN as they are enabled for your organization.</p>
</section>
<section class="auth-panel">
<div class="auth-card">
<h2>Welcome back</h2>
<p>Sign in to your Cybte AI account.</p>
<?php if ($error !== ''): ?><div class="auth-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" autocomplete="on">
<?php echo csrf_input(); ?>
<label for="email">Email address</label>
<input id="email" type="email" name="email" autocomplete="email" required autofocus>
<label for="password">Password</label>
<input id="password" type="password" name="password" autocomplete="current-password" required>
<button class="auth-submit" type="submit">Sign in <i class="fas fa-arrow-right"></i></button>
</form>
<div class="auth-links">New to Cybte AI? <a href="signup.php">Create an account</a></div>
</div>
</section>
</div>
</body>
</html>
