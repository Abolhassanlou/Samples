<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/CategoryController.php';



if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

$categoryController = new CategoryController();
$category = $categoryController->edit($id);

if (!$category) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoryController->delete($id);

    header('Location: index.php');
    exit;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Delete Category</h1>

<p>
    Are you sure you want to delete
    <strong><?= htmlspecialchars($category['name']) ?></strong>?
</p>

<form method="POST">

    <button type="submit">
        Yes, Delete
    </button>

    <a href="index.php">
        Cancel
    </a>

</form>

<?php require_once '../layout/footer.php'; ?>