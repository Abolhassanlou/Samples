<?php

require_once __DIR__ . '/../model/Order.php';

class OrderController
{
    public function store(array $data, array $cart): int
    {
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $orderData = [
            'user_id' => $_SESSION['user']['id'] ?? null,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'],
            'total_price' => $total,
        ];

        $orderModel = new Order();

        return $orderModel->create($orderData, $cart);
    }
    public function userOrders(): array 
    {
        $orderModel = new Order();
        return $orderModel->getByUserId((int)$_SESSION['user']['id']);
    }
    public function userOrderDetails(int $orderId): array|false
    {
        $orderModel = new Order();
        $order = $orderModel->findForUser($orderId, (int)$_SESSION['user']['id']);
        if(!$order){
            return false;
        }
        $order['items'] = $orderModel->items($orderId);
        return $order;
    }
}