<?php 
require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/CategoryController.php';

IsAdmin::check();
$controller = new CategoryController();
$categories = $controller->index();

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Categories </h1>
<a href="create.php">Create New Category</a>
<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Created at</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($categories as $category): ?>
        <tr>
            <td><?= htmlspecialchars($category['id']) ?></td>
            <td><?= htmlspecialchars($category['name']) ?></td>
            <td><?= htmlspecialchars($category['created_at']) ?></td>
            <td>
                <a href="edit.php?id=<?= $category['id'] ?>">Edit</a>
                |
                <a href="delete.php?id=<?= $category['id'] ?>">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require_once '../layout/footer.php'; ?>