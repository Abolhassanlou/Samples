<?php 
require_once __DIR__ . '/../config/DB.php';

class User extends DB {

   public function getUserByEmail($email) {
        $connection = $this->getConnection();
        $sql="Select * from users where email= :email";
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(':email',$email);
        $stmt->execute();
        $user=$stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
   }

  public function create(array $data): bool
{
    $hashed_password = password_hash($data["password"], PASSWORD_DEFAULT);

    $connection = $this->getConnection();

    $sql = "INSERT INTO users (email, password, first_name, last_name)
            VALUES (:email, :password, :first_name, :last_name)";

    $stmt = $connection->prepare($sql);

    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':first_name', $data['first_name']);
    $stmt->bindParam(':last_name', $data['last_name']);

    return $stmt->execute();
}
public function all(): array 
{
    $connection = $this->getConnection();
    $sql = "
    SELECT 
    id, first_name, last_name,email, is_admin, created_at
    FROM users
    ORDER BY id DESC
    ";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll((PDO::FETCH_ASSOC));
}
public function delete(int $id): bool {
    $connection = $this->getConnection();
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $connection->prepare($sql);
    return $stmt->execute([':id' => $id]);
}

}