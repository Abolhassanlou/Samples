<?php

class CategoryRequest
{
    private static array $errors = [];

    public static function validate(array $data): array
    {
        self::$errors = [];

        if (empty($data['name'])) {
            self::$errors['name'] = 'Category name is required';
        }

        return self::$errors;
    }
}