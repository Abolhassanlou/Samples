<?php

require_once '../../bootstrap.php';
require_once '../../middleware/IsAdmin.php';
require_once '../../controller/UserController.php';

IsAdmin::check('../auth/login.php', '../dashboard/dashboard.php');

$controller = new UserController();
$users = $controller->index();

require_once '../layout/header.php';
require_once '../layout/sidebar.php';
?>

<h1>Users</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>First name</th>
        <th>Last name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created at</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['first_name']) ?></td>
            <td><?= htmlspecialchars($user['last_name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <?= (int)$user['is_admin'] === 1 ? 'Admin' : 'User' ?>
            </td>
            <td><?= htmlspecialchars($user['created_at']) ?></td>
            <td>
                <?php if((int)$user['id'] !== (int)$_SESSION['user']['id']): ?>
                    <a href="delete.php?id=<?= $user['id'] ?>">Delete</a>
                    <?php else: ?>
                        Current UserController
                    <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once '../layout/footer.php'; ?>