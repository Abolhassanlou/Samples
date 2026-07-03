<?php 
class IsAuth {
    public static function check(string $redirectTo = '../auth/login'): void
    {
        if(!isset($_SESSION['user'])) {
            header("Location: $redirectTo");
            exit;
        }
    }
}