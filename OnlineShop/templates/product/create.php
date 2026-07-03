<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/ProductController.php';
require_once '../../controller/CategoryController.php';

IsAdmin::check();

$errors = [];

$categoryController = new CategoryController();
$categories = $categoryController->index();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_POST['img_name'] = null;

    if (!empty($_FILES['image']['name'])) {

        $imageName = time() . '_' . $_FILES['image']['name'];
        $uploadPath = '../../assets/images/' . $imageName;

        move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);

        $_POST['img_name'] = $imageName;
    }

    $controller = new ProductController();
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

<h1>Create Product</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">

    <label>Category</label>
    <select name="category_id">
        <option value="">Select category</option>

        <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>">
                <?= htmlspecialchars($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <label>Name</label>
    <input
        type="text"
        name="name"
        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
    >

    <br><br>

    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <br><br>

    <label>Stock</label>
    <input
        type="number"
        name="stock"
        value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>"
    >

    <br><br>

    <label>Price</label>
    <input
        type="number"
        step="0.01"
        name="price"
        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
    >

    <br><br>

    <label>Status</label>
    <select name="is_active">
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>

    <br><br>

    <label>Image</label>
    <input type="file" name="image">

    <br><br>

    <button type="submit">Create Product</button>

</form>

<?php require_once '../layout/footer.php'; ?>