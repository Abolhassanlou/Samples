<?php 
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../requests/RegisterRequest.php';
require_once __DIR__ . '/../requests/LoginRequest.php';

class AuthController {
    

public function register(array $data) {
    $errors = RegisterRequest::validate($data);
    if(!empty($errors)) {
        return $errors;
    }
    $userModel = new User();
   return $userModel->create($data);
}

public function login(array $data) {
    $errors = LoginRequest::validate($data);
    if(!empty($errors)) {
        return $errors;
    }
    $userModel = new User();
    $user = $userModel->getUserByEmail($data['email']);
    if(!$user){
        $errors['email'] = 'User doesnot exist';
        return $errors;
        
    }
    if (!password_verify($data['password'] , $user['password'])){
        $errors['password'] = 'Password is not correct';
        return $errors;
    }
    

        $_SESSION['user'] = $user;
        
        return true;
       
    }
}

?>