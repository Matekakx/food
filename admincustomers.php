<?php
session_start();
include "connection.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$customers_query = "SELECT * FROM customers LIMIT $offset, $limit";
$customers_result = mysqli_query($conn, $customers_query);

$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers");
$total_row = mysqli_fetch_assoc($total_query);
$total_pages = ceil($total_row['total'] / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Admin Dashboard</title>
    <link rel="stylesheet" href="adminstyle.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <p>Food Ordering System</p>
        <a href="admindashboard.php" class="nav-link">🏠 Dashboard</a>
        <a href="admincustomers.php" class="nav-link active-tab">👥 Customers</a>
        <a href="adminmenu.php" class="nav-link">🍔 Menu Items</a>
        <a href="adminorders.php" class="nav-link">📦 Orders</a>
        <a href="adminpayments.php" class="nav-link">💰 Payments</a>
        <a href="adminreports.php" class="nav-link">📊 Reports</a>
        <a href="admindashboard.php?logout=true" class="nav-link" style="background: #c0392b; margin-top: 50px;">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>Customers Management</h1>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($customers_result)): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                             
                            <a href="admincustomerdelete.php?id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" style="margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="admincustomers.php?page=1">« First</a>
                <a href="admincustomers.php?page=<?php echo $page - 1; ?>">‹ Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="admincustomers.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="admincustomers.php?page=<?php echo $page + 1; ?>">Next ›</a>
                <a href="admincustomers.php?page=<?php echo $total_pages; ?>">Last »</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>