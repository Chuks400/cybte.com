<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';

security_start_session();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!security_rate_limit('signup', 5, 1800)) {
        $error = 'Too many registration attempts. Please wait and try again.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $error = 'Enter your full name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            $error = 'Use at least 10 characters with uppercase, lowercase and a number.';
        } else {
            try {
                $db = new Database();
                $conn = $db->connect();
                $check = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $check->execute([':email' => $email]);

                if ($check->fetch()) {
                    $error = 'An account already exists for this email.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($hash === false) {
                        throw new RuntimeException('Password hashing failed.');
                    }

                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, email_verified) VALUES (:name, :email, :password, 'user', 0)");
                    $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hash]);
                    $userId = (int)$conn->lastInsertId();

                    $verification = new EmailVerification($conn);
                    $token = $verification->generateToken();
                    $sent = false;
                    if ($verification->saveToken($userId, $email, $token)) {
                        $sent = $verification->sendVerificationEmail($email, $token, $name);
                    }

                    security_clear_rate_limit('signup');
                    header('Location: verification_pending.php?email=' . urlencode($email) . '&sent=' . ($sent ? '1' : '0'));
                    exit();
                }
            } catch (Throwable $e) {
                error_log('Signup error: ' . $e->getMessage());
                $error = 'We could not create your account right now. Please try again later.';
            }
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
<title>Create Account — Cybte AI</title>
<link rel="icon" href="assets/images/favicon.ico">
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.auth-shell{min-height:100vh;display:grid;grid-template-columns:.9fr 1.1fr;background:#040913;color:#fff}.auth-brand{padding:7vw;display:flex;flex-direction:column;justify-content:center;background:radial-gradient(circle at 25% 25%,rgba(59,231,255,.13),transparent 34%)}.auth-brand img{width:110px;margin-bottom:38px}.auth-brand h1{font-size:clamp(2.6rem,4.7vw,4.8rem);line-height:1;letter-spacing:-.055em}.auth-brand p{color:#91a3ba;line-height:1.8;margin-top:20px}.auth-panel{display:grid;place-items:center;padding:38px;border-left:1px solid rgba(123,196,255,.12)}.auth-card{width:100%;max-width:500px}.auth-card h2{font-size:2rem}.auth-card>p{color:#91a3ba;margin:8px 0 22px}.auth-card label{display:block;font-size:.78rem;color:#aebed0;margin:12px 0 6px}.auth-card input{width:100%;padding:14px 15px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#07111f;color:#fff}.auth-card input:focus{outline:none;border-color:#3be7ff}.auth-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:9px;background:linear-gradient(135deg,#3be7ff,#67a7ff);color:#03111a;font-weight:800;cursor:pointer}.auth-error{padding:12px 14px;border:1px solid rgba(255,99,99,.25);background:rgba(255,99,99,.07);border-radius:8px;color:#ff9b9b;margin-bottom:18px}.auth-links{margin-top:22px;color:#8194aa;font-size:.9rem}.auth-links a{color:#3be7ff;text-decoration:none}.password-note{display:block;margin-top:7px;color:#6f8399;font-size:.74rem}.back-home{position:absolute;top:24px;left:28px;color:#9dafc2;text-decoration:none;font-size:.85rem}@media(max-width:800px){.auth-shell{grid-template-columns:1fr}.auth-brand{display:none}.auth-panel{min-height:100vh;border:0;padding:28px}}
</style>
</head>
<body>
<a class="back-home" href="index.php"><i class="fas fa-arrow-left"></i> Cybte AI</a>
<div class="auth-shell">
<section class="auth-brand"><img src="assets/images/logo.png" alt="Cybte AI"><h1>One account for the Cybte security ecosystem.</h1><p>Create a customer account for access to enabled Cybte AI services. Individual products remain subject to availability, plan and organization access.</p></section>
<section class="auth-panel"><div class="auth-card">
<h2>Create your account</h2><p>Start with a secure Cybte AI identity.</p>
<?php if ($error !== ''): ?><div class="auth-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" autocomplete="on">
<?php echo csrf_input(); ?>
<label for="name">Full name</label><input id="name" name="name" maxlength="100" autocomplete="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
<label for="email">Email address</label><input id="email" type="email" name="email" maxlength="190" autocomplete="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
<label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="10" required><small class="password-note">At least 10 characters with uppercase, lowercase and a number.</small>
<label for="confirm_password">Confirm password</label><input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="10" required>
<button class="auth-submit" type="submit">Create account <i class="fas fa-arrow-right"></i></button>
</form>
<div class="auth-links">Already have an account? <a href="login.php">Sign in</a></div>
</div></section>
</div>
</body>
</html>
