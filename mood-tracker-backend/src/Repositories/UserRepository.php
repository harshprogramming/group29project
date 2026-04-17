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

    public function findByEmailExcludingUser(string $email, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = ? AND user_id <> ?");
        $stmt->execute([$email, $userId]);
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

    public function update(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE Users
            SET name = ?, email = ?, phone = ?, age = ?, gender = ?, password = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['age'] ?? null,
            $data['gender'] ?? null,
            $data['password'],
            $userId
        ]);

        return true;
    }

    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);

        return $stmt->rowCount() > 0;
    }
}