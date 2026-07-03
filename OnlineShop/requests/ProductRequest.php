<?php

class ProductRequest
{
    private static array $errors = [];

    public static function validate(array $data): array
    {
        self::$errors = [];

        if (empty($data['category_id'])) {
            self::$errors['category_id'] = 'Category is required';
        }

        if (empty($data['name'])) {
            self::$errors['name'] = 'Product name is required';
        }

        if (empty($data['description'])) {
            self::$errors['description'] = 'Description is required';
        }

        if ($data['stock'] === '' || !isset($data['stock'])) {
            self::$errors['stock'] = 'Stock is required';
        } elseif (!is_numeric($data['stock'])) {
            self::$errors['stock'] = 'Stock must be a number';
        } elseif ($data['stock'] < 0) {
            self::$errors['stock'] = 'Stock cannot be negative';
        }

        if ($data['price'] === '' || !isset($data['price'])) {
            self::$errors['price'] = 'Price is required';
        } elseif (!is_numeric($data['price'])) {
            self::$errors['price'] = 'Price must be a number';
        } elseif ($data['price'] < 0) {
            self::$errors['price'] = 'Price cannot be negative';
        }

        if (!isset($data['is_active'])) {
            self::$errors['is_active'] = 'Product status is required';
        }

        return self::$errors;
    }
}