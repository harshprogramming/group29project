<?php

class ReminderRepository
{
    public function __construct(private PDO $db) {}

    public function createReminder(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO Reminder (user_id, reminder_time, reminder_message)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['reminder_time'],
            $data['reminder_message']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getDueReminders(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT reminder_id, user_id, reminder_time, reminder_message, created_at
            FROM Reminder
            WHERE user_id = ?
              AND reminder_time <= NOW()
            ORDER BY reminder_time ASC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function deleteReminder(int $reminderId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM Reminder
            WHERE reminder_id = ? AND user_id = ?
        ");
        $stmt->execute([$reminderId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function listReminders(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT reminder_id, user_id, reminder_time, reminder_message, created_at
            FROM Reminder
            WHERE user_id = ?
            ORDER BY reminder_time ASC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}