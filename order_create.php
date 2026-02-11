<?php
// order_create.php
session_start();
include "connection.php";

// Protect page - require customer login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$error_message = "";
$success_message = "";
$control_number = "";
$order_id = "";

// Generate 12-digit control number
function generateControlNumber() {
    return str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
}

// Get menu_id from POST
$menu_id = isset($_POST['menu_id']) ? (int)$_POST['menu_id'] : 0;

if ($menu_id === 0) {
    header("Location: customermenu.php?error=Invalid menu item");
    exit();
}

// Fetch menu item details
$menu_sql = "SELECT * FROM menu WHERE id = ? AND availability = 1";
$menu_stmt = $conn->prepare($menu_sql);
$menu_stmt->bind_param("i", $menu_id);
$menu_stmt->execute();
$menu_result = $menu_stmt->get_result();
$menu_item = $menu_result->fetch_assoc();

if (!$menu_item) {
    header("Location: customermenu.php?error=Menu item not found");
    exit();
}

// Fetch customer details
$customer_sql = "SELECT * FROM customers WHERE id = ?";
$customer_stmt = $conn->prepare($customer_sql);
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
    $order_details = isset($_POST['order_details']) ? trim($_POST['order_details']) : '';

    // Validate inputs
    if ($quantity < 1) {
        $error_message = "Quantity must be at least 1.";
    } elseif (empty($delivery_address)) {
        $error_message = "Delivery address is required.";
    } else {
        // Calculate totals
        $item_price = $menu_item['price'];
        $subtotal = $item_price * $quantity;
        $tax = $subtotal * 0.18; // 18% tax
        $total_amount = $subtotal + $tax;

        // Generate control number for this order
        $control_number = generateControlNumber();

        // Insert order into orders table
        $order_sql = "INSERT INTO orders (customer_id, order_date, total_amount, status, delivery_address, order_details) 
                      VALUES (?, NOW(), ?, 'pending', ?, ?)";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("idss", $customer_id, $total_amount, $delivery_address, $order_details);

        if ($order_stmt->execute()) {
            $order_id = $conn->insert_id;

            // Insert order item into order_items table
            $item_sql = "INSERT INTO order_items (order_id, menu_id, quantity, price, subtotal) 
                         VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_sql);
            $item_stmt->bind_param("iiiid", $order_id, $menu_id, $quantity, $item_price, $subtotal);

            if ($item_stmt->execute()) {
                $success_message = "✓ Order created successfully! Order ID: #" . $order_id;
            } else {
                $error_message = "Error creating order item: " . $item_stmt->error;
            }
        } else {
            $error_message = "Error creating order: " . $order_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order - Matekakx Delicious</title>
    <link rel="stylesheet" href="customerstyle.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .order-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-header a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .order-header a:hover {
            text-decoration: underline;
        }

        .order-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .order-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .order-section h3 {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-section h3 i {
            color: #667eea;
            font-size: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8e8e8;
        }

        .info-label {
            color: #999;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .menu-card-display {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #667eea;
            margin-bottom: 20px;
        }

        .menu-card-image-display {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .menu-card-details {
            padding: 20px;
        }

        .menu-name {
            color: #333;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu-category {
            color: #667eea;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .menu-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .menu-price {
            color: #27ae60;
            font-size: 22px;
            font-weight: 700;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid #667eea;
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            color: #667eea;
            font-size: 20px;
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

        .form-group label i {
            color: #667eea;
            margin-right: 6px;
        }

        .form-group input,
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
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .form-group input[type="number"] {
            max-width: 150px;
        }

        .summary-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #f9fafb 100%);
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e8e8e8;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8e8e8;
            font-weight: 600;
        }

        .summary-row.total {
            font-size: 18px;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-top: 10px;
        }

        .alert-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        .alert-message i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert-message.error {
            background: #fadbd8;
            color: #c0392b;
            border-left: 4px solid #c0392b;
        }

        .alert-message.success {
            background: #d5f4e6;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        /* Control Number Modal/Display */
        .control-number-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            animation: fadeIn 0.3s ease;
        }

        .control-number-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .control-number-content {
            background: white;
            padding: 50px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        .control-number-icon {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .control-number-title {
            color: #333;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .control-number-label {
            color: #999;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .control-number-display {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            padding: 30px;
            background: #f8f9fa;
            border: 3px solid #667eea;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .control-number-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .control-number-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-copy-control {
            flex: 1;
            padding: 12px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-copy-control:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-continue {
            flex: 1;
            padding: 12px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-continue:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            flex: 1;
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-cancel {
            flex: 1;
            padding: 15px 20px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-3px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
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
            .order-layout {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .order-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .control-number-content {
                padding: 30px 20px;
            }

            .control-number-display {
                font-size: 32px;
                letter-spacing: 2px;
            }

            .control-number-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="order-container">
    <!-- Header -->
    <div class="order-header">
        <h1><i class="fas fa-shopping-cart"></i> Create Order</h1>
        <a href="customermenu.php"><i class="fas fa-arrow-left"></i> Back to Menu</a>
    </div>

    <!-- Error/Success Messages -->
    <?php if (!empty($error_message)): ?>
        <div class="alert-message error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert-message success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Control Number Modal (Displayed after order creation) -->
    <?php if (!empty($control_number) && !empty($order_id)): ?>
    <div class="control-number-modal show" id="controlNumberModal">
        <div class="control-number-content">
            <div class="control-number-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="control-number-title">Order Confirmed!</h2>
            <p class="control-number-label">Your Control Number Is</p>
            <div class="control-number-display" id="controlDisplay">
                <?php echo $control_number; ?>
            </div>
            <p class="control-number-message">
                <strong>Order ID: #<?php echo $order_id; ?></strong><br>
                Save this control number for your payment and order tracking.<br>
                You will need it when making payment.
            </p>
            <div class="control-number-buttons">
                <button class="btn-copy-control" onclick="copyControlNumber()">
                    <i class="fas fa-copy"></i> Copy Number
                </button>
                <a href="orderdetails.php?order_id=<?php echo $order_id; ?>" class="btn-continue">
                    <i class="fas fa-eye"></i> View Order Details
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Details Layout -->
    <div class="order-layout">
        <!-- Menu Item Details (Left) -->
        <div>
            <div class="order-section">
                <h3><i class="fas fa-info-circle"></i> Menu Item Details</h3>

                <div class="menu-card-display">
                    <?php if ($menu_item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($menu_item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($menu_item['name']); ?>" 
                             class="menu-card-image-display">
                    <?php else: ?>
                        <div class="menu-card-image-display">
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-size: 64px;">
                                <i class="fas fa-image"></i>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="menu-card-details">
                        <div class="menu-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($menu_item['category']); ?>
                        </div>
                        <div class="menu-name">
                            <i class="fas fa-star" style="color: #f39c12;"></i>
                            <?php echo htmlspecialchars($menu_item['name']); ?>
                        </div>
                        <p class="menu-description">
                            <?php echo htmlspecialchars($menu_item['description']); ?>
                        </p>
                        <div class="menu-price">
                            Tsh <?php echo number_format($menu_item['price'], 2); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information (Left) -->
            <div class="order-section" style="margin-top: 20px;">
                <h3><i class="fas fa-user"></i> Your Information</h3>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user-circle"></i> Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['fullname']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-venus-mars"></i> Gender</span>
                    <span class="info-value"><?php echo htmlspecialchars(ucfirst($customer['gender'] ?? 'N/A')); ?></span>
                </div>
                <div class="info-row" style="border: none;">
                    <span class="info-label"><i class="fas fa-home"></i> Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Order Form (Right) -->
        <div>
            <form method="POST" class="form-section">
                <h3><i class="fas fa-edit"></i> Order Details</h3>

                <div class="form-group">
                    <label for="quantity">
                        <i class="fas fa-cubes"></i> Quantity *
                    </label>
                    <input type="number" id="quantity" name="quantity" min="1" value="1" required 
                           onchange="updateSummary()">
                </div>

                <div class="form-group">
                    <label for="delivery_address">
                        <i class="fas fa-map-marker-alt"></i> Delivery Address *
                    </label>
                    <textarea id="delivery_address" name="delivery_address" rows="4" 
                              placeholder="Enter your delivery address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="order_details">
                        <i class="fas fa-note-sticky"></i> Special Instructions (Optional)
                    </label>
                    <textarea id="order_details" name="order_details" rows="3" 
                              placeholder="Any special requests or instructions?"></textarea>
                </div>

                <!-- Order Summary -->
                <div class="summary-box">
                    <h4 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">
                        <i class="fas fa-receipt"></i> Order Summary
                    </h4>
                    <div class="summary-row">
                        <span><i class="fas fa-tag"></i> Item Price:</span>
                        <span id="item-price">Tsh <?php echo number_format($menu_item['price'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-cubes"></i> Quantity:</span>
                        <span id="summary-quantity">1</span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-calculator"></i> Subtotal:</span>
                        <span id="subtotal">Tsh <?php echo number_format($menu_item['price'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-percent"></i> Tax (18%):</span>
                        <span id="tax">Tsh <?php echo number_format($menu_item['price'] * 0.18, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span><i class="fas fa-money-bill-wave"></i> Total Amount:</span>
                        <span id="total">Tsh <?php echo number_format($menu_item['price'] * 1.18, 2); ?></span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <a href="customermenu.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="confirm_order" class="btn-submit">
                        <i class="fas fa-check"></i> Confirm Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Update summary when quantity changes
    function updateSummary() {
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        const itemPrice = <?php echo $menu_item['price']; ?>;
        
        const subtotal = itemPrice * quantity;
        const tax = subtotal * 0.18;
        const total = subtotal + tax;

        document.getElementById('summary-quantity').textContent = quantity;
        document.getElementById('subtotal').textContent = 'Tsh ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('tax').textContent = 'Tsh ' + tax.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total').textContent = 'Tsh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Copy control number to clipboard
    function copyControlNumber() {
        const controlNumber = document.getElementById('controlDisplay').textContent.trim();
        const tempInput = document.createElement('input');
        tempInput.value = controlNumber;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);

        // Show feedback
        const btn = event.target.closest('.btn-copy-control');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#27ae60';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#3498db';
        }, 2000);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('delivery_address').value = '<?php echo htmlspecialchars($customer['address'] ?? ''); ?>';
    });
</script>

</body>
</html>