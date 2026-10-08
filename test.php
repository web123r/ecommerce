



<?php
include ("./includes/header.php");
include 'functions.php';
include 'database.php';




$update ="SELECT *
 FROM items
 LEFT JOIN images
 ON items.id = images   .item_id
"; 




<?php
session_start();
require 'db.php'; // MySQLi connection instance: $conn (e.g. $conn = new mysqli(...))

// 1. Guard clauses
if (empty($_SESSION['cart']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$cart = $_SESSION['cart']; // Format: [product_id => quantity]
$userId = (int)$_SESSION['user_id'];

// 2. Fetch fresh prices and inventory from MySQL using dynamic placeholders
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids)); // All IDs are integers

$query = "SELECT id, price, stock FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[$row['id']] = $row;
}
$stmt->close();

// 3. Calculate the true order total
$totalAmount = 0.00;
foreach ($cart as $productId => $qty) {
    if (isset($products[$productId])) {
        $totalAmount += $products[$productId]['price'] * $qty;
    }
}

// 4. Save to Database using a MySQL Transaction
$conn->begin_transaction();

try {
    // Step A: Insert master order
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, status) 
        VALUES (?, ?, 'completed')
    ");
    $orderStmt->bind_param("id", $userId, $totalAmount);
    $orderStmt->execute();
    $orderId = $conn->insert_id;
    $orderStmt->close();

    // Step B: Prepare statements for batch insertion & stock reduction
    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, price, quantity) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stockStmt = $conn->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ?
    ");

    foreach ($cart as $productId => $qty) {
        if (!isset($products[$productId])) {
            continue;
        }

        $unitPrice = (float)$products[$productId]['price'];
        $qty = (int)$qty;
        $productId = (int)$productId;

        // Bind variables: order_id (i), product_id (i), price (d), quantity (i)
        $itemStmt->bind_param("iidi", $orderId, $productId, $unitPrice, $qty);
        $itemStmt->execute();

        // Deduct inventory: quantity (i), product_id (i)
        $stockStmt->bind_param("ii", $qty, $productId);
        $stockStmt->execute();
    }

    $itemStmt->close();
    $stockStmt->close();

    // Step C: Commit all queries together
    $conn->commit();

    // Step D: Wipe the cart session
    unset($_SESSION['cart']);

    // Redirect to confirmation
    header("Location: order_success.php?id=" . $orderId);
    exit();

} catch (Exception $e) {
    // Revert all queries if any step fails
    $conn->rollback();
    die("Checkout failed: " . $e->getMessage());
}

?>
