<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

session_regenerate_id(true);

require_once __DIR__ . '/../src/config/database.php';

// Get user info
try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cybte AI</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #0d1b2a 50%, #0a1628 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: rgba(13, 25, 48, 0.95);
            border-right: 1px solid rgba(0, 229, 255, 0.1);
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.1);
        }
        .sidebar-logo img {
            width: 45px;
            height: 45px;
            border-radius: 10px;
        }
        .sidebar-logo h2 {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
        }
        .sidebar-logo span {
            color: #00e5ff;
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }
        .sidebar-nav {
            list-style: none;
        }
        .sidebar-nav li {
            margin-bottom: 8px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(0, 229, 255, 0.1);
            color: #00e5ff;
        }
        .sidebar-nav i {
            width: 20px;
            text-align: center;
        }
        .sidebar-logout {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
        }
        .sidebar-logout a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.2);
            border-radius: 10px;
            color: #ff6b6b;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .sidebar-logout a:hover {
            background: rgba(255, 68, 68, 0.2);
        }
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 25px;
            background: rgba(13, 25, 48, 0.7);
            border: 1px solid rgba(0, 229, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }
        .topbar-welcome h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .topbar-welcome p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(0, 229, 255, 0.1);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 10px;
        }
        .topbar-user i {
            color: #00e5ff;
            font-size: 18px;
        }
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(13, 25, 48, 0.6);
            border: 1px solid rgba(0, 229, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 229, 255, 0.2);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .stat-header h3 {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .stat-icon.blue { background: rgba(0, 229, 255, 0.1); color: #00e5ff; }
        .stat-icon.orange { background: rgba(255, 165, 0, 0.1); color: #ffa500; }
        .stat-icon.green { background: rgba(0, 255, 136, 0.1); color: #00ff88; }
        .stat-icon.red { background: rgba(255, 68, 68, 0.1); color: #ff4444; }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .stat-change {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }
        /* Services Grid */
        .services-section {
            margin-bottom: 30px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-header h2 {
            font-size: 20px;
            font-weight: 600;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .service-card {
            background: rgba(13, 25, 48, 0.6);
            border: 1px solid rgba(0, 229, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00e5ff, #00b8d4);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .service-card:hover::before {
            transform: scaleX(1);
        }
        .service-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 229, 255, 0.2);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
        }
        .service-icon {
            width: 50px;
            height: 50px;
            background: rgba(0, 229, 255, 0.1);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #00e5ff;
            margin-bottom: 16px;
        }
        .service-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .service-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            line-height: 1.5;
        }
        .service-arrow {
            position: absolute;
            top: 24px;
            right: 24px;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .service-card:hover .service-arrow {
            color: #00e5ff;
            transform: translateX(4px);
        }
        /* Activity Section */
        .activity-section {
            background: rgba(13, 25, 48, 0.6);
            border: 1px solid rgba(0, 229, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
        }
        .activity-list {
            list-style: none;
            margin-top: 16px;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .activity-icon.verify { background: rgba(0, 229, 255, 0.1); color: #00e5ff; }
        .activity-icon.fraud { background: rgba(255, 165, 0, 0.1); color: #ffa500; }
        .activity-icon.scan { background: rgba(0, 255, 136, 0.1); color: #00ff88; }
        .activity-content p {
            font-size: 14px;
            margin-bottom: 2px;
        }
        .activity-content span {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo" style="justify-content: center; border-bottom: none; margin-bottom: 20px;">
                <div style="position: relative; padding: 8px; background: linear-gradient(135deg, rgba(0,229,255,0.3), rgba(0,184,212,0.1)); border-radius: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);">
                    <div style="position: relative; padding: 4px; background: #0a1628; border-radius: 20px;">
                        <img src="assets/images/logo.png" alt="Cybte AI" style="width: px; height: 100px; border-radius: 16px; display: block; box-shadow: 0 4px 16px rgba(0,0,0,0.4);">
                        <div style="position: absolute; top: -2px; left: 20%; right: 20%; height: 2px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); border-radius: 1px;"></div>
                    </div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="vpn_dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="vpn_servers.php"><i class="fas fa-server"></i> VPN Servers</a></li>
                <li><a href="vpn_pricing.php"><i class="fas fa-credit-card"></i> Purchase Plan</a></li>
                <li><a href="vpn_download.php"><i class="fas fa-download"></i> Download Apps</a></li>
                <li><a href="vpn_security.php"><i class="fas fa-shield-alt"></i> Security</a></li>
                <li><a href="dashboard.php"><i class="fas fa-layer-group"></i> Main App</a></li>
            </ul>
            
            <div class="sidebar-logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="topbar-welcome">
                    <h1 style="font-size: 20px;"><span style="color: #fff; font-weight: 700;">CYB</span><span style="color: #00e5ff; font-weight: 700;">TE</span> <span style="color: #fff; font-weight: 400;">AI VPN</span></h1>
                    <p>Secure your connection, protect your privacy</p>
                </div>
                <div class="topbar-actions">
                    <div class="topbar-user">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['user_email'] ?? $user['email'] ?? 'User'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Fraud Alerts</h3>
                        <div class="stat-icon blue"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <div class="stat-value">12</div>
                    <div class="stat-change">+2 from yesterday</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Pending Verifications</h3>
                        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="stat-value">5</div>
                    <div class="stat-change">3 urgent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Security Scans</h3>
                        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="stat-value">18</div>
                    <div class="stat-change">All systems secure</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>High Risk Transactions</h3>
                        <div class="stat-icon red"><i class="fas fa-ban"></i></div>
                    </div>
                    <div class="stat-value">3</div>
                    <div class="stat-change">Blocked today</div>
                </div>
            </div>

            <!-- Services Section -->
            <div class="services-section">
                <div class="section-header">
                    <h2>VPN Services</h2>
                </div>
                <div class="services-grid">
                    <a href="vpn_servers.php" class="service-card">
                        <div class="service-icon"><i class="fas fa-server"></i></div>
                        <div class="service-arrow"><i class="fas fa-arrow-right"></i></div>
                        <h3>VPN Servers</h3>
                        <p>Connect to high-speed servers in 15+ countries with low latency</p>
                    </a>
                    <a href="vpn_pricing.php" class="service-card">
                        <div class="service-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="service-arrow"><i class="fas fa-arrow-right"></i></div>
                        <h3>Upgrade Plan</h3>
                        <p>Get more traffic and longer subscription with premium plans</p>
                    </a>
                    <a href="vpn_download.php" class="service-card">
                        <div class="service-icon"><i class="fas fa-download"></i></div>
                        <div class="service-arrow"><i class="fas fa-arrow-right"></i></div>
                        <h3>Download Apps</h3>
                        <p>Get V2Ray, Clash, and other VPN clients for all platforms</p>
                    </a>
                    <a href="vpn_security.php" class="service-card">
                        <div class="service-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="service-arrow"><i class="fas fa-arrow-right"></i></div>
                        <h3>Security Center</h3>
                        <p>Reset subscription links and manage account security</p>
                    </a>
                </div>
            </div>

            <!-- Activity Section -->
            <div class="activity-section">
                <div class="section-header">
                    <h2>VPN Status</h2>
                </div>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon verify"><i class="fas fa-globe"></i></div>
                        <div class="activity-content">
                            <p>Connected to United States server</p>
                            <span>IP: 47.90.170.143 • Port: 443</span>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon green" style="background: rgba(0, 255, 136, 0.1); color: #00ff88;"><i class="fas fa-lock"></i></div>
                        <div class="activity-content">
                            <p>Encryption: VLESS + XTLS</p>
                            <span>Protocol secure • 12ms latency</span>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon orange"><i class="fas fa-chart-bar"></i></div>
                        <div class="activity-content">
                            <p>Traffic used: 2.4GB / 10GB</p>
                            <span>Expires in 28 days</span>
                        </div>
                    </li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>