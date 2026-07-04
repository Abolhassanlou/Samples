<?php

require_once '../../bootstrap.php';
require_once '../../controller/ProductController.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$id = (int)$_GET['id'];

$controller = new ProductController();
$product = $controller->show($id);

if (!$product) {
    header('Location: products.php');
    exit;
}

require_once 'layout/header.php';
require_once 'layout/navbar.php';
?>

<section class="product-container">

    <div class="product-image">

        <?php if (!empty($product['img_name'])): ?>
            <img
                src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($product['img_name']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>">
        <?php endif; ?>

    </div>

    <div class="product-info">

        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <p class="category">
            Category:
            <strong><?= htmlspecialchars($product['category_name']) ?></strong>
        </p>

        <p class="price">
            €<?= number_format($product['price'],2) ?>
        </p>

        <p class="stock">
            Stock:
            <?= (int)$product['stock'] > 0 ? (int)$product['stock'] : 'Out Of Stock' ?>
        </p>

        <div class="description">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>

        <form action="cart-add.php" method="POST">

            <input
                type="hidden"
                name="product_id"
                value="<?= $product['id'] ?>">

            <label>Quantity</label>

            <input
                type="number"
                name="quantity"
                value="1"
                min="1"
                max="<?= (int)$product['stock'] ?>">

            <br><br>

            <button type="submit">
                Add to Cart
            </button>

        </form>

    </div>

</section>

<?php require_once 'layout/footer.php'; ?>

<style>

.product-container{

    max-width:1200px;

    margin:50px auto;

    display:grid;

    grid-template-columns:500px 1fr;

    gap:60px;

}

.product-image img{

    width:100%;

    border-radius:12px;

    object-fit:cover;

}

.product-info h1{

    margin-top:0;

    font-size:36px;

}

.category{

    margin-top:20px;

    color:#666;

}

.price{

    margin:25px 0;

    font-size:34px;

    color:#d6001c;

    font-weight:bold;

}

.stock{

    font-weight:bold;

}

.description{

    margin:30px 0;

    line-height:1.8;

}

input[type=number]{

    width:90px;

    padding:10px;

}

button{

    background:#d6001c;

    color:white;

    border:none;

    padding:14px 30px;

    border-radius:6px;

    cursor:pointer;

    font-size:17px;

}

button:hover{

    background:#b50016;

}

</style>