<?php

class IsGuest
{
    public static function check(
        string $adminRedirect = '../dashboard/dashboard.php',
        string $userRedirect = '../account/dashboard.php'
    ): void {
        if (!isset($_SESSION['user'])) {
            return;
        }

        if ((int)$_SESSION['user']['is_admin'] === 1) {
            header("Location: $adminRedirect");
            exit;
        }

        header("Location: $userRedirect");
        exit;
    }
}