<?php

require_once __DIR__ . '/../model/User.php';

class RegisterRequest
{
    private static array $errors = [];

    public static function validate(array $data): array
    {
        self::$errors = [];

        if (empty($data['first_name'])) {
            self::$errors['first_name'] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            self::$errors['last_name'] = 'Last name is required';
        }

        if (empty($data['email'])) {
            self::$errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            self::$errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            self::$errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            self::$errors['password'] = 'Password must be at least 8 characters';
        }

        if (empty(self::$errors)) {
            $userModel = new User();

            if ($userModel->getUserByEmail($data['email'])) {
                self::$errors['email'] = 'Email already exists';
            }
        }
        if (empty($data["confirm_password"])) {
            self::$errors["confirm_password"] = "Please confirm your password.";
        } elseif ($data["password"] !== $data["confirm_password"]) {
            self::$errors["confirm_password"] = "Passwords do not match.";
        }

        return self::$errors;
    }
}