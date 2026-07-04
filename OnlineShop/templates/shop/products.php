<?php

require_once '../../bootstrap.php';
require_once '../../controller/ProductController.php';


$controller = new ProductController();

$page = (int)($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

$perPage = 8;

$products = $controller->index($_GET, $page, $perPage);

$totalProducts = $controller->count($_GET);
$totalPages = ceil($totalProducts / $perPage);

require_once 'layout/header.php';
require_once 'layout/navbar.php';


?>

<h1>Shop Products</h1>

<?php foreach ($products as $product): ?>
    <div style="border:1px solid #ddd; padding:15px; margin-bottom:15px;">

        <?php if (!empty($product['img_name'])): ?>
            <img 
                src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($product['img_name']) ?>" 
                width="150"
            >
        <?php endif; ?>

        <h2><?= htmlspecialchars($product['name']) ?></h2>

        <p>Price: <?= htmlspecialchars($product['price']) ?></p>

        <p>Category: <?= htmlspecialchars($product['category_name'] ?? '-') ?></p>
        <p>Stock: <?= htmlspecialchars($product['stock'] >0 ? $product['stock'] : 'Out of stock') ?></p>

        <a href="product.php?id=<?= $product['id'] ?>">View Details</a>

    </div>
<?php endforeach; ?>

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