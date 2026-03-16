<?php
/**
 * Toolify — Get Products API
 * GET /get_products.php              → all products
 * GET /get_products.php?category=welding → filter by category
 */

require_once __DIR__ . '/db_config.php';

$db = getDB();

$category = isset($_GET['category']) ? trim($_GET['category']) : null;

if ($category && $category !== 'all') {
    $stmt = $db->prepare("SELECT id, name, category, price, imgUrl, description as 'desc' FROM products WHERE category = :category ORDER BY id");
    $stmt->execute(['category' => $category]);
} else {
    $stmt = $db->query("SELECT id, name, category, price, imgUrl, description as 'desc' FROM products ORDER BY id");
}

$products = $stmt->fetchAll();

// Ensure price is numeric
foreach ($products as &$product) {
    $product['price'] = (float) $product['price'];
    $product['id'] = (int) $product['id'];
}

sendJSON($products);
?>
