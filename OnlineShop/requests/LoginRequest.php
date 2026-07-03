<?php

class LoginRequest
{
    private static array $errors = [];

    public static function validate(array $data): array
    {
        self::$errors = [];

        if (empty($data['email'])) {
            self::$errors['email'] = "Please write email";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            self::$errors['email'] = "Invalid email format";
        }

        if (empty($data['password'])) {
            self::$errors['password'] = "Please enter your password";
        }

        return self::$errors;
    }
}