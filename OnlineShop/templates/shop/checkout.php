<?php

require_once '../../bootstrap.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$total = 0;

foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

$customerName = '';
$customerEmail = '';

if (isset($_SESSION['user'])) {
    $customerName = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
    $customerEmail = $_SESSION['user']['email'];
}

require_once 'layout/header.php';
require_once 'layout/navbar.php';
?>

<h1 class="checkout-title">Checkout</h1>

<section class="checkout-container">

    <div class="checkout-form">

        <h2>Delivery Information</h2>

        <form action="place-order.php" method="POST">

            <label>Full Name</label>
            <input
                type="text"
                name="customer_name"
                value="<?= htmlspecialchars($customerName) ?>"
                required>

            <label>Email</label>
            <input
                type="email"
                name="customer_email"
                value="<?= htmlspecialchars($customerEmail) ?>"
                required>

            <label>Phone</label>
            <input
                type="text"
                name="customer_phone"
                required>

            <label>Delivery Address</label>
            <textarea
                name="customer_address"
                rows="5"
                required></textarea>

            <button type="submit">
                Place Order
            </button>

        </form>

    </div>

    <div class="order-summary">

        <h2>Order Summary</h2>

        <?php foreach ($cart as $item): ?>
            <div class="summary-item">
                <span>
                    <?= htmlspecialchars($item['name']) ?>
                    × <?= (int)$item['quantity'] ?>
                </span>

                <strong>
                    €<?= number_format($item['price'] * $item['quantity'], 2) ?>
                </strong>
            </div>
        <?php endforeach; ?>

        <hr>

        <div class="summary-total">
            <span>Total</span>
            <strong>€<?= number_format($total, 2) ?></strong>
        </div>

    </div>

</section>

<?php require_once 'layout/footer.php'; ?>

<style>
.checkout-title{
    max-width:1100px;
    margin:40px auto 20px;
}

.checkout-container{
    max-width:1100px;
    margin:0 auto 50px;
    display:grid;
    grid-template-columns:1fr 380px;
    gap:40px;
}

.checkout-form,
.order-summary{
    background:#fff;
    padding:30px;
    border:1px solid #ddd;
    border-radius:10px;
}

.checkout-form label{
    display:block;
    margin-top:15px;
    font-weight:bold;
}

.checkout-form input,
.checkout-form textarea{
    width:100%;
    padding:12px;
    margin-top:6px;
    border:1px solid #ccc;
    border-radius:6px;
}

.checkout-form button{
    margin-top:25px;
    width:100%;
    padding:14px;
    background:#d6001c;
    color:white;
    border:none;
    border-radius:6px;
    font-size:17px;
    cursor:pointer;
}

.summary-item,
.summary-total{
    display:flex;
    justify-content:space-between;
    margin:15px 0;
}

.summary-total{
    font-size:20px;
}
</style>