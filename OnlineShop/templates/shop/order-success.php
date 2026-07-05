<?php

require_once '../../bootstrap.php';

$orderId = (int)($_GET['id'] ?? 0);

require_once 'layout/header.php';
require_once 'layout/navbar.php';
?>

<section style="max-width:800px; margin:60px auto; text-align:center;">

    <h1>Order Placed Successfully</h1>

    <p>Your order number is:</p>

    <h2>#<?= $orderId ?></h2>

    <a href="<?= BASE_URL ?>/templates/shop/products.php">
        Continue Shopping
    </a>

</section>

<?php require_once 'layout/footer.php'; ?>