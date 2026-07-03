

<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
IsAdmin::check();


require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Dashboard</h1>

<p>Welcome to your admin dashboard.</p>

<?php require_once '../layout/footer.php'; ?>