 <?php
session_start();
include "connection.php";

// Protect page - require customer login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch all available menu items from database
$result = mysqli_query($conn, "SELECT * FROM menu WHERE availability = 1 ORDER BY category, created_at DESC");

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Menu - Matekakx Delicious</title>
    <link rel="stylesheet" href="customerstyle.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .menu-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-header a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .menu-header a:hover {
            text-decoration: underline;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-title {
            color: #333;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-title i {
            color: #667eea;
            font-size: 24px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .menu-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .menu-card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .menu-card-title {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu-card-description {
            color: #999;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
            flex-grow: 1;
        }

        .menu-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .menu-card-price {
            color: #27ae60;
            font-size: 18px;
            font-weight: 700;
        }

        .menu-card-button {
            padding: 10px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .menu-card-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .no-items {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .no-items i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-items p {
            color: #999;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .menu-card-image {
                height: 150px;
            }

            .menu-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Header -->
    <div class="menu-header">
        <h1><i class="fas fa-utensils"></i> Our Food Menu</h1>
        <a href="customerdashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Menu Items by Category -->
    <?php
    // Organize menu items by category
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $category = $row['category'] ?? 'Uncategorized';
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $row;
    }

    if (empty($categories)):
    ?>
        <div class="no-items">
            <i class="fas fa-inbox"></i>
            <h2>No Menu Items Available</h2>
            <p>Please check back later for our delicious food offerings.</p>
        </div>
    <?php
    else:
        $categoryIcons = [
            'Appetizers' => 'fas fa-drumstick-bite',
            'Main Courses' => 'fas fa-utensils',
            'Beverages' => 'fas fa-glass-water',
            'Desserts' => 'fas fa-ice-cream',
            'Salads' => 'fas fa-leaf',
            'Soups' => 'fas fa-mug-hot'
        ];

        foreach ($categories as $categoryName => $items):
            $icon = $categoryIcons[$categoryName] ?? 'fas fa-plate-wheat';
    ?>
        <div class="category-section">
            <div class="category-title">
                <i class="<?php echo $icon; ?>"></i>
                <?php echo htmlspecialchars($categoryName); ?>
            </div>

            <div class="menu-grid">
                <?php foreach ($items as $item): ?>
                    <div class="menu-card">
                        <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="menu-card-image">
                        <?php else: ?>
                            <div class="menu-card-image">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>

                        <div class="menu-card-content">
                            <div class="menu-card-title">
                                <i class="fas fa-star" style="color: #f39c12;"></i>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <p class="menu-card-description">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>
                        </div>

                        <div class="menu-card-footer">
                            <span class="menu-card-price">
                                Tsh <?php echo number_format($item['price'], 2); ?>
                            </span>
                            <form action="order_create.php" method="post" style="margin: 0;">
                                <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                <button type="submit" class="menu-card-button">
                                    <i class="fas fa-shopping-cart"></i> Order Now
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; endif; ?>

</div>

</body>
</html>