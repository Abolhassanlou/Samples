<?php

require_once __DIR__ . '/../model/Category.php';
require_once __DIR__ . '/../requests/CategoryRequest.php';

class CategoryController
{
    public function index(): array
    {
        $categoryModel = new Category();

        return $categoryModel->all();
    }

    public function create(array $data): bool|array
    {
        $errors = CategoryRequest::validate($data);

        if (!empty($errors)) {
            return $errors;
        }

        $categoryModel = new Category();

        return $categoryModel->create($data);
    }
    public function edit(int $id): array|false
    {
        $categoryModel = new Category();
        return $categoryModel->find($id);
    }
    public function update(int $id, array $data):bool|array
    {
        $errors = CategoryRequest::validate($data);
        if(!empty($errors)) {
            return $errors;
        }
        $categoryModel = new Category();
        return $categoryModel->update($id, $data);
    } 
    public function delete(int $id): bool 
    {
        $categoryModel = new Category();
        return $categoryModel->delete($id);
    }
}