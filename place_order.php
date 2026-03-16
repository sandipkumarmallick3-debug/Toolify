<?php
/**
 * Toolify — Place Order API
 * POST /place_order.php
 * Body: { cart: [{id, qty}], customerName, customerPhone, customerAddress }
 */

require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST method is allowed', 405);
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart']) || !is_array($input['cart']) || count($input['cart']) === 0) {
    sendError('Cart is empty or invalid');
}

$customerName = isset($input['customerName']) ? trim($input['customerName']) : '';
$customerPhone = isset($input['customerPhone']) ? trim($input['customerPhone']) : '';
$customerAddress = isset($input['customerAddress']) ? trim($input['customerAddress']) : '';

if (empty($customerName) || empty($customerPhone) || empty($customerAddress)) {
    sendError('Customer name, phone, and address are required');
}

$userId = isset($input['userId']) ? (int) $input['userId'] : null;

$db = getDB();

try {
    $db->beginTransaction();

    // Calculate total
    $totalAmount = 0;
    $orderItems = [];

    foreach ($input['cart'] as $cartItem) {
        $productId = (int) $cartItem['id'];
        $qty = (int) $cartItem['qty'];

        if ($qty <= 0) continue;

        $stmt = $db->prepare("SELECT id, price, name FROM products WHERE id = :id");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $db->rollBack();
            sendError("Product with ID {$productId} not found");
        }

        $itemTotal = $product['price'] * $qty;
        $totalAmount += $itemTotal;

        $orderItems[] = [
            'product_id' => $productId,
            'quantity' => $qty,
            'price' => $product['price']
        ];
    }

    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total_amount)
        VALUES (:user_id, :name, :phone, :address, :total)
    ");
    $stmt->execute([
        'user_id' => $userId,
        'name' => $customerName,
        'phone' => $customerPhone,
        'address' => $customerAddress,
        'total' => $totalAmount
    ]);


    $orderId = $db->lastInsertId();

    // Insert order items
    $stmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ");

    foreach ($orderItems as $item) {
        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ]);
    }

    $db->commit();

    sendJSON([
        'success' => true,
        'orderId' => (int) $orderId,
        'total' => $totalAmount,
        'message' => 'Order placed successfully!'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    sendError('Failed to place order: ' . $e->getMessage(), 500);
}
?>
