<?php
// admindashboard.php
session_start();
include "connection.php";

// Protect page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

$admin_username = $_SESSION['admin_username'];
$message = "";
$message_type = "";

// Handle Menu Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // ADD MENU ITEM
    if ($action === 'add_menu') {
        $name = isset($_POST['menu_name']) ? trim($_POST['menu_name']) : '';
        $description = isset($_POST['menu_description']) ? trim($_POST['menu_description']) : '';
        $price = isset($_POST['menu_price']) ? floatval($_POST['menu_price']) : 0;
        $category = isset($_POST['menu_category']) ? trim($_POST['menu_category']) : '';
        $availability = isset($_POST['menu_availability']) ? 1 : 0;
        
        $image_url = "";
        
        // Handle image upload
        if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] === 0) {
            $upload_dir = 'uploads/menu_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['menu_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = uniqid('menu_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['menu_image']['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                }
            }
        }

        if ($name && $price > 0 && $category) {
            $sql = "INSERT INTO menu (name, description, price, category, image_url, availability) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdisi", $name, $description, $price, $category, $image_url, $availability);
            
            if ($stmt->execute()) {
                $message = "Menu item added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding menu item: " . $stmt->error;
                $message_type = "error";
            }
        } else {
            $message = "Please fill in all required fields!";
            $message_type = "error";
        }
    }

    // UPDATE MENU ITEM
    elseif ($action === 'update_menu') {
        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        $name = isset($_POST['menu_name']) ? trim($_POST['menu_name']) : '';
        $description = isset($_POST['menu_description']) ? trim($_POST['menu_description']) : '';
        $price = isset($_POST['menu_price']) ? floatval($_POST['menu_price']) : 0;
        $category = isset($_POST['menu_category']) ? trim($_POST['menu_category']) : '';
        $availability = isset($_POST['menu_availability']) ? 1 : 0;

        $image_url = isset($_POST['existing_image']) ? $_POST['existing_image'] : "";

        // Handle image update
        if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] === 0) {
            $upload_dir = 'uploads/menu_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['menu_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                // Delete old image if exists
                if ($image_url && file_exists($image_url)) {
                    unlink($image_url);
                }
                
                $new_filename = uniqid('menu_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['menu_image']['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                }
            }
        }

        if ($menu_id && $name && $price > 0) {
            $sql = "UPDATE menu SET name=?, description=?, price=?, category=?, image_url=?, availability=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdisii", $name, $description, $price, $category, $image_url, $availability, $menu_id);
            
            if ($stmt->execute()) {
                $message = "Menu item updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating menu item!";
                $message_type = "error";
            }
        }
    }

    // DELETE MENU ITEM
    elseif ($action === 'delete_menu') {
        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        
        if ($menu_id) {
            // Get image path to delete it
            $sql_get = "SELECT image_url FROM menu WHERE id = ?";
            $stmt_get = $conn->prepare($sql_get);
            $stmt_get->bind_param("i", $menu_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            
            if ($row = $result_get->fetch_assoc()) {
                if ($row['image_url'] && file_exists($row['image_url'])) {
                    unlink($row['image_url']);
                }
            }
            
            $sql = "DELETE FROM menu WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $menu_id);
            
            if ($stmt->execute()) {
                $message = "Menu item deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting menu item!";
                $message_type = "error";
            }
        }
    }

    // UPDATE ORDER STATUS
    elseif ($action === 'update_order_status') {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $delivery_date = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : null;

        if ($order_id && $status) {
            $sql = "UPDATE orders SET status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $order_id);
            
            if ($stmt->execute()) {
                $message = "Order status updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating order status!";
                $message_type = "error";
            }
        }
    }

    // DELETE ORDER
    elseif ($action === 'delete_order') {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if ($order_id) {
            // First delete order items
            $delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
            $delete_items_stmt = $conn->prepare($delete_items_sql);
            $delete_items_stmt->bind_param("i", $order_id);
            $delete_items_stmt->execute();

            // Then delete order
            $sql = "DELETE FROM orders WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                $message = "Order deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting order!";
                $message_type = "error";
            }
        }
    }
}

// Fetch all menu items
$sql_menu = "SELECT * FROM menu ORDER BY created_at DESC";
$menu_result = $conn->query($sql_menu);

// Fetch all orders with customer details - including all order information
$sql_orders = "SELECT 
                o.id,
                o.customer_id,
                o.order_date,
                o.created_at,
                o.total_amount,
                o.status,
                o.delivery_address,
                o.order_details,
                c.fullname as customer_name,
                c.email as customer_email,
                c.phone as customer_phone
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              ORDER BY o.created_at DESC";
$orders_result = $conn->query($sql_orders);

// Fetch order statistics
$sql_stats = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(total_amount) as total_revenue
              FROM orders";
$stats_result = $conn->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// Fetch customer statistics
$sql_customers = "SELECT COUNT(*) as total_customers FROM customers";
$customers_result = $conn->query($sql_customers);
$customers_stats = $customers_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MATEKAKX DELICIOUS</title>
    <link rel="stylesheet" href="adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional Dashboard Styles */
        .admin-dashboard {
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-bar h1 {
            color: #333;
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-header-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }

        .admin-user-badge i {
            font-size: 18px;
        }

        .logout-link {
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-link:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .stat-card.primary {
            border-left-color: #667eea;
        }

        .stat-card.success {
            border-left-color: #27ae60;
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-card.info {
            border-left-color: #3498db;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 32px;
            opacity: 0.1;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .section-header h2 {
            color: #333;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: #667eea;
        }

        .btn-add {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-header h3 i {
            color: #667eea;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
        }

        .close-modal:hover {
            color: #333;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th {
            background: #f8f9fa;
            color: #333;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e8e8e8;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #e8e8e8;
            color: #555;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #27ae60;
            color: white;
        }

        .btn-view:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .message-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .message-box i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e8e8e8;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 150px;
            border-radius: 8px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            width: auto;
            accent-color: #667eea;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .top-bar h1 {
                justify-content: center;
            }

            .admin-header-info {
                flex-direction: column;
                width: 100%;
            }

            .logout-link {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 12px;
            }

            table th, table td {
                padding: 10px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: left;
            }
        }
    </style>
</head>
<body class="admin-dashboard">

<div class="dashboard-wrapper">
    <!-- Top Bar -->
    <div class="top-bar">
        <h1>
            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
        </h1>
        <div class="admin-header-info">
            <div class="admin-user-badge">
                <i class="fas fa-user-tie"></i>
                <span><?php echo htmlspecialchars($admin_username); ?></span>
            </div>
            <a href="adminlogout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Message Display -->
    <?php if ($message): ?>
        <div class="message-box message-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics Section -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['confirmed_orders'] ?? 0; ?></div>
            <div class="stat-label">Confirmed Orders</div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
            <div class="stat-label">Delivered Orders</div>
        </div>

        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $customers_stats['total_customers'] ?? 0; ?></div>
            <div class="stat-label">Total Customers</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-value">Tsh <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Menu Management Section -->
    <div class="section">
        <div class="section-header">
            <h2>
                <i class="fas fa-utensils"></i> Food Menu Management
            </h2>
            <button class="btn-add" onclick="openMenuModal('add')">
                <i class="fas fa-plus"></i> Add Menu Item
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-image"></i> Image</th>
                        <th><i class="fas fa-utensils"></i> Name</th>
                        <th><i class="fas fa-list"></i> Category</th>
                        <th><i class="fas fa-money-bill-wave"></i> Price (Tsh)</th>
                        <th><i class="fas fa-align-left"></i> Description</th>
                        <th><i class="fas fa-check"></i> Available</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($menu_result && $menu_result->num_rows > 0): ?>
                        <?php while ($menu = $menu_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($menu['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($menu['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($menu['name']); ?>" 
                                             style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                                    <?php else: ?>
                                        <span style="color: #999;"><i class="fas fa-image"></i> No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($menu['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($menu['category']); ?></td>
                                <td><strong>Tsh <?php echo number_format($menu['price'], 2); ?></strong></td>
                                <td><?php echo substr(htmlspecialchars($menu['description']), 0, 40); ?>...</td>
                                <td>
                                    <span class="status-badge <?php echo $menu['availability'] ? 'status-confirmed' : 'status-pending'; ?>">
                                        <?php echo $menu['availability'] ? '✓ Yes' : '✗ No'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-edit" onclick="editMenu(<?php echo $menu['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-small btn-delete" onclick="deleteMenu(<?php echo $menu['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox" style="font-size: 32px; margin-right: 10px;"></i>
                                No menu items found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Orders Management Section -->
    <div class="section">
        <div class="section-header">
            <h2>
                <i class="fas fa-shopping-bag"></i> Orders Management
            </h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> Order ID</th>
                        <th><i class="fas fa-user"></i> Customer</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-phone"></i> Phone</th>
                        <th><i class="fas fa-money-bill-wave"></i> Amount (Tsh)</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-calendar"></i> Order Date</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></td>
                                <td><strong>Tsh <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="orderdetails.php?order_id=<?php echo htmlspecialchars($order['id']); ?>" 
                                           class="btn-small btn-view" title="View Order Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button class="btn-small btn-edit" onclick="editOrder(<?php echo htmlspecialchars($order['id']); ?>)"
                                                title="Update Order Status">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-small btn-delete" onclick="deleteOrder(<?php echo htmlspecialchars($order['id']); ?>)"
                                                title="Delete Order">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <i class="fas fa-inbox" style="font-size: 32px; margin-right: 10px;"></i>
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Menu Modal -->
<div id="menuModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="menuModalTitle"><i class="fas fa-plus-circle"></i> Add Menu Item</h3>
            <button class="close-modal" onclick="closeMenuModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="menuAction" value="add_menu">
            <input type="hidden" name="menu_id" id="menuId" value="">
            <input type="hidden" name="existing_image" id="existingImage" value="">

            <div class="form-group">
                <label for="menuName"><i class="fas fa-utensils"></i> Menu Item Name *</label>
                <input type="text" id="menuName" name="menu_name" placeholder="e.g., Grilled Chicken" required>
            </div>

            <div class="form-group">
                <label for="menuDescription"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="menuDescription" name="menu_description" placeholder="Describe your food item" rows="4"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="menuPrice"><i class="fas fa-money-bill-wave"></i> Price (Tsh) *</label>
                    <input type="number" id="menuPrice" name="menu_price" step="0.01" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="menuCategory"><i class="fas fa-list"></i> Category *</label>
                    <select id="menuCategory" name="menu_category" required>
                        <option value="">Select a category</option>
                        <option value="Appetizers">Appetizers</option>
                        <option value="Main Courses">Main Courses</option>
                        <option value="Beverages">Beverages</option>
                        <option value="Desserts">Desserts</option>
                        <option value="Salads">Salads</option>
                        <option value="Soups">Soups</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="menuImage"><i class="fas fa-image"></i> Upload Image</label>
                <input type="file" id="menuImage" name="menu_image" accept="image/*" onchange="previewImage(event)">
                <img id="imagePreview" class="image-preview" style="display: none;">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="menuAvailability" name="menu_availability" checked>
                <label for="menuAvailability"><i class="fas fa-check-circle"></i> Available for order</label>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-add" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Menu Item
                </button>
                <button type="button" class="btn-add" style="background: #999; flex: 1;" onclick="closeMenuModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            <button class="close-modal" onclick="closeOrderModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_order_status">
            <input type="hidden" name="order_id" id="orderId" value="">

            <div class="form-group">
                <label for="orderStatus"><i class="fas fa-info-circle"></i> Order Status *</label>
                <select id="orderStatus" name="status" required>
                    <option value="">Select status</option>
                    <option value="pending">🕐 Pending</option>
                    <option value="confirmed">✓ Confirmed</option>
                    <option value="delivered">🚚 Delivered</option>
                    <option value="cancelled">✗ Cancelled</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-add" style="flex: 1;">
                    <i class="fas fa-save"></i> Update Status
                </button>
                <button type="button" class="btn-add" style="background: #999; flex: 1;" onclick="closeOrderModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
    function openMenuModal(mode) {
        const modal = document.getElementById('menuModal');
        const form = modal.querySelector('form');
        
        if (mode === 'add') {
            document.getElementById('menuModalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add Menu Item';
            document.getElementById('menuAction').value = 'add_menu';
            form.reset();
            document.getElementById('menuId').value = '';
            document.getElementById('existingImage').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        modal.classList.add('active');
    }

    function closeMenuModal() {
        document.getElementById('menuModal').classList.remove('active');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('active');
    }

    function editMenu(menuId) {
        alert('Edit functionality would fetch menu data and populate the form');
        openMenuModal('edit');
    }

    function deleteMenu(menuId) {
        if (confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_menu">
                <input type="hidden" name="menu_id" value="${menuId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editOrder(orderId) {
        document.getElementById('orderId').value = orderId;
        document.getElementById('orderModal').classList.add('active');
    }

    function deleteOrder(orderId) {
        if (confirm('Are you sure you want to delete this order and all its items? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_order">
                <input type="hidden" name="order_id" value="${orderId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const menuModal = document.getElementById('menuModal');
        const orderModal = document.getElementById('orderModal');
        
        if (event.target === menuModal) {
            menuModal.classList.remove('active');
        }
        if (event.target === orderModal) {
            orderModal.classList.remove('active');
        }
    });

    // Auto-refresh orders every 5 seconds
    setInterval(function() {
        // Optional: Add auto-refresh functionality
        // location.reload();
    }, 5000);
</script>

</body>
</html>