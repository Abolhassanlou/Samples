<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsUser.php';

IsUser::check('../auth/login.php', '../dashboard/dashboard.php');

require_once '../layout/header.php';
require_once 'layout/sidebar.php';
?>

<h1>My Account</h1>

<p>Welcome <?= htmlspecialchars($_SESSION['user']['first_name']) ?>.</p>

<p>This is your personal account dashboard.</p>

<a href="../shop/index.php">Go to Shop</a>

<?php require_once '../layout/footer.php'; ?>