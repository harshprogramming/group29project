<?php

class ActivityController
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function store(): void
    {
        $data = Request::body();

        $errors = validate_required($data, ['date', 'time', 'activity_name']);

        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $durationMinutes = isset($data['duration_minutes']) && $data['duration_minutes'] !== ''
            ? (int)$data['duration_minutes']
            : null;

        $effortLevel = isset($data['effort_level']) && $data['effort_level'] !== ''
            ? (int)$data['effort_level']
            : null;

        if ($durationMinutes !== null && ($durationMinutes < 1 || $durationMinutes > 300)) {
            Response::error('Validation failed', 422, [
                'duration_minutes' => 'Duration must be between 1 and 300 minutes.'
            ]);
        }

        if ($effortLevel !== null && ($effortLevel < 1 || $effortLevel > 5)) {
            Response::error('Validation failed', 422, [
                'effort_level' => 'Effort level must be between 1 and 5.'
            ]);
        }

        $activityEntryId = $this->moodRepository->createActivityEntry(
            current_user_id(),
            [
                'date' => $data['date'],
                'time' => $data['time'],
                'activity_name' => trim($data['activity_name']),
                'category' => isset($data['category']) && trim((string)$data['category']) !== '' ? trim($data['category']) : null,
                'duration_minutes' => $durationMinutes,
                'effort_level' => $effortLevel,
                'note' => isset($data['note']) && trim((string)$data['note']) !== '' ? trim($data['note']) : null,
            ]
        );

        $activity = $this->moodRepository->findActivityEntry(
            $activityEntryId,
            current_user_id()
        );

        Response::success(
            ['activity' => $activity],
            'Activity saved successfully',
            201
        );
    }

    public function index(): void
    {
        $activities = $this->moodRepository->listActivityEntries(current_user_id());

        Response::success(
            ['activities' => $activities],
            'Activities fetched'
        );
    }

    public function update($id): void
    {
        $data = Request::body();

        $errors = validate_required($data, ['date', 'time', 'activity_name']);

        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $durationMinutes = isset($data['duration_minutes']) && $data['duration_minutes'] !== ''
            ? (int)$data['duration_minutes']
            : null;

        $effortLevel = isset($data['effort_level']) && $data['effort_level'] !== ''
            ? (int)$data['effort_level']
            : null;

        if ($durationMinutes !== null && ($durationMinutes < 1 || $durationMinutes > 300)) {
            Response::error('Validation failed', 422, [
                'duration_minutes' => 'Duration must be between 1 and 300 minutes.'
            ]);
        }

        if ($effortLevel !== null && ($effortLevel < 1 || $effortLevel > 5)) {
            Response::error('Validation failed', 422, [
                'effort_level' => 'Effort level must be between 1 and 5.'
            ]);
        }

        $updated = $this->moodRepository->updateActivityEntry(
            (int)$id,
            current_user_id(),
            [
                'date' => $data['date'],
                'time' => $data['time'],
                'activity_name' => trim($data['activity_name']),
                'category' => isset($data['category']) && trim((string)$data['category']) !== '' ? trim($data['category']) : null,
                'duration_minutes' => $durationMinutes,
                'effort_level' => $effortLevel,
                'note' => isset($data['note']) && trim((string)$data['note']) !== '' ? trim($data['note']) : null,
            ]
        );

        if (!$updated) {
            Response::error('Activity not found', 404);
        }

        $activity = $this->moodRepository->findActivityEntry((int)$id, current_user_id());

        Response::success(['activity' => $activity], 'Activity updated');
    }

    public function destroy($id): void
    {
        $deleted = $this->moodRepository->deleteActivityEntry(
            (int)$id,
            current_user_id()
        );

        if (!$deleted) {
            Response::error('Activity not found', 404);
        }

        Response::success([], 'Activity deleted');
    }

    public function linkedToMood(): void
    {
        $date = Request::query('date');

        if (!$date) {
            Response::error('Date is required', 422);
        }

        $results = $this->moodRepository->getActivitiesLinkedToMoodForDate(
            current_user_id(),
            $date
        );

        Response::success(['items' => $results], 'Activities linked to mood fetched');
    }

    public function mostActiveDayThisMonth(): void
    {
        $year = (int) Request::query('year');
        $month = (int) Request::query('month');

        if (!$year || !$month) {
            Response::error('Year and month are required', 422);
        }

        $result = $this->moodRepository->getMostActiveDayThisMonth(
            current_user_id(),
            $year,
            $month
        );

        Response::success(['item' => $result], 'Most active day this month fetched');
    }
}