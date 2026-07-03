<?php 

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/ProductController.php';
require_once '../../controller/CategoryController.php';

IsAdmin::check();      

$controller = new ProductController();
$page = (int)($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

$perPage = 5;

$products = $controller->index($_GET, $page, $perPage);

$categoryController = new CategoryController();
$categories = $categoryController->index();
$totalProducts = $controller->count($_GET);
$totalPages = ceil($totalProducts / $perPage);

require_once '../layout/header.php';
require_once '../layout/sidebar.php';

?>

<h1>Products</h1>
 

<a href="create.php">Create New Product</a>
<form method ="GET">
    <input type="text" name="search" placeholder="Search product..."
    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <input type="number" name="min_price" placeholder="Min Price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
    >
    <input type="number" name="max_price" placeholder="Max price"
    value="<?= htmlspecialchars($_GET['max_price'] ?? '')?>">
    <select name="is_active">
        <option value="">All status</option>
        <option value="1" <?=($_GET['is_active'] ?? '') ==='1' ? 'selected': '' ?>>Active</option>
        <option value="0" <?=($_GET['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <select name="category_id">
        <option value="">All categories</option>

        <?php foreach($categories as $category):?>
        <option 
            value="<?= $category['id']?>"
            <?=($_GET['category_id'] ?? '') == $category['id'] ? 'selected': '' ?>>
        <?= htmlspecialchars($category['name']) ?>
    <?php endforeach?>
    </select>
    <select name="sort">
    <option value="">Default sort</option>
    <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
    <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest</option>
    <option value="price_low" <?= ($_GET['sort'] ?? '') === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
    <option value="price_high" <?= ($_GET['sort'] ?? '') === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
    <option value="name_az" <?= ($_GET['sort'] ?? '') === 'name_az' ? 'selected' : '' ?>>Name A-Z</option>
</select>
    <button type="submit" >Search</button>
    <a href="index.php">Reset</a>
</form>
<br>
<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Name</th>
        <th>Price</th>
        <th>Category</th>
        <th>Description</th>
        <th>Created at</th>
        <th>Actions</th>
        
    </tr>
 

    <?php foreach ($products as $product): ?>
 
        <tr>
            <td><?= htmlspecialchars($product['id']) ?></td>
            <td>
                <?php if(!empty($product['img_name'])) : ?>
                    <img
                    src="../../assets/images/<?= htmlspecialchars($product['img_name']) ?>"
                    width ="80"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    >
                <?php else: ?>
                    No Image
                <?php endif;?>
            </td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['price']) ?></td>
            <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($product['description'] ) ?></td>
            
            <td><?= htmlspecialchars($product['created_at']) ?></td>
            <td>
                <a href="edit.php?id=<?= $product['id'] ?>">Edit</a>
                |
                <a href="delete.php?id=<?= $product['id'] ?>">Delete</a>
            </td>
            
        </tr>
    <?php endforeach; ?>
</table>
<br>
<?php
$queryParams = $_GET;
?>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php
        $queryParams['page'] = $i;
        $link = '?' . http_build_query($queryParams);
    ?>

    <a 
        href="<?= htmlspecialchars($link) ?>"
        style="<?= $i === $page ? 'font-weight:bold;' : '' ?>"
    >
        <?= $i ?>
    </a>
<?php endfor; ?>

<?php require_once '../layout/footer.php'; ?>