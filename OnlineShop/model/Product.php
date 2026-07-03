<?php

require_once __DIR__ . '/../config/DB.php';

class Product extends DB
{
    public function all(int $page = 1, int $perPage = 5): array
    {
        $connection = $this->getConnection();

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                products.*,
                categories.name AS category_name
            FROM products
            LEFT JOIN categories
                ON products.category_id = categories.id
            ORDER BY products.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        $connection = $this->getConnection();

        $sql = "SELECT COUNT(*) FROM products";

        $stmt = $connection->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function filter(array $filters, int $page = 1, int $perPage = 5): array
    {
        $connection = $this->getConnection();

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                products.*,
                categories.name AS category_name
            FROM products
            LEFT JOIN categories
                ON products.category_id = categories.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND products.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND products.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND products.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND products.name LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND products.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        $sort = $filters['sort'] ?? '';

        switch ($sort) {
            case 'oldest':
                $sql .= " ORDER BY products.id ASC";
                break;

            case 'price_low':
                $sql .= " ORDER BY products.price ASC";
                break;

            case 'price_high':
                $sql .= " ORDER BY products.price DESC";
                break;

            case 'name_az':
                $sql .= " ORDER BY products.name ASC";
                break;

            case 'newest':
            default:
                $sql .= " ORDER BY products.id DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $connection->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $connection = $this->getConnection();

        $sql = "
            INSERT INTO products
            (
                category_id,
                name,
                description,
                stock,
                price,
                is_active,
                img_name
            )
            VALUES
            (
                :category_id,
                :name,
                :description,
                :stock,
                :price,
                :is_active,
                :img_name
            )
        ";

        $stmt = $connection->prepare($sql);

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
        $connection = $this->getConnection();

        $sql = "SELECT * FROM products WHERE id = :id";

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $connection = $this->getConnection();

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

        $stmt = $connection->prepare($sql);

        return $stmt->execute([
            ':category_id' => $data['category_id'],
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':stock' => $data['stock'],
            ':price' => $data['price'],
            ':is_active' => $data['is_active'],
            ':img_name' => $data['img_name'] ?? null,
            ':id' => $id
        ]);
    }

    public function delete(int $id): bool
    {
        $connection = $this->getConnection();

        $sql = "DELETE FROM products WHERE id = :id";

        $stmt = $connection->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }
    public function countFiltered(array $filters): int
{
    $connection = $this->getConnection();

    $sql = "
        SELECT COUNT(*)
        FROM products
        LEFT JOIN categories
            ON products.category_id = categories.id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($filters['category_id'])) {
        $sql .= " AND products.category_id = :category_id";
        $params[':category_id'] = $filters['category_id'];
    }

    if (!empty($filters['min_price'])) {
        $sql .= " AND products.price >= :min_price";
        $params[':min_price'] = $filters['min_price'];
    }

    if (!empty($filters['max_price'])) {
        $sql .= " AND products.price <= :max_price";
        $params[':max_price'] = $filters['max_price'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND products.name LIKE :search";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $sql .= " AND products.is_active = :is_active";
        $params[':is_active'] = $filters['is_active'];
    }

    $stmt = $connection->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}
}