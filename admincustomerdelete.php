<?php
// admincustomerdelete.php
session_start();
include "connection.php";

// Security check: admin only
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admincustomers.php?error=Invalid customer ID");
    exit();
}

$customer_id = (int)$_GET['id'];

/*
 OPTIONAL BUT RECOMMENDED:
 Delete related data first to avoid orphan records
 (orders, payments, etc.)
*/

// Delete customer orders
$stmt = $conn->prepare("DELETE FROM orders WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();

// Delete customer payments (if table exists)
if ($conn->query("SHOW TABLES LIKE 'payments'")->num_rows > 0) {
    $stmt = $conn->prepare("DELETE FROM payments WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
}

// Delete customer account
$stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();

// Redirect back with success message
header("Location: admincustomers.php?message=Customer deleted successfully");
exit();
