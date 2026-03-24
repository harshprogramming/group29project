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

        return [
            'top_stressors' => $topStressors,
            'top_emotions' => $topEmotions,
            'best_activities' => $bestActivities,
            'recommendations' => $recommendations,
            'insight' => $this->buildInsight($topStressors, $bestActivities)
        ];
    }

    private function buildInsight(array $stressors, array $activities): string
    {
        $topStressor = $stressors[0]['factor_name'] ?? null;
        $bestActivity = $activities[0]['activity_name'] ?? null;

        if ($topStressor && $bestActivity) {
            return "Your most common stress trigger appears to be {$topStressor}. Based on your history, {$bestActivity} is linked to better mood outcomes.";
        }

        if ($topStressor) {
            return "Your most common stress trigger appears to be {$topStressor}.";
        }

        if ($bestActivity) {
            return "{$bestActivity} appears to be one of your strongest positive activities.";
        }

        return "Not enough data yet. Add more mood entries to generate stronger personal insights.";
    }
}