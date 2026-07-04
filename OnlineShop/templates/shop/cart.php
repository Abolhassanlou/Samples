<?php

require_once '../../bootstrap.php';

$cart = $_SESSION['cart'] ?? [];

require_once 'layout/header.php';
require_once 'layout/navbar.php';

$total = 0;
?>

<h1 class="cart-title">Shopping Cart</h1>

<section class="cart-container">

    <?php if (empty($cart)): ?>

        <p>Your cart is empty.</p>
        <a href="products.php">Continue Shopping</a>

    <?php else: ?>

        <table class="cart-table">
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>

            <?php foreach ($cart as $item): ?>
                <?php
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>

                <tr>
                    <td>
                       <a href="product.php?id=<?= $item['id'] ?>" class="product-link">

                       
                        <?php if (!empty($item['img_name'])): ?>
                            <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($item['img_name']) ?>" width="70">
                        <?php endif; ?>

                        <?= htmlspecialchars($item['name']) ?>
                        </a>
                    </td>

                    <td>€<?= number_format($item['price'], 2) ?></td>

                    <td>
                        <form action="cart-update.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" class="qty-btn">-</button>
                        </form>

                        <span class="qty-number">
                            <?= (int)$item['quantity'] ?>
                        </span>

                        <form action="cart-update.php" method="POST" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </td>

                    <td>€<?= number_format($subtotal, 2) ?></td>
                </tr>

            <?php endforeach; ?>
        </table>

        <h2 class="cart-total">
            Total: €<?= number_format($total, 2) ?>
        </h2>

        <a href="products.php" class="btn-secondary">Continue Shopping</a>
        <a href="checkout.php" class="btn-checkout">Checkout</a>

    <?php endif; ?>

</section>

<?php require_once 'layout/footer.php'; ?>

<style>
.cart-title{
    max-width:1100px;
    margin:40px auto 20px;
}

.cart-container{
    max-width:1100px;
    margin:0 auto 50px;
}

.cart-table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

.cart-table th,
.cart-table td{
    border:1px solid #ddd;
    padding:15px;
    text-align:left;
}

.cart-table img{
    vertical-align:middle;
    margin-right:15px;
    border-radius:6px;
}

.qty-btn{
    width:32px;
    height:32px;
    border:none;
    background:#d6001c;
    color:white;
    border-radius:4px;
    cursor:pointer;
    font-size:18px;
}

.qty-number{
    display:inline-block;
    margin:0 10px;
    font-weight:bold;
}

.cart-total{
    text-align:right;
    margin-top:25px;
}

.btn-secondary,
.btn-checkout{
    display:inline-block;
    margin-top:20px;
    padding:12px 24px;
    text-decoration:none;
    border-radius:6px;
    font-weight:bold;
}

.btn-secondary{
    background:#eee;
    color:#222;
}

.btn-checkout{
    background:#d6001c;
    color:white;
    margin-left:10px;
}
</style>