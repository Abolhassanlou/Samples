<?php

class IsAdmin
{
    public static function check(
        string $guestRedirect = '../auth/login.php',
        string $userRedirect = '../account/dashboard.php'
    ): void {
        if (!isset($_SESSION['user'])) {
            header("Location: $guestRedirect");
            exit;
        }

        if ((int)$_SESSION['user']['is_admin'] !== 1) {
            header("Location: $userRedirect");
            exit;
        }
    }
}