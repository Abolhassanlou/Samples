<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsUser.php';
require_once '../../controller/OrderController.php';

IsUser::check();

$controller = new OrderController();
$orders = $controller->userOrders();

require_once '../layout/header.php';
require_once 'layout/sidebar.php';
?>

<h1>My Orders</h1>

<?php if (empty($orders)): ?>

    <p>You have no orders yet.</p>
    <a href="<?= BASE_URL ?>/templates/shop/products.php">Start Shopping</a>

<?php else: ?>

    <table border="1" cellpadding="10">
        <tr>
            <th>Order ID</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
        </tr>

        <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    <a href="order.php?id=<?= $order['id'] ?>">
                        #<?= $order['id'] ?>
                    </a>
                </td>
                <td>€<?= number_format($order['total_price'], 2) ?></td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td><?= htmlspecialchars($order['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

<?php endif; ?>

<?php require_once '../layout/footer.php'; ?>