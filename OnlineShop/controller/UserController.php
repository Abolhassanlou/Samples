<?php
require_once __DIR__ . '/../model/User.php';
class UserController
{
    public function index(): array 
    {
        $userModel = new User();
        return $userModel->all();
    }
    public function delete(int $id): void
{
    $userModel = new User();
    $userModel->delete($id);
}
}