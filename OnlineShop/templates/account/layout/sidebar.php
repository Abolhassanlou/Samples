<aside class="sidebar">

    <h2>My Account</h2>

    <div class="user-info">
        <strong><?= htmlspecialchars($_SESSION['user']['first_name']) ?></strong>
        <br>
        <?= htmlspecialchars($_SESSION['user']['email']) ?>
    </div>

    <hr>

    <a href="../account/dashboard.php">Dashboard</a>
    <a href="../account/profile.php">Profile</a>
    <a href="../account/orders.php">My Orders</a>
    <a href="../account/wishlist.php">Wishlist</a>

    <hr>

    <a href="../shop/index.php">Back to Shop</a>
    <a href="../auth/logout.php">Logout</a>

</aside>

<main class="content">