<?php
require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/CategoryController.php';



$errors = [];

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

$categoryController = new CategoryController();
$category = $categoryController->edit($id);

if (!$category) {
    header('Location: index.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $categoryController->update($id, $_POST);

    if ($result === true) {
        header('Location: index.php');
        exit;
    }

    $errors = $result;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Edit Category</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">

    <label>Category</label>
     <br><br>

    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>">

    <button type="submit">Update Product</button>
</form>

<?php require_once '../layout/footer.php'; ?>