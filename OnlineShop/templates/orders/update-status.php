<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/OrderController.php';

IsAdmin::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';

$controller = new OrderController();
$controller->updateStatus($orderId, $status);


$redirect = $_POST['redirect'] ?? 'index.php';

header('Location: ' . $redirect);
exit;
