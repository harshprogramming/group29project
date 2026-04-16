<?php

class ReminderController
{
    public function __construct(private ReminderRepository $reminderRepository) {}

    public function store(): void
    {
        $data = Request::body();

        $errors = validate_required($data, ['reminder_time', 'reminder_message']);

        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $reminderId = $this->reminderRepository->createReminder(current_user_id(), [
            'reminder_time' => $data['reminder_time'],
            'reminder_message' => trim($data['reminder_message']),
        ]);

        Response::success(['reminder_id' => $reminderId], 'Reminder created', 201);
    }

    public function index(): void
    {
        $reminders = $this->reminderRepository->listReminders(current_user_id());
        Response::success(['reminders' => $reminders], 'Reminders fetched');
    }

    public function due(): void
    {
        $reminders = $this->reminderRepository->getDueReminders(current_user_id());
        Response::success(['reminders' => $reminders], 'Due reminders fetched');
    }

    public function destroy($id): void
    {
        $deleted = $this->reminderRepository->deleteReminder((int)$id, current_user_id());

        if (!$deleted) {
            Response::error('Reminder not found', 404);
        }

        Response::success([], 'Reminder deleted');
    }
}