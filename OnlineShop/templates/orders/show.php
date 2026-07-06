<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/OrderController.php';

IsAdmin::check();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$controller = new OrderController();

$order = $controller->show((int)$_GET['id']);

if (!$order) {
    header('Location: index.php');
    exit;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Order #<?= $order['id'] ?></h1>

<p>
    <strong>Customer:</strong>
    <?= htmlspecialchars($order['customer_name']) ?>
</p>

<p>
    <strong>Email:</strong>
    <?= htmlspecialchars($order['customer_email']) ?>
</p>

<p>
    <strong>Phone:</strong>
    <?= htmlspecialchars($order['customer_phone']) ?>
</p>

<p>
    <strong>Address:</strong><br>
    <?= nl2br(htmlspecialchars($order['customer_address'])) ?>
</p>

<p>
    <strong>Status:</strong>
    <?= htmlspecialchars($order['status']) ?>
</p>
<form action="update-status.php" method="POST">
    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
    <input type="hidden" name="redirect" value="show.php?id=<?= $order['id'] ?>">

    <select name="status">
        <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
            <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                <?= ucfirst($status) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Update Status</button>
</form>

<p>
    <strong>Total:</strong>
    €<?= number_format($order['total_price'],2) ?>
</p>

<h2>Products</h2>

<table border="1" cellpadding="10">

<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Subtotal</th>
</tr>

<?php foreach($order['items'] as $item): ?>

<tr>

    <td><?= htmlspecialchars($item['product_name']) ?></td>

    <td>€<?= number_format($item['price'],2) ?></td>

    <td><?= $item['quantity'] ?></td>

    <td>€<?= number_format($item['subtotal'],2) ?></td>

</tr>

<?php endforeach; ?>

</table>

<br>

<a href="index.php">← Back</a>

<?php require_once '../layout/footer.php'; ?>