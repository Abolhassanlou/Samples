<?php
require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/ProductController.php';
require_once '../../controller/CategoryController.php';

IsAdmin::check('../auth/login.php', '../dashboard/dashboard.php');

$errors = [];

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

$productController = new ProductController();
$product = $productController->edit($id);

if (!$product) {
    header('Location: index.php');
    exit;
}

$categoryController = new CategoryController();
$categories = $categoryController->index();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_POST['img_name'] = $product['img_name'] ?? null;

    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($product['img_name'])) {
            $oldImage = '../../assets/images/' . $product['img_name'];

            if (file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        $_POST['img_name'] = null;
    }

    if (!empty($_FILES['image']['name'])) {
        if (!empty($product['img_name'])) {
            $oldImage = '../../assets/images/' . $product['img_name'];

            if (file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        $imageName = time() . '_' . $_FILES['image']['name'];
        $uploadPath = '../../assets/images/' . $imageName;

        move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);

        $_POST['img_name'] = $imageName;
    }

    $result = $productController->update($id, $_POST);

    if ($result === true) {
        header('Location: index.php');
        exit;
    }

    $errors = $result;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Edit Product</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">

    <label>Category</label>
    <select name="category_id">
        <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>"
                <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>">

    <br><br>

    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>

    <br><br>

    <label>Stock</label>
    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>">

    <br><br>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>">

    <br><br>

    <label>Status</label>
    <select name="is_active">
        <option value="1" <?= (int)$product['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= (int)$product['is_active'] === 0 ? 'selected' : '' ?>>Inactive</option>
    </select>

    <br><br>

    <?php if (!empty($product['img_name'])): ?>
        <p>Current Image:</p>
        <img src="../../assets/images/<?= htmlspecialchars($product['img_name']) ?>" width="120">

        <br><br>

        <label>
            <input type="checkbox" name="remove_image" value="1">
            Remove current image
        </label>

        <br><br>
    <?php endif; ?>

    <label>New Image</label>
    <input type="file" name="image">

    <br><br>

    <button type="submit">Update Product</button>
</form>

<?php require_once '../layout/footer.php'; ?>