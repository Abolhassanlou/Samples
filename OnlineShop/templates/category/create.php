<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/CategoryController.php';



$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CategoryController();
    $result = $controller->create($_POST);

    if ($result === true) {
        header('Location: index.php');
        exit;
    }

    $errors = $result;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Create Category</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
    <label>Category Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

    <button type="submit">Create</button>
</form>

<?php require_once '../layout/footer.php'; ?>