<?php

class UserRepository
{
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT user_id, name, email, phone, age, gender, created_at
            FROM Users
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO Users (name, email, phone, age, gender, password)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['age'] ?? null,
            $data['gender'] ?? null,
            $data['password'],
        ]);

        return (int)$this->db->lastInsertId();
    }
}