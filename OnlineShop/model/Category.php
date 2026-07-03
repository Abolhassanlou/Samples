<?php

require_once __DIR__ . '/../config/DB.php';

class Category extends DB
{
    /**
     * Get all categories
     */
    public function all(): array
    {
        $connection = $this->getConnection();

        $sql = "SELECT * FROM categories ORDER BY id DESC";

        $stmt = $connection->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new category
     */
    public function create(array $data): bool
    {
        $connection = $this->getConnection();

        $sql = "INSERT INTO categories (name)
                VALUES (:name)";

        $stmt = $connection->prepare($sql);

        $stmt->bindParam(':name', $data['name']);

        return $stmt->execute();
    }

    public function find(int $id): array|false
    {
        $connection = $this->getConnection();
        $sql = "SELECT * FROM categories WHERE id= :id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update(int $id, array $data): bool 
    {
        $connection = $this->getConnection();
        $sql = "UPDATE categories set name= :name WHERE id= :id";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':id' => $id
        ]);
    }
    public function delete(int $id): bool
    {
        $connection = $this->getConnection();
        $sql = "DELETE FROM categories where id= :id";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            ':id' =>$id
        ]);
    }
}