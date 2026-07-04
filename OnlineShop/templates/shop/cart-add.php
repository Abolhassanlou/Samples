<?php

require_once '../../bootstrap.php';
require_once '../../model/Product.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    header('Location: products.php');
    exit;
}

$productModel = new Product();
$product = $productModel->find($productId);

if (!$product) {
    header('Location: products.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$productId] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'img_name' => $product['img_name'],
        'quantity' => $quantity
    ];
}

header('Location: cart.php');
exit;