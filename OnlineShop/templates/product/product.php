<?php

require_once __DIR__ . '/../config/DB.php';

class Product
{
    private PDO $conn;

    public function __construct()
    {
        $db = new DB();
        $this->conn = $db->getConnection();
    }

    public function all(): array
    {
        $sql = "
            SELECT 
                products.*,
                categories.name AS category_name
            FROM products
            LEFT JOIN categories 
                ON products.category_id = categories.id
            ORDER BY products.id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO products
            (category_id, name, description, stock, price, is_active, img_name)
            VALUES
            (:category_id, :name, :description, :stock, :price, :is_active, :img_name)
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':stock' => $data['stock'],
            ':price' => $data['price'],
            ':is_active' => $data['is_active'],
            ':img_name' => $data['img_name'] ?? null,
        ]);
    }

    public function find(int $id): array|false
    {
        $sql = "SELECT * FROM products WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE products SET
                category_id = :category_id,
                name = :name,
                description = :description,
                stock = :stock,
                price = :price,
                is_active = :is_active,
                img_name = :img_name
            WHERE id = :id
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':stock' => $data['stock'],
            ':price' => $data['price'],
            ':is_active' => $data['is_active'],
            ':img_name' => $data['img_name'] ?? null,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM products WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }
}