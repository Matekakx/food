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

// Fetch stats
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_spent
FROM orders WHERE customer_id = ?";
$stats_stmt = $conn->prepare($stats_sql);

if ($stats_stmt) {
    $stats_stmt->bind_param("i", $customer_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
} else {
    $stats = ['total_orders' => 0, 'total_spent' => 0];
}
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
        <p> Share delicious food with us.</p>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: white; padding: 20px; border-radius: 10px; border-left: 5px solid #667eea; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">
                <i class="fas fa-shopping-bag"></i> Total Orders
            </h4>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #667eea;">
                <?php echo $stats['total_orders'] ?? 0; ?>
            </p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 10px; border-left: 5px solid #27ae60; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 10px 0; color: #999; font-size: 12px; text-transform: uppercase;">
                <i class="fas fa-money-bill-wave"></i> Total Spent
            </h4>
            <p style="margin: 0; font-size: 28px; font-weight: 700; color: #27ae60;">
                Tsh <?php echo number_format($stats['total_spent'] ?? 0, 2); ?>
            </p>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="action-buttons">
        <a href="customermenu.php"><i class="fas fa-utensils"></i> Food Menu</a>
         
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
                            <i class="fas fa-calendar"></i> Date: <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
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

<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-pending { background: #ffeaa7; color: #d35400; }
    .status-confirmed { background: #74b9ff; color: #0984e3; }
    .status-delivered { background: #55efc4; color: #00b894; }
    .status-cancelled { background: #fab1a0; color: #d63031; }
</style>

</body>
</html>