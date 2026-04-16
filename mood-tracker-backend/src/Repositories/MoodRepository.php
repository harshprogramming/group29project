<?php

class MoodRepository
{
    public function __construct(private PDO $db) {}

    public function getReferenceOptions(): array
    {
        return [
            'emotions' => [
                'Happy',
                'Calm',
                'Motivated',
                'Neutral',
                'Sad',
                'Angry',
                'Stressed',
            ],
            'activities' => [
                'Walking',
                'Jogging',
                'Meditation',
                'Yoga',
                'Reading',
                'Listening to Music',
                'Journaling',
                'Stretching',
                'Deep Breathing',
                'Talking to a Friend',
            ],
        ];
    }

    public function createMoodEntry(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO MoodEntry (
                user_id, date, time, stress_level, mood_level, emotions, note
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['date'],
            $data['time'],
            $data['stress_level'],
            $data['mood_level'],
            $data['emotions'] ?? null,
            $data['note'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateMoodEntry(int $entryId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE MoodEntry
            SET
                date = ?,
                time = ?,
                stress_level = ?,
                mood_level = ?,
                emotions = ?,
                note = ?
            WHERE entry_id = ? AND user_id = ?
        ");

        $stmt->execute([
            $data['date'],
            $data['time'],
            $data['stress_level'],
            $data['mood_level'],
            $data['emotions'] ?? null,
            $data['note'] ?? null,
            $entryId,
            $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteMoodEntry(int $entryId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM MoodEntry
            WHERE entry_id = ? AND user_id = ?
        ");
        $stmt->execute([$entryId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function findMoodEntry(int $entryId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                entry_id,
                user_id,
                date,
                time,
                stress_level,
                mood_level,
                emotions,
                note,
                created_at
            FROM MoodEntry
            WHERE entry_id = ? AND user_id = ?
        ");
        $stmt->execute([$entryId, $userId]);

        $entry = $stmt->fetch();

        return $entry ?: null;
    }

    public function listMoodEntries(int $userId, ?string $from = null, ?string $to = null): array
    {
        $sql = "
            SELECT
                entry_id,
                user_id,
                date,
                time,
                stress_level,
                mood_level,
                emotions,
                note,
                created_at
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

        return $stmt->fetchAll();
    }

    public function getLatestMoodEntry(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                entry_id,
                user_id,
                date,
                time,
                stress_level,
                mood_level,
                emotions,
                note,
                created_at
            FROM MoodEntry
            WHERE user_id = ?
              AND TIMESTAMP(date, time) <= NOW()
            ORDER BY date DESC, time DESC, entry_id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        $entry = $stmt->fetch();

        return $entry ?: null;
    }

    public function getSummary(int $userId, string $period = 'week'): array
    {
        $days = $period === 'month' ? 30 : 7;

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_entries,
                ROUND(AVG(mood_level), 2) AS avg_mood_level,
                ROUND(AVG(stress_level), 2) AS avg_stress_level,
                MIN(date) AS first_entry_date,
                MAX(date) AS last_entry_date
            FROM MoodEntry
            WHERE user_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
        ");
        $stmt->execute([$userId]);

        $summary = $stmt->fetch();

        if (!$summary) {
            return [];
        }

        $topEmotionStmt = $this->db->prepare("
            SELECT emotions, COUNT(*) AS count
            FROM MoodEntry
            WHERE user_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
              AND emotions IS NOT NULL
              AND emotions <> ''
            GROUP BY emotions
            ORDER BY count DESC, emotions ASC
            LIMIT 3
        ");
        $topEmotionStmt->execute([$userId]);

        $summary['top_emotions'] = $topEmotionStmt->fetchAll();

        return $summary;
    }

    public function getTopEmotions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT emotions AS emotion_name, COUNT(*) AS count
            FROM MoodEntry
            WHERE user_id = ?
              AND emotions IS NOT NULL
              AND emotions <> ''
            GROUP BY emotions
            ORDER BY count DESC, emotion_name ASC
            LIMIT 5
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getTopStressors(int $userId): array
    {
        return [];
    }

    public function getBestActivities(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT activity_name, COUNT(*) AS times_used
            FROM activityentry
            WHERE user_id = ?
            GROUP BY activity_name
            ORDER BY times_used DESC, activity_name ASC
            LIMIT 5
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    private function mapRecommendationRows(array $rows, array $latestEntry): array
    {
        return array_map(function ($row) use ($latestEntry) {
            return [
                'recommendation_rule_id' => (int) $row['recommendation_rule_id'],
                'emotion_name' => $row['emotion_name'],
                'activity_name' => $row['activity_name'],
                'recommendation_text' => $row['recommendation_text'],
                'priority_score' => (int) $row['priority_score'],
                'historical_match_count' => (int) $row['historical_match_count'],
                'avg_mood_after_activity' => (float) $row['avg_mood_after_activity'],
                'avg_stress_after_activity' => (float) $row['avg_stress_after_activity'],
                'effectiveness_score' => (float) $row['effectiveness_score'],
                'matched_on' => [
                    'entry_id' => (int) $latestEntry['entry_id'],
                    'date' => $latestEntry['date'],
                    'time' => $latestEntry['time'],
                    'emotion' => $latestEntry['emotions'],
                    'stress_level' => (int) $latestEntry['stress_level'],
                    'mood_level' => (int) $latestEntry['mood_level']
                ]
            ];
        }, $rows);
    }

    private function getHistoricalActivityStatsSubquery(): string
    {
        return "
            SELECT
                a.activity_name,
                COUNT(*) AS match_count,
                ROUND(AVG(m.mood_level), 2) AS avg_mood_after_activity,
                ROUND(AVG(m.stress_level), 2) AS avg_stress_after_activity
            FROM activityentry a
            JOIN MoodEntry m
                ON a.user_id = m.user_id
               AND a.date = m.date
            WHERE a.user_id = ?
            GROUP BY a.activity_name
        ";
    }

    public function getRecommendationMatches(int $userId): array
    {
        $latestEntry = $this->getLatestMoodEntry($userId);

        if (!$latestEntry || empty($latestEntry['emotions'])) {
            return [];
        }

        $emotion = trim((string) $latestEntry['emotions']);
        $emotionNormalized = mb_strtolower($emotion);
        $stressLevel = (int) $latestEntry['stress_level'];
        $moodLevel = (int) $latestEntry['mood_level'];

        $historicalStatsSql = $this->getHistoricalActivityStatsSubquery();

        // 1) Exact emotion + exact range match
        $stmt = $this->db->prepare("
            SELECT
                rr.recommendation_rule_id,
                rr.emotion_name,
                rr.min_stress_level,
                rr.max_stress_level,
                rr.min_mood_level,
                rr.max_mood_level,
                rr.activity_name,
                rr.recommendation_text,
                rr.priority_score,
                COALESCE(hist.match_count, 0) AS historical_match_count,
                COALESCE(hist.avg_mood_after_activity, 0) AS avg_mood_after_activity,
                COALESCE(hist.avg_stress_after_activity, 0) AS avg_stress_after_activity,
                ROUND(
                    COALESCE(hist.avg_mood_after_activity, 0) -
                    COALESCE(hist.avg_stress_after_activity, 0),
                    2
                ) AS effectiveness_score
            FROM RecommendationRule rr
            LEFT JOIN (
                {$historicalStatsSql}
            ) hist
                ON hist.activity_name = rr.activity_name
            WHERE rr.is_active = 1
              AND TRIM(LOWER(rr.emotion_name)) = ?
              AND ? BETWEEN rr.min_stress_level AND rr.max_stress_level
              AND ? BETWEEN rr.min_mood_level AND rr.max_mood_level
            ORDER BY
                rr.priority_score DESC,
                effectiveness_score DESC,
                historical_match_count DESC,
                rr.activity_name ASC
            LIMIT 3
        ");
        $stmt->execute([
            $userId,
            $emotionNormalized,
            $stressLevel,
            $moodLevel
        ]);

        $rows = $stmt->fetchAll();
        if ($rows) {
            return $this->mapRecommendationRows($rows, $latestEntry);
        }

        // 2) Same emotion only, closest ranges first
        $stmt = $this->db->prepare("
            SELECT
                rr.recommendation_rule_id,
                rr.emotion_name,
                rr.min_stress_level,
                rr.max_stress_level,
                rr.min_mood_level,
                rr.max_mood_level,
                rr.activity_name,
                rr.recommendation_text,
                rr.priority_score,
                COALESCE(hist.match_count, 0) AS historical_match_count,
                COALESCE(hist.avg_mood_after_activity, 0) AS avg_mood_after_activity,
                COALESCE(hist.avg_stress_after_activity, 0) AS avg_stress_after_activity,
                ROUND(
                    COALESCE(hist.avg_mood_after_activity, 0) -
                    COALESCE(hist.avg_stress_after_activity, 0),
                    2
                ) AS effectiveness_score,
                (
                    CASE
                        WHEN ? BETWEEN rr.min_stress_level AND rr.max_stress_level THEN 0
                        ELSE LEAST(ABS(? - rr.min_stress_level), ABS(? - rr.max_stress_level))
                    END
                    +
                    CASE
                        WHEN ? BETWEEN rr.min_mood_level AND rr.max_mood_level THEN 0
                        ELSE LEAST(ABS(? - rr.min_mood_level), ABS(? - rr.max_mood_level))
                    END
                ) AS distance_score
            FROM RecommendationRule rr
            LEFT JOIN (
                {$historicalStatsSql}
            ) hist
                ON hist.activity_name = rr.activity_name
            WHERE rr.is_active = 1
              AND TRIM(LOWER(rr.emotion_name)) = ?
            ORDER BY
                distance_score ASC,
                rr.priority_score DESC,
                effectiveness_score DESC,
                historical_match_count DESC,
                rr.activity_name ASC
            LIMIT 3
        ");
        $stmt->execute([
            $stressLevel,
            $stressLevel,
            $stressLevel,
            $moodLevel,
            $moodLevel,
            $moodLevel,
            $userId,
            $emotionNormalized
        ]);

        $rows = $stmt->fetchAll();
        if ($rows) {
            return $this->mapRecommendationRows($rows, $latestEntry);
        }

        // 3) Final fallback: closest active rules overall
        $stmt = $this->db->prepare("
            SELECT
                rr.recommendation_rule_id,
                rr.emotion_name,
                rr.min_stress_level,
                rr.max_stress_level,
                rr.min_mood_level,
                rr.max_mood_level,
                rr.activity_name,
                rr.recommendation_text,
                rr.priority_score,
                COALESCE(hist.match_count, 0) AS historical_match_count,
                COALESCE(hist.avg_mood_after_activity, 0) AS avg_mood_after_activity,
                COALESCE(hist.avg_stress_after_activity, 0) AS avg_stress_after_activity,
                ROUND(
                    COALESCE(hist.avg_mood_after_activity, 0) -
                    COALESCE(hist.avg_stress_after_activity, 0),
                    2
                ) AS effectiveness_score,
                (
                    CASE
                        WHEN TRIM(LOWER(rr.emotion_name)) = ? THEN 0 ELSE 2
                    END
                    +
                    CASE
                        WHEN ? BETWEEN rr.min_stress_level AND rr.max_stress_level THEN 0
                        ELSE LEAST(ABS(? - rr.min_stress_level), ABS(? - rr.max_stress_level))
                    END
                    +
                    CASE
                        WHEN ? BETWEEN rr.min_mood_level AND rr.max_mood_level THEN 0
                        ELSE LEAST(ABS(? - rr.min_mood_level), ABS(? - rr.max_mood_level))
                    END
                ) AS distance_score
            FROM RecommendationRule rr
            LEFT JOIN (
                {$historicalStatsSql}
            ) hist
                ON hist.activity_name = rr.activity_name
            WHERE rr.is_active = 1
            ORDER BY
                distance_score ASC,
                rr.priority_score DESC,
                effectiveness_score DESC,
                historical_match_count DESC,
                rr.activity_name ASC
            LIMIT 3
        ");
        $stmt->execute([
            $emotionNormalized,
            $stressLevel,
            $stressLevel,
            $stressLevel,
            $moodLevel,
            $moodLevel,
            $moodLevel,
            $userId
        ]);

        $rows = $stmt->fetchAll();
        return $rows ? $this->mapRecommendationRows($rows, $latestEntry) : [];
    }

    public function createActivityEntry(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO activityentry (
                user_id,
                activity_name,
                category,
                note,
                date,
                time,
                duration_minutes,
                effort_level
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['activity_name'],
            $data['category'] ?? null,
            $data['note'] ?? null,
            $data['date'],
            $data['time'],
            $data['duration_minutes'] ?? null,
            $data['effort_level'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findActivityEntry(int $activityEntryId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                activity_entry_id,
                user_id,
                activity_name,
                category,
                note,
                date,
                time,
                duration_minutes,
                effort_level,
                created_at
            FROM activityentry
            WHERE activity_entry_id = ? AND user_id = ?
        ");
        $stmt->execute([$activityEntryId, $userId]);

        $activity = $stmt->fetch();

        return $activity ?: null;
    }

    public function listActivityEntries(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                activity_entry_id,
                user_id,
                activity_name,
                category,
                note,
                date,
                time,
                duration_minutes,
                effort_level,
                created_at
            FROM activityentry
            WHERE user_id = ?
            ORDER BY date DESC, time DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function updateActivityEntry(int $activityEntryId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE activityentry
            SET
                activity_name = ?,
                category = ?,
                note = ?,
                date = ?,
                time = ?,
                duration_minutes = ?,
                effort_level = ?
            WHERE activity_entry_id = ? AND user_id = ?
        ");

        $stmt->execute([
            $data['activity_name'],
            $data['category'] ?? null,
            $data['note'] ?? null,
            $data['date'],
            $data['time'],
            $data['duration_minutes'] ?? null,
            $data['effort_level'] ?? null,
            $activityEntryId,
            $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteActivityEntry(int $activityEntryId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM activityentry
            WHERE activity_entry_id = ? AND user_id = ?
        ");

        $stmt->execute([$activityEntryId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function getBestMoodImprovingActivities(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                a.activity_name,
                ROUND(AVG(m.mood_level), 2) AS avg_mood_after_activity,
                COUNT(*) AS match_count
            FROM activityentry a
            JOIN MoodEntry m
                ON a.user_id = m.user_id
               AND a.date = m.date
            WHERE a.user_id = ?
              AND YEARWEEK(a.date, 1) = YEARWEEK(CURDATE(), 1)
              AND YEARWEEK(m.date, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY a.activity_name
            HAVING COUNT(*) > 0
            ORDER BY avg_mood_after_activity DESC, match_count DESC, a.activity_name ASC
            LIMIT 3
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getMostStressfulDays(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DAYNAME(date) AS weekday,
                ROUND(AVG(stress_level), 2) AS avg_stress,
                COUNT(*) AS entry_count,
                WEEKDAY(date) AS weekday_order
            FROM MoodEntry
            WHERE user_id = ?
              AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY DAYNAME(date), WEEKDAY(date)
            ORDER BY avg_stress DESC, entry_count DESC, weekday_order ASC
            LIMIT 3
        ");
        $stmt->execute([$userId]);

        return array_map(function ($row) {
            unset($row['weekday_order']);
            return $row;
        }, $stmt->fetchAll());
    }

    public function getActivitiesLinkedToMoodForDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT
                a.activity_entry_id,
                a.activity_name,
                a.category,
                a.note,
                a.duration_minutes,
                a.effort_level,
                a.time AS activity_time,
                m.entry_id,
                m.time AS mood_time,
                m.emotions,
                m.mood_level,
                m.stress_level
            FROM activityentry a
            JOIN MoodEntry m
                ON a.user_id = m.user_id
               AND a.date = m.date
            WHERE a.user_id = ?
              AND a.date = ?
            ORDER BY a.time ASC, m.time ASC
        ");
        $stmt->execute([$userId, $date]);

        return $stmt->fetchAll();
    }

    public function getMostActiveDayThisMonth(int $userId, int $year, int $month): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                date,
                COUNT(*) AS activity_count
            FROM activityentry
            WHERE user_id = ?
              AND YEAR(date) = ?
              AND MONTH(date) = ?
            GROUP BY date
            ORDER BY activity_count DESC, date ASC
            LIMIT 1
        ");
        $stmt->execute([$userId, $year, $month]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}