<?php

class ReferenceController
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function options(): void
    {
        $options = $this->moodRepository->getReferenceOptions();
        Response::success($options, 'Reference data fetched');
    }
}