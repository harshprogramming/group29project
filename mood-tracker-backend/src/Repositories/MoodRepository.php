<?php

class MoodRepository
{
    public function __construct(private PDO $db) {}

    public function getReferenceOptions(): array
    {
        return [
            'emotions' => $this->fetchAll("SELECT type_id AS id, emotion_name AS name FROM Emotion_type ORDER BY emotion_name"),
            'stress_factors' => $this->fetchAll("SELECT factor_id AS id, factor_name AS name FROM Stress_factor ORDER BY factor_name"),
            'activities' => $this->fetchAll("SELECT activity_id AS id, activity_name AS name FROM Activities ORDER BY activity_name"),
        ];
    }

    public function createMoodEntry(int $userId, array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO MoodEntry (user_id, date, time, intensity, stress_level, mood_level, note)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $data['date'],
                $data['time'],
                $data['intensity'],
                $data['stress_level'],
                $data['mood_level'],
                $data['note'] ?? null,
            ]);

            $entryId = (int)$this->db->lastInsertId();

            $this->syncRelations('MoodEntry_Emotion', 'type_id', $entryId, $data['emotion_ids'] ?? []);
            $this->syncRelations('MoodEntry_Stress', 'factor_id', $entryId, $data['stress_factor_ids'] ?? []);
            $this->syncRelations('MoodEntry_Activity', 'activity_id', $entryId, $data['activity_ids'] ?? []);

            $this->db->commit();
            return $entryId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateMoodEntry(int $entryId, int $userId, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE MoodEntry
                SET date = ?, time = ?, intensity = ?, stress_level = ?, mood_level = ?, note = ?
                WHERE entry_id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['date'],
                $data['time'],
                $data['intensity'],
                $data['stress_level'],
                $data['mood_level'],
                $data['note'] ?? null,
                $entryId,
                $userId
            ]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            $this->deleteRelations($entryId);
            $this->syncRelations('MoodEntry_Emotion', 'type_id', $entryId, $data['emotion_ids'] ?? []);
            $this->syncRelations('MoodEntry_Stress', 'factor_id', $entryId, $data['stress_factor_ids'] ?? []);
            $this->syncRelations('MoodEntry_Activity', 'activity_id', $entryId, $data['activity_ids'] ?? []);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteMoodEntry(int $entryId, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM MoodEntry WHERE entry_id = ? AND user_id = ?");
        $stmt->execute([$entryId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function findMoodEntry(int $entryId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT entry_id, user_id, date, time, intensity, stress_level, mood_level, note, created_at
            FROM MoodEntry
            WHERE entry_id = ? AND user_id = ?
        ");
        $stmt->execute([$entryId, $userId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            return null;
        }

        $entry['emotions'] = $this->fetchRelatedNames(
            "SELECT et.type_id AS id, et.emotion_name AS name
             FROM MoodEntry_Emotion me
             JOIN Emotion_type et ON et.type_id = me.type_id
             WHERE me.entry_id = ?", $entryId
        );

        $entry['stress_factors'] = $this->fetchRelatedNames(
            "SELECT sf.factor_id AS id, sf.factor_name AS name
             FROM MoodEntry_Stress ms
             JOIN Stress_factor sf ON sf.factor_id = ms.factor_id
             WHERE ms.entry_id = ?", $entryId
        );

        $entry['activities'] = $this->fetchRelatedNames(
            "SELECT a.activity_id AS id, a.activity_name AS name
             FROM MoodEntry_Activity ma
             JOIN Activities a ON a.activity_id = ma.activity_id
             WHERE ma.entry_id = ?", $entryId
        );

        return $entry;
    }

    public function listMoodEntries(int $userId, ?string $from = null, ?string $to = null): array
    {
        $sql = "
            SELECT entry_id, date, time, intensity, stress_level, mood_level, note, created_at
            FROM MoodEntry
            WHERE user_id = ?
        ";

        $params = [$userId];

        if ($from) {
            $sql .= " AND date >= ?";
            $params[] = $from;
        }

        if ($to) {
            $sql .= " AND date <= ?";
            $params[] = $to;
        }

        $sql .= " ORDER BY date DESC, time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll();

        foreach ($entries as &$entry) {
            $entry['emotions'] = $this->fetchSimpleList(
                "SELECT et.emotion_name
                 FROM MoodEntry_Emotion me
                 JOIN Emotion_type et ON et.type_id = me.type_id
                 WHERE me.entry_id = ?",
                $entry['entry_id'],
                'emotion_name'
            );

            $entry['stress_factors'] = $this->fetchSimpleList(
                "SELECT sf.factor_name
                 FROM MoodEntry_Stress ms
                 JOIN Stress_factor sf ON sf.factor_id = ms.factor_id
                 WHERE ms.entry_id = ?",
                $entry['entry_id'],
                'factor_name'
            );

            $entry['activities'] = $this->fetchSimpleList(
                "SELECT a.activity_name
                 FROM MoodEntry_Activity ma
                 JOIN Activities a ON a.activity_id = ma.activity_id
                 WHERE ma.entry_id = ?",
                $entry['entry_id'],
                'activity_name'
            );
        }

        return $entries;
    }

    public function getSummary(int $userId, string $period = 'week'): array
    {
        $days = $period === 'month' ? 30 : 7;

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_entries,
                ROUND(AVG(mood_level), 2) AS avg_mood_level,
                ROUND(AVG(stress_level), 2) AS avg_stress_level,
                ROUND(AVG(intensity), 2) AS avg_intensity,
                MIN(date) AS first_entry_date,
                MAX(date) AS last_entry_date
            FROM MoodEntry
            WHERE user_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
        ");
        $stmt->execute([$userId]);
        $summary = $stmt->fetch();

        $topEmotionStmt = $this->db->prepare("
            SELECT et.emotion_name, COUNT(*) AS count
            FROM MoodEntry m
            JOIN MoodEntry_Emotion me ON me.entry_id = m.entry_id
            JOIN Emotion_type et ON et.type_id = me.type_id
            WHERE m.user_id = ?
              AND m.date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
            GROUP BY et.emotion_name
            ORDER BY count DESC
            LIMIT 3
        ");
        $topEmotionStmt->execute([$userId]);

        $summary['top_emotions'] = $topEmotionStmt->fetchAll();

        return $summary ?: [];
    }

    public function getTopStressors(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT sf.factor_name, COUNT(*) AS count, ROUND(AVG(m.stress_level), 2) AS avg_stress_level
            FROM MoodEntry m
            JOIN MoodEntry_Stress ms ON ms.entry_id = m.entry_id
            JOIN Stress_factor sf ON sf.factor_id = ms.factor_id
            WHERE m.user_id = ?
            GROUP BY sf.factor_name
            ORDER BY count DESC, avg_stress_level DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getTopEmotions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT et.emotion_name, COUNT(*) AS count
            FROM MoodEntry m
            JOIN MoodEntry_Emotion me ON me.entry_id = m.entry_id
            JOIN Emotion_type et ON et.type_id = me.type_id
            WHERE m.user_id = ?
            GROUP BY et.emotion_name
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getBestActivities(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.activity_name,
                   COUNT(*) AS times_used,
                   ROUND(AVG(m.mood_level), 2) AS avg_mood_level,
                   ROUND(AVG(m.stress_level), 2) AS avg_stress_level
            FROM MoodEntry m
            JOIN MoodEntry_Activity ma ON ma.entry_id = m.entry_id
            JOIN Activities a ON a.activity_id = ma.activity_id
            WHERE m.user_id = ?
            GROUP BY a.activity_name
            HAVING times_used >= 1
            ORDER BY avg_mood_level DESC, avg_stress_level ASC, times_used DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getRecommendationMatches(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                et.emotion_name,
                r.suggested_activity
            FROM MoodEntry m
            JOIN MoodEntry_Emotion me ON me.entry_id = m.entry_id
            JOIN Emotion_type et ON et.type_id = me.type_id
            JOIN Recommendation r ON r.target_emotion = et.emotion_name
            WHERE m.user_id = ?
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    private function syncRelations(string $table, string $column, int $entryId, array $ids): void
    {
        $ids = sanitize_int_array($ids);

        if (empty($ids)) {
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO {$table} (entry_id, {$column}) VALUES (?, ?)");

        foreach ($ids as $id) {
            $stmt->execute([$entryId, $id]);
        }
    }

    private function deleteRelations(int $entryId): void
    {
        $this->db->prepare("DELETE FROM MoodEntry_Emotion WHERE entry_id = ?")->execute([$entryId]);
        $this->db->prepare("DELETE FROM MoodEntry_Stress WHERE entry_id = ?")->execute([$entryId]);
        $this->db->prepare("DELETE FROM MoodEntry_Activity WHERE entry_id = ?")->execute([$entryId]);
    }

    private function fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll();
    }

    private function fetchRelatedNames(string $sql, int $entryId): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entryId]);
        return $stmt->fetchAll();
    }

    private function fetchSimpleList(string $sql, int $entryId, string $column): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entryId]);
        return array_column($stmt->fetchAll(), $column);
    }
}