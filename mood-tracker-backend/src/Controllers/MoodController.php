<?php

class MoodController
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function index(): void
    {
        $userId = current_user_id();
        $from = Request::query('from');
        $to = Request::query('to');

        $entries = $this->moodRepository->listMoodEntries($userId, $from, $to);

        Response::success(['entries' => $entries], 'Mood entries fetched');
    }

    public function show($id): void
    {
        $entry = $this->moodRepository->findMoodEntry((int)$id, current_user_id());

        if (!$entry) {
            Response::error('Mood entry not found', 404);
        }

        Response::success(['entry' => $entry], 'Mood entry fetched');
    }

    public function store(): void
    {
        $data = Request::body();

        $errors = validate_required($data, ['date', 'time', 'stress_level', 'mood_level', 'emotions']);

        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $payload = [
            'date' => $data['date'],
            'time' => $data['time'],
            'stress_level' => (int)$data['stress_level'],
            'mood_level' => (int)$data['mood_level'],
            'emotions' => isset($data['emotions']) ? trim($data['emotions']) : null,
            'note' => isset($data['note']) ? trim($data['note']) : null,
        ];

        $entryId = $this->moodRepository->createMoodEntry(current_user_id(), $payload);
        $entry = $this->moodRepository->findMoodEntry($entryId, current_user_id());

        Response::success(['entry' => $entry], 'Mood entry created', 201);
    }

    public function update($id): void
    {
        $data = Request::body();

        $errors = validate_required($data, ['date', 'time', 'stress_level', 'mood_level', 'emotions']);

        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $payload = [
            'date' => $data['date'],
            'time' => $data['time'],
            'stress_level' => (int)$data['stress_level'],
            'mood_level' => (int)$data['mood_level'],
            'emotions' => isset($data['emotions']) ? trim($data['emotions']) : null,
            'note' => isset($data['note']) ? trim($data['note']) : null,
        ];

        $updated = $this->moodRepository->updateMoodEntry((int)$id, current_user_id(), $payload);

        if (!$updated) {
            Response::error('Mood entry not found or not updated', 404);
        }

        $entry = $this->moodRepository->findMoodEntry((int)$id, current_user_id());

        Response::success(['entry' => $entry], 'Mood entry updated');
    }

    public function destroy($id): void
    {
        $deleted = $this->moodRepository->deleteMoodEntry((int)$id, current_user_id());

        if (!$deleted) {
            Response::error('Mood entry not found', 404);
        }

        Response::success([], 'Mood entry deleted');
    }
}