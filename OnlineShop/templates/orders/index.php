<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/OrderController.php';

IsAdmin::check();

$controller = new OrderController();
$orders = $controller->index();

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Orders</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($orders as $order): ?>
        <tr>
            <td>#<?= $order['id'] ?></td>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
            <td><?= htmlspecialchars($order['customer_email']) ?></td>
            <td>€<?= number_format($order['total_price'], 2) ?></td>
            <td>
                <form action="update-status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="redirect" value="index.php">

                    <select name="status">
                        <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update</button>
                </form>
            </td>
            <td><?= htmlspecialchars($order['created_at']) ?></td>
            <td>
                <a href="show.php?id=<?= $order['id'] ?>">View</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once '../layout/footer.php'; ?>