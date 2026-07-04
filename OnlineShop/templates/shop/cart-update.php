<?php

require_once '../../bootstrap.php';
require_once '../../model/Product.php';

$productId = (int)($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($productId <= 0 || !isset($_SESSION['cart'][$productId])) {
    header('Location: cart.php');
    exit;
}

$productModel = new Product();
$product = $productModel->find($productId);

if (!$product) {
    header('Location: cart.php');
    exit;
}

if ($action === 'increase') {
    if ($_SESSION['cart'][$productId]['quantity'] < (int)$product['stock']) {
        $_SESSION['cart'][$productId]['quantity']++;
    }
}

if ($action === 'decrease') {
    $_SESSION['cart'][$productId]['quantity']--;

    if ($_SESSION['cart'][$productId]['quantity'] <= 0) {
        unset($_SESSION['cart'][$productId]);
    }
}

header('Location: cart.php');
exit;