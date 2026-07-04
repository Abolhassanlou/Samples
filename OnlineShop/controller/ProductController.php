<?php

require_once __DIR__ . '/../model/Product.php';
require_once __DIR__ . '/../requests/ProductRequest.php';

class ProductController
{
    public function index(array $filters = [], int $page = 1, int $perPage = 5): array
{
    $productModel = new Product();

    if (!empty($filters)) {
        return $productModel->filter($filters, $page, $perPage);
    }

    return $productModel->all($page, $perPage);
}
public function count(array $filters = []): int
{
    $productModel = new Product();

    if (!empty($filters)) {
        return $productModel->countFiltered($filters);
    }

    return $productModel->countAll();
}
    public function create(array $data): bool|array
    {
        $errors = ProductRequest::validate($data);

        if (!empty($errors)) {
            return $errors;
        }

        $productModel = new Product();

        return $productModel->create($data);
    }
    public function edit(int $id): array|false {
        $productModel = new Product();
        return $productModel->find($id);
    }
    public function update(int $id, array $data): bool|array {
        $errors = ProductRequest::validate($data);
        if(!empty($errors)) {
            return $errors;
        }
        $productModel = new Product();
        return $productModel->update($id, $data);
    }
    public function delete(int $id): bool{
        $productModel = new Product();
        return $productModel->delete($id);
    }

    public function show(int $id): array|false
    {
        $productModel = new Product();
        return $productModel->find($id);
    }
}