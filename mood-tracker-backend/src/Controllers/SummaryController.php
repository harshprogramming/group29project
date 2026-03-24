<?php

class SummaryController
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function summary(): void
    {
        $period = Request::query('period', 'week');

        if (!in_array($period, ['week', 'month'], true)) {
            Response::error('Period must be week or month', 422);
        }

        $summary = $this->moodRepository->getSummary(current_user_id(), $period);

        Response::success([
            'period' => $period,
            'summary' => $summary
        ], 'Summary fetched');
    }
}