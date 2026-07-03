<aside class="sidebar">

    <h2>Online Shop</h2>

    <div class="user-info">
        <strong>Welcome</strong><br>
        <?= htmlspecialchars($_SESSION['user']['first_name']) ?>
        <br>
        <?= htmlspecialchars($_SESSION['user']['email']) ?>
        <br>
        <small>
            <?= $_SESSION['user']['is_admin'] ? 'Administrator' : 'User' ?>
        </small>
    </div>

    <hr>

    <a href="../dashboard/dashboard.php">Dashboard</a>

    <?php if ($_SESSION['user']['is_admin']): ?>

        <a href="../category/index.php">Categories</a>

        <a href="../product/index.php">Products</a>

        <a href="../users/index.php">Users</a>

    <?php endif; ?>

    <hr>

    <a href="../auth/logout.php">Logout</a>

</aside>

<main class="content">