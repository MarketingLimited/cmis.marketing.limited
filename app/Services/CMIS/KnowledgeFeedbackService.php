<?php

namespace App\Services\CMIS;

use Illuminate\Support\Facades\DB;

class KnowledgeFeedbackService
{
    public function analyzeDaily(): void
    {
        $feedback = DB::select("
            SELECT category, COUNT(*) AS searches,
                   AVG(avg_similarity) AS quality
            FROM cmis_knowledge.semantic_search_logs
            WHERE created_at > now() - interval '1 day'
            GROUP BY category;
        ");

        foreach ($feedback as $row) {
            DB::table('cmis_knowledge.index')->insert([
                'domain' => 'system',
                'category' => 'feedback',
                'topic' => 'AutoFeedback_' . $row->category,
                'keywords' => json_encode(['auto_feedback', $row->category]),
                'tier' => 2,
                'embedding_model' => 'feedback:auto',
                'updated_at' => now(),
            ]);
        }
    }
}
