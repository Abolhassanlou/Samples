<?php

require_once '../../bootstrap.php';
require_once '../../controller/OrderController.php';

$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$requiredFields = [
    'customer_name',
    'customer_email',
    'customer_phone',
    'customer_address'
];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        header('Location: checkout.php');
        exit;
    }
}

$controller = new OrderController();

$orderId = $controller->store($_POST, $cart);

unset($_SESSION['cart']);

header('Location: order-success.php?id=' . $orderId);
exit;