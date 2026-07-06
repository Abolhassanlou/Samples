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

    <a href="<?= BASE_URL ?>/templates/shop/products.php">
        Start Shopping
    </a>

<?php else: ?>

<table class="orders-table">

    <thead>
        <tr>
            <th>Order</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th>Details</th>
        </tr>
    </thead>

    <tbody>

    <?php foreach ($orders as $order): ?>

        <tr>

            <td>
                #<?= $order['id'] ?>
            </td>

            <td>
                €<?= number_format($order['total_price'],2) ?>
            </td>

            <td>
                <span class="status <?= htmlspecialchars($order['status']) ?>">
                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                </span>
            </td>

            <td>
                <?= htmlspecialchars($order['created_at']) ?>
            </td>

            <td>
                <a class="view-btn"
                   href="order.php?id=<?= $order['id'] ?>">
                    View
                </a>
            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php endif; ?>

<?php require_once '../layout/footer.php'; ?>


<style>

.orders-table{

    width:100%;

    border-collapse:collapse;

    margin-top:25px;

    background:#fff;

}

.orders-table th{

    background:#f5f5f5;

    padding:15px;

    text-align:left;

}

.orders-table td{

    padding:15px;

    border-top:1px solid #eee;

}

.status{

    padding:6px 12px;

    border-radius:20px;

    color:#fff;

    font-size:14px;

    font-weight:bold;

}

.status.pending{

    background:#f39c12;

}

.status.processing{

    background:#3498db;

}

.status.shipped{

    background:#8e44ad;

}

.status.delivered{

    background:#27ae60;

}

.status.cancelled{

    background:#e74c3c;

}

.view-btn{

    background:#d6001c;

    color:#fff;

    text-decoration:none;

    padding:8px 15px;

    border-radius:5px;

}

.view-btn:hover{

    background:#b30018;

}

</style>