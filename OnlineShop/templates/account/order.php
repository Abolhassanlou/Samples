<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsUser.php';
require_once '../../controller/OrderController.php';

IsUser::check();

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

$controller = new OrderController();

$order = $controller->userOrderDetails($orderId);

if (!$order) {
    header('Location: orders.php');
    exit;
}

require_once '../layout/header.php';
require_once 'layout/sidebar.php';
?>

<h1>Order #<?= $order['id'] ?></h1>

<div class="order-info">

    <p>
        <strong>Status:</strong>
        <?= htmlspecialchars($order['status']) ?>
    </p>

    <p>
        <strong>Date:</strong>
        <?= htmlspecialchars($order['created_at']) ?>
    </p>

    <p>
        <strong>Delivery Address:</strong><br>
        <?= nl2br(htmlspecialchars($order['customer_address'])) ?>
    </p>

</div>

<h2>Products</h2>

<table class="order-table">

    <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Subtotal</th>
    </tr>

    <?php foreach ($order['items'] as $item): ?>

        <tr>

            <td>
                <?= htmlspecialchars($item['product_name']) ?>
            </td>

            <td>
                €<?= number_format($item['price'],2) ?>
            </td>

            <td>
                <?= (int)$item['quantity'] ?>
            </td>

            <td>
                €<?= number_format($item['subtotal'],2) ?>
            </td>

        </tr>

    <?php endforeach; ?>

</table>

<h2 class="total">
    Total:
    €<?= number_format($order['total_price'],2) ?>
</h2>

<a class="back-btn" href="orders.php">
    ← Back to Orders
</a>

<?php require_once '../layout/footer.php'; ?>

<style>

.order-info{

    background:#fff;

    border:1px solid #ddd;

    padding:25px;

    border-radius:8px;

    margin-bottom:30px;

}

.order-table{

    width:100%;

    border-collapse:collapse;

}

.order-table th,
.order-table td{

    border:1px solid #ddd;

    padding:14px;

}

.order-table th{

    background:#f5f5f5;

}

.total{

    text-align:right;

    margin-top:30px;

}

.back-btn{

    display:inline-block;

    margin-top:25px;

    padding:12px 20px;

    background:#d6001c;

    color:white;

    text-decoration:none;

    border-radius:6px;

}

</style>