<?php

class AnalyzerController
{
    public function __construct(private AnalyzerService $analyzerService) {}

    public function analyze(): void
    {
        $result = $this->analyzerService->analyze(current_user_id());
        Response::success($result, 'Analysis generated');
    }
}