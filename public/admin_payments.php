<?php
/**
 * Admin Payment Overview Page
 * View all payments, statistics, and manage payment settings
 */

session_start();

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';

use Cybte\Payment\PaymentFactory;

// Check admin authentication
require_role(['admin'], 'vpn_login.php');

$database = new Database();
$conn = $database->connect();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$methodFilter = $_GET['method'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$whereClauses = ["p.created_at BETWEEN :date_from AND :date_to"];
$params = [':date_from' => $dateFrom . ' 00:00:00', ':date_to' => $dateTo . ' 23:59:59'];

if ($statusFilter !== 'all') {
    $whereClauses[] = "p.status = :status";
    $params[':status'] = $statusFilter;
}

if ($methodFilter !== 'all') {
    $whereClauses[] = "p.method = :method";
    $params[':method'] = $methodFilter;
}

$whereSql = implode(' AND ', $whereClauses);

// Get payments with user info
$paymentsQuery = "
    SELECT p.*, u.email, u.name as user_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE {$whereSql}
    ORDER BY p.created_at DESC
    LIMIT 100
";

$stmt = $conn->prepare($paymentsQuery);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'paid' AND method = 'alipay' THEN amount ELSE 0 END) as alipay_revenue,
        SUM(CASE WHEN status = 'paid' AND method = 'wechat' THEN amount ELSE 0 END) as wechat_revenue
    FROM payments p
    WHERE {$whereSql}
";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Today's revenue
$todayQuery = "
    SELECT COALESCE(SUM(amount), 0) as today_revenue, COUNT(*) as today_count
    FROM payments
    WHERE status = 'paid' AND DATE(created_at) = CURDATE()
";
$todayStmt = $conn->query($todayQuery);
$todayStats = $todayStmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Payment Overview | TrustShield AI</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/payment.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(31, 41, 55, 0.85);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }
        
        .stat-card.paid {
            border-color: rgba(0, 255, 136, 0.3);
            background: rgba(0, 255, 136, 0.08);
        }
        
        .stat-card.pending {
            border-color: rgba(255, 193, 7, 0.3);
            background: rgba(255, 193, 7, 0.08);
        }
        
        .stat-card.revenue {
            border-color: rgba(0, 229, 255, 0.4);
            background: rgba(0, 229, 255, 0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: #00E5FF;
            margin-bottom: 8px;
        }
        
        .stat-card.paid .stat-value {
            color: #00ff88;
        }
        
        .stat-card.pending .stat-value {
            color: #ffc107;
        }
        
        .stat-label {
            color: #b0c4de;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .filters-section {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(0, 229, 255, 0.15);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .filter-group label {
            color: #b0c4de;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .filter-group select,
        .filter-group input {
            background: rgba(10, 25, 41, 0.6);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 8px;
            padding: 10px 14px;
            color: #ffffff;
            font-size: 0.9rem;
            min-width: 140px;
        }
        
        .filter-btn {
            background: linear-gradient(45deg, #00E5FF, #0099CC);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: #0a1929;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 229, 255, 0.3);
        }
        
        .payments-table-container {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(0, 229, 255, 0.15);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payments-table th {
            background: rgba(0, 229, 255, 0.1);
            color: #00E5FF;
            font-weight: 800;
            text-align: left;
            padding: 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payments-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.1);
            color: #b0c4de;
        }
        
        .payments-table tr:hover td {
            background: rgba(0, 229, 255, 0.05);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .status-badge.paid {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
        }
        
        .status-badge.pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        
        .status-badge.failed {
            background: rgba(255, 68, 68, 0.15);
            color: #ff7a7a;
        }
        
        .amount-cell {
            color: #00E5FF;
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        .method-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .method-icon.alipay {
            color: #1677FF;
        }
        
        .method-icon.wechat {
            color: #07C160;
        }
        
        .order-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: #8a9bb0;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-email {
            color: #ffffff;
            font-weight: 600;
        }
        
        .user-id {
            font-size: 0.75rem;
            color: #8a9bb0;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.2);
        }
        
        .admin-title {
            font-size: 1.8rem;
            color: #00E5FF;
            font-weight: 800;
        }
        
        .back-link {
            color: #b0c4de;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .back-link:hover {
            color: #00E5FF;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #8a9bb0;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .payments-table {
                font-size: 0.85rem;
            }
            
            .payments-table th,
            .payments-table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>

<div class="starry-background"></div>
<div class="stars"></div>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="admin-title"><i class="fas fa-chart-line"></i> Payment Overview</h1>
        <a href="vpn_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card revenue">
            <div class="stat-value">¥<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue (Period)</div>
        </div>
        <div class="stat-card paid">
            <div class="stat-value"><?php echo $stats['paid_count'] ?? 0; ?></div>
            <div class="stat-label">Paid Orders</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['failed_count'] ?? 0; ?></div>
            <div class="stat-label">Failed Orders</div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-value">¥<?php echo number_format($todayStats['today_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $todayStats['today_count'] ?? 0; ?></div>
            <div class="stat-label">Today's Orders</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Payment Method</label>
                <select name="method">
                    <option value="all" <?php echo $methodFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                    <option value="alipay" <?php echo $methodFilter === 'alipay' ? 'selected' : ''; ?>>Alipay</option>
                    <option value="wechat" <?php echo $methodFilter === 'wechat' ? 'selected' : ''; ?>>WeChat Pay</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
            </div>
            
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
            </div>
            
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </form>
    </div>
    
    <!-- Payments Table -->
    <div class="payments-table-container">
        <?php if (empty($payments)): ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <p>No payments found for the selected criteria.</p>
            </div>
        <?php else: ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <span class="order-id"><?php echo htmlspecialchars($payment['order_id']); ?></span>
                            </td>
                            <td>
                                <div class="user-info">
                                    <span class="user-email"><?php echo htmlspecialchars($payment['email'] ?? 'Unknown'); ?></span>
                                    <span class="user-id">ID: <?php echo $payment['user_id']; ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                            <td class="amount-cell">
                                ¥<?php echo number_format($payment['amount'], 2); ?>
                            </td>
                            <td>
                                <span class="method-icon <?php echo $payment['method']; ?>">
                                    <?php if ($payment['method'] === 'alipay'): ?>
                                        <i class="fab fa-alipay"></i> Alipay
                                    <?php else: ?>
                                        <i class="fab fa-weixin"></i> WeChat
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $payment['status']; ?>">
                                    <?php if ($payment['status'] === 'paid'): ?>
                                        <i class="fas fa-check-circle"></i> Paid
                                    <?php elseif ($payment['status'] === 'pending'): ?>
                                        <i class="fas fa-clock"></i> Pending
                                    <?php elseif ($payment['status'] === 'refunded'): ?>
                                        <i class="fas fa-undo"></i> Refunded
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Failed
                                    <?php endif; ?>
                                </span>
                                <?php if ($payment['status'] === 'paid'): ?>
                                    <button class="refund-btn" onclick="openRefundModal('<?php echo $payment['order_id']; ?>', <?php echo $payment['amount']; ?>)">
                                        <i class="fas fa-undo"></i> Refund
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Refund Modal -->
<div id="refundModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-undo"></i> Process Refund</h3>
            <button class="close-btn" onclick="closeRefundModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="refund-details">
                <p><strong>Order ID:</strong> <span id="refundOrderId"></span></p>
                <p><strong>Original Amount:</strong> ¥<span id="refundOriginalAmount"></span></p>
            </div>
            <div class="form-group">
                <label>Refund Amount (CNY)</label>
                <input type="number" id="refundAmount" step="0.01" min="0.01" required>
                <small>Enter amount between 0.01 and original amount</small>
            </div>
            <div class="form-group">
                <label>Refund Reason</label>
                <textarea id="refundReason" rows="3" placeholder="Enter reason for refund..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-confirm" onclick="processRefund()">Process Refund</button>
            <button class="btn-cancel" onclick="closeRefundModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
let currentRefundOrder = null;
let currentMaxRefund = 0;

function openRefundModal(orderId, amount) {
    currentRefundOrder = orderId;
    currentMaxRefund = amount;
    document.getElementById('refundOrderId').textContent = orderId;
    document.getElementById('refundOriginalAmount').textContent = amount.toFixed(2);
    document.getElementById('refundAmount').max = amount;
    document.getElementById('refundAmount').value = amount;
    document.getElementById('refundModal').style.display = 'block';
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
    currentRefundOrder = null;
    currentMaxRefund = 0;
}

function processRefund() {
    const amount = parseFloat(document.getElementById('refundAmount').value);
    const reason = document.getElementById('refundReason').value;
    
    if (!amount || amount <= 0 || amount > currentMaxRefund) {
        alert('Invalid refund amount');
        return;
    }
    
    if (!confirm('Are you sure you want to process this refund?')) {
        return;
    }
    
    fetch('api/payment/refund.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: currentRefundOrder,
            amount: amount,
            reason: reason
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Refund processed successfully');
            location.reload();
        } else {
            alert('Refund failed: ' + data.error);
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('refundModal');
    if (event.target === modal) {
        closeRefundModal();
    }
}
</script>

<style>
.refund-btn {
    background: rgba(255, 68, 68, 0.15);
    border: 1px solid rgba(255, 68, 68, 0.3);
    color: #ff7a7a;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    margin-left: 8px;
    transition: all 0.2s;
}

.refund-btn:hover {
    background: rgba(255, 68, 68, 0.25);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(5, 11, 30, 0.9);
    backdrop-filter: blur(8px);
}

.modal-content {
    background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(15, 25, 45, 0.95));
    border: 1px solid rgba(0, 229, 255, 0.3);
    border-radius: 16px;
    width: 90%;
    max-width: 480px;
    margin: 100px auto;
    box-shadow: 0 25px 60px rgba(0, 229, 255, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 229, 255, 0.15);
}

.modal-header h3 {
    color: #00E5FF;
    margin: 0;
}

.close-btn {
    background: none;
    border: none;
    color: #b0c4de;
    font-size: 1.8rem;
    cursor: pointer;
}

.modal-body {
    padding: 24px;
}

.refund-details {
    background: rgba(10, 25, 41, 0.6);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.refund-details p {
    margin: 8px 0;
    color: #b0c4de;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #b0c4de;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input,
.form-group textarea {
    width: 100%;
    background: rgba(10, 25, 41, 0.6);
    border: 1px solid rgba(0, 229, 255, 0.2);
    border-radius: 8px;
    padding: 12px;
    color: #ffffff;
    font-size: 1rem;
}

.form-group small {
    color: #8a9bb0;
    font-size: 0.8rem;
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid rgba(0, 229, 255, 0.15);
}

.btn-confirm {
    flex: 1;
    background: linear-gradient(45deg, #ff7a7a, #ff5252);
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    color: #ffffff;
    font-weight: 800;
    cursor: pointer;
}

.btn-cancel {
    flex: 1;
    background: transparent;
    border: 1px solid rgba(0, 229, 255, 0.3);
    border-radius: 8px;
    padding: 12px 24px;
    color: #b0c4de;
    font-weight: 600;
    cursor: pointer;
}

.status-badge.refunded {
    background: rgba(156, 39, 176, 0.15);
    color: #9c27b0;
}
</style>

</body>
</html>
