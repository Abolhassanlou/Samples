<?php 
require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/UserController.php';

IsAdmin::check('../auth/login.php', '../dashboard/dashboard.php');

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

if ($id === (int) $_SESSION['user']['id']) {
    header('Location: index.php');
    exit;
}

$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->delete($id);

    header('Location: index.php');
    exit;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Delete User</h1>

<p>Are you sure you want to delete this user?</p>

<form method="POST">
    <button type="submit">Yes, Delete</button>
    <a href="index.php">Cancel</a>
</form>

<?php require_once '../layout/footer.php'; ?>