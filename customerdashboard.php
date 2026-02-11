<?php
// customerdashboard.php
session_start();
include "connection.php";

// Protect page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch recent orders (last 3)
$sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 3";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = null;
}

// Fetch comprehensive stats
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_spent,
    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN status = 'confirmed' THEN total_amount ELSE 0 END) as confirmed_amount,
    SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as delivered_amount,
    SUM(CASE WHEN status = 'cancelled' THEN total_amount ELSE 0 END) as cancelled_amount,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
FROM orders WHERE customer_id = ?";
$stats_stmt = $conn->prepare($stats_sql);

if ($stats_stmt) {
    $stats_stmt->bind_param("i", $customer_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
} else {
    $stats = [
        'total_orders' => 0, 
        'total_spent' => 0,
        'pending_amount' => 0,
        'confirmed_amount' => 0,
        'delivered_amount' => 0,
        'cancelled_amount' => 0,
        'pending_orders' => 0,
        'confirmed_orders' => 0,
        'delivered_orders' => 0,
        'cancelled_orders' => 0
    ];
}

// Calculate amount required to pay (pending + confirmed)
$amount_to_pay = ($stats['pending_amount'] ?? 0) + ($stats['confirmed_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="customerstyle.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced Stats Styles */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .stat-card.primary {
            border-left-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .stat-card.success {
            border-left-color: #27ae60;
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.05) 0%, rgba(46, 204, 113, 0.05) 100%);
        }

        .stat-card.warning {
            border-left-color: #f39c12;
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.05) 0%, rgba(230, 126, 34, 0.05) 100%);
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.05) 0%, rgba(192, 57, 43, 0.05) 100%);
        }

        .stat-card.info {
            border-left-color: #3498db;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05) 0%, rgba(41, 128, 185, 0.05) 100%);
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-icon.primary {
            color: #667eea;
        }

        .stat-icon.success {
            color: #27ae60;
        }

        .stat-icon.warning {
            color: #f39c12;
        }

        .stat-icon.danger {
            color: #e74c3c;
        }

        .stat-icon.info {
            color: #3498db;
        }

        .stat-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: #333;
        }

        .stat-subtext {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
            font-weight: 500;
        }

        .highlight-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }

        .stat-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .breakdown-item {
            text-align: center;
        }

        .breakdown-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
        }

        .breakdown-value {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-top: 4px;
        }

        /* Main content adjustments */
        .main-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .quick-stat {
            background: white;
            padding: 18px;
            border-radius: 10px;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .quick-stat:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .quick-stat.danger {
            border-left-color: #e74c3c;
        }

        .quick-stat.success {
            border-left-color: #27ae60;
        }

        .quick-stat h4 {
            margin: 0 0 8px 0;
            color: #999;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .quick-stat p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .quick-stat i {
            margin-right: 6px;
            font-size: 16px;
        }

        .quick-stat.danger i {
            color: #e74c3c;
        }

        .quick-stat.success i {
            color: #27ae60;
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 24px;
            }

            .stat-breakdown {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Header -->
    <div class="dashboard-header">
        <h1><i class="fas fa-hamburger"></i> Matekakx Delicious Food</h1>
        <div class="user-info">
            <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['customer_name']); ?></strong>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
            <a href="customerlogout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h2><i class="fas fa-hand-wave"></i> Welcome, <?php echo explode(' ', $_SESSION['customer_name'])[0]; ?>!</h2>
        <p>🍽️ Share delicious food with us.</p>
    </div>

    <!-- Quick Stats (Top Row) -->
    <div class="main-stats">
        <!-- Total Orders -->
        <div class="quick-stat success">
            <h4><i class="fas fa-shopping-bag"></i> Total Orders</h4>
            <p><?php echo $stats['total_orders'] ?? 0; ?></p>
        </div>

        <!-- Amount to Pay -->
        <div class="quick-stat danger">
            <h4><i class="fas fa-credit-card"></i> Amount to Pay</h4>
            <p>Tsh <?php echo number_format($amount_to_pay, 2); ?></p>
        </div>

        <!-- Total Spent -->
        <div class="quick-stat success">
            <h4><i class="fas fa-money-bill-wave"></i> Total Spent</h4>
            <p>Tsh <?php echo number_format($stats['total_spent'] ?? 0, 2); ?></p>
        </div>
    </div>

    <!-- Enhanced Stats Cards -->
    <div class="stats-container">
        <!-- All Orders Card -->
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-shopping-bag"></i>
                <span>All Orders</span>
            </div>
            <div class="stat-label">Total Orders Made</div>
            <p class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></p>
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <div class="breakdown-label">Pending</div>
                    <div class="breakdown-value" style="color: #f39c12;">
                        <?php echo $stats['pending_orders'] ?? 0; ?>
                    </div>
                </div>
                <div class="breakdown-item">
                    <div class="breakdown-label">Confirmed</div>
                    <div class="breakdown-value" style="color: #3498db;">
                        <?php echo $stats['confirmed_orders'] ?? 0; ?>
                    </div>
                </div>
                <div class="breakdown-item">
                    <div class="breakdown-label">Delivered</div>
                    <div class="breakdown-value" style="color: #27ae60;">
                        <?php echo $stats['delivered_orders'] ?? 0; ?>
                    </div>
                </div>
                <div class="breakdown-item">
                    <div class="breakdown-label">Cancelled</div>
                    <div class="breakdown-value" style="color: #e74c3c;">
                        <?php echo $stats['cancelled_orders'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Amount to Pay Card -->
        <div class="stat-card danger">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>Payment Due</span>
            </div>
            <div class="stat-label">Amount Required to Pay</div>
            <p class="stat-value">Tsh <?php echo number_format($amount_to_pay, 2); ?></p>
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <div class="breakdown-label">Pending Orders</div>
                    <div class="breakdown-value">
                        Tsh <?php echo number_format($stats['pending_amount'] ?? 0, 0); ?>
                    </div>
                </div>
                <div class="breakdown-item">
                    <div class="breakdown-label">Confirmed Orders</div>
                    <div class="breakdown-value">
                        Tsh <?php echo number_format($stats['confirmed_amount'] ?? 0, 0); ?>
                    </div>
                </div>
            </div>
            <a href="payments.php" style="display: inline-block; margin-top: 12px; padding: 8px 16px; background: #e74c3c; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600;">
                <i class="fas fa-credit-card"></i> Make Payment
            </a>
        </div>

        <!-- Total Spent Card -->
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
                <span>Total Spent</span>
            </div>
            <div class="stat-label">Total Amount Spent</div>
            <p class="stat-value">Tsh <?php echo number_format($stats['total_spent'] ?? 0, 2); ?></p>
            <div class="stat-breakdown">
                <div class="breakdown-item">
                    <div class="breakdown-label">Delivered</div>
                    <div class="breakdown-value">
                        Tsh <?php echo number_format($stats['delivered_amount'] ?? 0, 0); ?>
                    </div>
                </div>
                <div class="breakdown-item">
                    <div class="breakdown-label">Cancelled</div>
                    <div class="breakdown-value">
                        Tsh <?php echo number_format($stats['cancelled_amount'] ?? 0, 0); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="action-buttons">
        <a href="customermenu.php"><i class="fas fa-utensils"></i> Choose your Menu</a>
         
        <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    </div>

    <!-- Recent Orders -->
    <div class="content-section">
        <h3><i class="fas fa-list"></i> Recent Orders</h3>

        <div class="profile-info">
            <?php if ($orders && $orders->num_rows > 0): ?>
                <?php while ($row = $orders->fetch_assoc()): ?>
                    <div class="profile-item">
                        <label>
                            <i class="fas fa-receipt"></i> Order #<?php echo htmlspecialchars($row['id']); ?> 
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>" style="float: right; margin-top: -5px;">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </label>
                        <p><?php echo htmlspecialchars($row['order_details']); ?></p>
                        <small>
                            <i class="fas fa-money-bill-wave"></i> Total: <strong>Tsh <?php echo number_format($row['total_amount'], 2); ?></strong><br>
                            <i class="fas fa-calendar"></i> Date: <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?><br>
                            <i class="fas fa-map-marker-alt"></i> Address: <?php echo htmlspecialchars($row['delivery_address']); ?>
                        </small>
                        <div style="margin-top: 12px;">
                            <a href="orderdetails.php?order_id=<?php echo htmlspecialchars($row['id']); ?>" style="color: #667eea; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="profile-item" style="text-align: center; grid-column: 1 / -1;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <p>No orders yet. <a href="customermenu.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Start ordering delicious food!</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
 

</body>
</html>