<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';

require_role(['admin'], 'dashboard.php');

$database = new Database();
$conn = $database->connect();
$stmt = $conn->prepare('SELECT id, name, email, role, created_at FROM users ORDER BY id DESC LIMIT 250');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Users — Cybte AI Administration</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>body{background:#040913;color:#fff;padding:40px;font-family:system-ui}.admin-wrap{max-width:1100px;margin:auto}.admin-wrap a{color:#3be7ff}.admin-table{width:100%;border-collapse:collapse;margin-top:25px;background:#081523}.admin-table th,.admin-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,.07);text-align:left;font-size:.84rem}.admin-table th{color:#7890a8}.role{font:700 .68rem ui-monospace;color:#3be7ff}@media(max-width:700px){body{padding:20px}.admin-table{display:block;overflow:auto}}</style>
</head>
<body><main class="admin-wrap"><a href="dashboard.php">← Back to dashboard</a><h1>Registered users</h1><p>Administrator-only account directory.</p><table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?php echo (int)$user['id']; ?></td><td><?php echo htmlspecialchars((string)$user['name']); ?></td><td><?php echo htmlspecialchars((string)$user['email']); ?></td><td><span class="role"><?php echo htmlspecialchars((string)$user['role']); ?></span></td><td><?php echo htmlspecialchars((string)$user['created_at']); ?></td></tr><?php endforeach; ?></tbody></table></main></body></html>
