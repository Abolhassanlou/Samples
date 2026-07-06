<?php

require_once __DIR__ . '/../config/DB.php';

class Order extends DB
{
    public function create(array $orderData, array $cart): int
    {
        $connection = $this->getConnection();

        $connection->beginTransaction();

        try {
            $sql = "
                INSERT INTO orders
                (
                    user_id,
                    customer_name,
                    customer_email,
                    customer_phone,
                    customer_address,
                    total_price,
                    status
                )
                VALUES
                (
                    :user_id,
                    :customer_name,
                    :customer_email,
                    :customer_phone,
                    :customer_address,
                    :total_price,
                    :status
                )
            ";

            $stmt = $connection->prepare($sql);

            $stmt->execute([
                ':user_id' => $orderData['user_id'],
                ':customer_name' => $orderData['customer_name'],
                ':customer_email' => $orderData['customer_email'],
                ':customer_phone' => $orderData['customer_phone'],
                ':customer_address' => $orderData['customer_address'],
                ':total_price' => $orderData['total_price'],
                ':status' => 'pending',
            ]);

            $orderId = (int)$connection->lastInsertId();

            foreach ($cart as $item) {
                $subtotal = $item['price'] * $item['quantity'];

                $itemSql = "
                    INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        quantity,
                        price,
                        subtotal
                    )
                    VALUES
                    (
                        :order_id,
                        :product_id,
                        :product_name,
                        :quantity,
                        :price,
                        :subtotal
                    )
                ";

                $itemStmt = $connection->prepare($itemSql);

                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['id'],
                    ':product_name' => $item['name'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price'],
                    ':subtotal' => $subtotal,
                ]);

                $stockSql = "
                    UPDATE products
                    SET stock = stock - :quantity
                    WHERE id = :product_id
                ";

                $stockStmt = $connection->prepare($stockSql);

                $stockStmt->execute([
                    ':quantity' => $item['quantity'],
                    ':product_id' => $item['id'],
                ]);
            }

            $connection->commit();

            return $orderId;

        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
    public function getByUserId(int $userId): array
{
    $connection = $this->getConnection();

    $sql = "
        SELECT *
        FROM orders
        WHERE user_id = :user_id
        ORDER BY id DESC
    ";

    $stmt = $connection->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function findForUser(int $orderId , int $userId): array|false 
{
    $connection = $this->getConnection();
    $sql = "
    SELECT * FROM orders 
    WHERE id = :id
    AND user_id = :user_id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        ':id' => $orderId, 
        ':user_id'=>$userId
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
public function items(int $orderId): array
{
    $connection = $this->getConnection();

    $sql = "
        SELECT *
        FROM order_items
        WHERE order_id = :order_id
    ";

    $stmt = $connection->prepare($sql);
    $stmt->execute([
        ':order_id' => $orderId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function all(): array {
    $connection = $this->getConnection();
    $sql = "
    SELECT orders.*, 
    users.first_name,
    users.last_name,
    users.email as user_email
    FROM orders
    LEFT JOIN users
        ON orders.user_id = users.id 
    ORDER by orders.id DESC
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    return $stmt->fetchALL(PDO::FETCH_ASSOC);
}
public function find(int $orderId): array|false
{
    $connection = $this->getConnection();
    $sql = "
        SELECT * FROM orders
        WHERE id= :id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute([':id'=> $orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);

}
public function getItems(int $orderId): array {
    $connection = $this->getConnection();
    $sql = "
        SELECT * 
        FROM order_items
        WHERE order_id = :order_id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute([
        ':order_id'=>$orderId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function updateStatus(int $orderId, string $status): bool
{
    $connection = $this->getConnection();

    $sql = "
        UPDATE orders
        SET status = :status
        WHERE id = :id
    ";

    $stmt = $connection->prepare($sql);

    return $stmt->execute([
        ':status' => $status,
        ':id' => $orderId
    ]);
}
}