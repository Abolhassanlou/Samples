<?php
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../model/Category.php';

$categoryModel = new Category();
$categories = $categoryModel->all();
$cartCount =0;
if(isset($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
?>

<header class="shop-header">

    <div class="logo">
        <a href="<?=BASE_URL?>/templates/shop/index.php">
            OnlineShop
        </a>
    </div>

    <div class="search">
        <form>
            <input type="text" placeholder="Search products...">
        </form>
    </div>

    <div class="actions">

        <?php if (!isset($_SESSION['user'])): ?>

            <a href="<?=BASE_URL?>/templates/auth/login.php">
                Login
            </a>

        <?php elseif ((int)$_SESSION['user']['is_admin'] === 1): ?>

            <a href="<?=BASE_URL?>/templates/dashboard/dashboard.php" class="account-link">
                👤 <?= htmlspecialchars($_SESSION['user']['first_name']) ?>
            </a>

        <?php else: ?>

            <a href="<?=BASE_URL ?>/templates/account/dashboard.php" class="account-link">
                👤 <?= htmlspecialchars($_SESSION['user']['first_name']) ?>
            </a>

        <?php endif; ?>

        <a href="#">Wishlist</a>
        <a href="<?= BASE_URL?>/templates/shop/cart.php">
            Cart(<?= $cartCount ?>)</a>

    </div>

    <nav class="navigation">
        <ul>

            <?php foreach ($categories as $category): ?>

                <li>
                    <a href="<?=BASE_URL?>/templates/shop/products.php?category_id=<?= $category['id'] ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </a>
                </li>

            <?php endforeach; ?>

            <li>
                <a href="<?BASE_URL?>/templates/shop/products.php?sale=1">
                    Sale
                </a>
            </li>

        </ul>
    </nav>

</header>

<style>

.shop-header{
    display:grid;

    grid-template-areas:
        "logo search actions"
        "nav nav nav";

    grid-template-columns:260px minmax(300px,1fr) 320px;
    grid-template-rows:90px 55px;

    background:#fff;
    border-bottom:1px solid #ddd;
}

/* Logo */

.logo{
    grid-area:logo;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:34px;
    font-weight:bold;
}

.logo a{
    color:#222;
    text-decoration:none;
}

/* Search */

.search{
    grid-area:search;

    display:flex;
    align-items:center;

    padding:0 30px;
}

.search form{
    width:100%;
}

.search input{
    width:100%;
    height:48px;

    padding:0 18px;

    border:1px solid #ccc;
    border-radius:5px;

    font-size:17px;
}

/* Right menu */

.actions{
    grid-area:actions;

    display:flex;
    justify-content:center;
    align-items:center;

    gap:35px;
}

.actions a{
    text-decoration:none;
    color:#222;
    font-weight:600;
}

/* Logged user */

.account-link{
    color:#d6001c !important;
    font-weight:bold;
}

/* Navigation */

.navigation{
    grid-area:nav;

    border-top:1px solid #eee;
}

.navigation ul{
    display:flex;
    justify-content:center;
    align-items:center;

    gap:40px;

    list-style:none;

    margin:0;
    padding:0;

    height:55px;
}

.navigation a{
    text-decoration:none;
    color:#222;
    font-weight:600;
}

.navigation a:hover{
    color:#d6001c;
}

</style>