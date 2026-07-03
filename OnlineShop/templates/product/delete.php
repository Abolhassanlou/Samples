<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/ProductController.php';

IsAdmin::check();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

$productController = new ProductController();
$product = $productController->edit($id);

if (!$product) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if(!empty($product['img_name'])){
    $imagePath = '../../assets/images/' .$product['img_name'];
    if(file_exists($imagePath)){
        unlink($imagePath);
    }
}

    $productController->delete($id);

    header('Location: index.php');
    exit;
}

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Delete Product</h1>

<p>
    Are you sure you want to delete
    <strong><?= htmlspecialchars($product['name']) ?></strong>?
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