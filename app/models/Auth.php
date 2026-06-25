<?php

require_once __DIR__ . '/BaseModel.php';

class Auth extends BaseModel
{
    public function findByEmail($email)
    {
        $sql = "
            SELECT 
                auth.auth_id,
                auth.auth_email,
                auth.auth_password,
                users.user_id,
                users.first_name,
                users.last_name,
                users.phone,
                users.Birth_date,
                roles.role_name
            FROM auth
            JOIN users ON auth.auth_id = users.auth_id
            JOIN user_role ON users.user_id = user_role.user_id
            JOIN roles ON user_role.role_id = roles.role_id
            WHERE auth.auth_email = :email
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }

    public function emailExists($email)
    {
        $sql = "SELECT auth_id FROM auth WHERE auth_email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() !== false;
    }

    public function phoneExists($phone)
    {
        $sql = "SELECT user_id FROM users WHERE phone = :phone LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['phone' => $phone]);

        return $stmt->fetch() !== false;
    }

    public function register($firstName, $lastName, $phone, $birthDate, $email, $passwordHash)
    {
        try {
            $this->conn->beginTransaction();

            $sqlAuth = "
                INSERT INTO auth (auth_email, auth_password)
                VALUES (:email, :password_hash)
            ";

            $stmtAuth = $this->conn->prepare($sqlAuth);
            $stmtAuth->execute([
                'email' => $email,
                'password_hash' => $passwordHash
            ]);

            $authId = $this->conn->lastInsertId();

            $sqlUser = "
                INSERT INTO users (auth_id, first_name, last_name, phone, Birth_date)
                VALUES (:auth_id, :first_name, :last_name, :phone, :birth_date)
            ";

            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([
                'auth_id' => $authId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'birth_date' => $birthDate
            ]);

            $userId = $this->conn->lastInsertId();

            $sqlRole = "
                INSERT INTO user_role (user_id, role_id)
                SELECT :user_id, role_id
                FROM roles
                WHERE role_name = 'CUSTOMER'
                LIMIT 1
            ";

            $stmtRole = $this->conn->prepare($sqlRole);
            $stmtRole->execute(['user_id' => $userId]);

            $this->conn->commit();

            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}