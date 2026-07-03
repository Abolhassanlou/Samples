<?php

class IsUser
{
    public static function check(
        string $guestRedirect = '../auth/login.php',
        string $adminRedirect = '../dashboard/dashboard.php'
    ): void {
        if (!isset($_SESSION['user'])) {
            header("Location: $guestRedirect");
            exit;
        }

        if ((int)$_SESSION['user']['is_admin'] === 1) {
            header("Location: $adminRedirect");
            exit;
        }
    }
}