<?php

require_once '../../bootstrap.php';
require_once '../../controller/AuthController.php';
require_once '../../middleware/IsGuest.php';

IsGuest::check();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $result = $controller->register($_POST);

    if ($result === true) {
        header('Location: login.php');
        exit;
    }

    $errors = $result;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <div class = "register-box">

    

    <h1>Register</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST">
        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

        <br>

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($_POST["last_name"] ?? '') ?>">

        <br>

        <label>Email</label>
        <input type="email" name="email" value = "<?= htmlspecialchars($_POST["email"] ?? '') ?>">

        <br>

        <label>Password</label>
        <input type="password" name="password">

        <br>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password">

        <br>

        <button type="submit">Register</button>
    </form>
</div>
</body>
<style>
    * {
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        margin: 0;
        min-height: 100vh;
        background: #f4f6f9;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-box {
        width: 400px;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    .error {
        color: red;
        font-size: 14px;
        margin-bottom: 8px;
    }

    label {
        display: block;
        margin-top: 15px;
        margin-bottom: 6px;
        font-weight: bold;
    }

    input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    button {
        width: 100%;
        margin-top: 25px;
        padding: 12px;
        background: #0d6efd;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
    }
</style>

</html>