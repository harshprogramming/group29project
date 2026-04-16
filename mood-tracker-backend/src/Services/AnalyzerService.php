<?php

class AnalyzerService
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function analyze(int $userId): array
    {
        $topStressors = $this->moodRepository->getTopStressors($userId);
        $topEmotions = $this->moodRepository->getTopEmotions($userId);
        $bestActivities = $this->moodRepository->getBestActivities($userId);
        $recommendations = $this->moodRepository->getRecommendationMatches($userId);
        $bestMoodImprovingActivities = $this->moodRepository->getBestMoodImprovingActivities($userId);
        $mostStressfulDays = $this->moodRepository->getMostStressfulDays($userId);

        return [
            'top_stressors' => $topStressors,
            'top_emotions' => $topEmotions,
            'best_activities' => $bestActivities,
            'recommendations' => $recommendations,
            'best_mood_improving_activities' => $bestMoodImprovingActivities,
            'most_stressful_days' => $mostStressfulDays,
            'insight' => $this->buildInsight($recommendations, $bestActivities)
        ];
    }

    private function buildInsight(array $recommendations, array $activities): string
    {
        $topRecommendation = $recommendations[0] ?? null;
        $bestActivity = $activities[0]['activity_name'] ?? null;

        if ($topRecommendation) {
            $activityName = $topRecommendation['activity_name'];
            $emotion = $topRecommendation['matched_on']['emotion'];
            $stressLevel = $topRecommendation['matched_on']['stress_level'];
            $moodLevel = $topRecommendation['matched_on']['mood_level'];

            return "Based on your latest mood entry ({$emotion}, stress {$stressLevel}, mood {$moodLevel}), {$activityName} is the strongest recommendation right now.";
        }

        if ($bestActivity) {
            return "{$bestActivity} appears frequently in your activity history and may continue to support your mood.";
        }

        return "Not enough data yet. Add more mood and activity entries to generate stronger recommendations.";
    }
}