<?php

namespace App\Jobs;

use App\Services\CMIS\KnowledgeFeedbackService;

class KnowledgeAutoLearnJob extends Job
{
    public function handle(KnowledgeFeedbackService $service): void
    {
        $service->analyzeDaily();
    }
}
