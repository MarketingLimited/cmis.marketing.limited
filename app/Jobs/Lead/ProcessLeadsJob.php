<?php

namespace App\Jobs\Lead;

use App\Models\Lead\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lead;
    protected $options;

    public function __construct(?Lead $lead = null, array $options = [])
    {
        $this->lead = $lead;
        $this->options = $options;
    }

    public function handle(): array
    {
        $startTime = microtime(true);
        $result = [
            'success' => true,
            'processing_time' => 0,
        ];

        // Handle bulk processing
        if (isset($this->options['leads']) && $this->options['leads'] instanceof Collection) {
            $result['processed_count'] = $this->options['leads']->count();
            $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);
            return $result;
        }

        if (!$this->lead) {
            $result['success'] = false;
            return $result;
        }

        // Assign lead score
        if (!isset($this->options['score'])) {
            $result['score'] = $this->calculateLeadScore($this->lead);
        }

        // Email validation
        if (isset($this->options['validate_email']) && $this->options['validate_email']) {
            $result['email_valid'] = filter_var($this->lead->email, FILTER_VALIDATE_EMAIL) !== false;
        }

        // Duplicate detection
        if (isset($this->options['check_duplicates']) && $this->options['check_duplicates']) {
            $duplicates = Lead::where('org_id', $this->lead->org_id)
                ->where('email', $this->lead->email)
                ->where('lead_id', '!=', $this->lead->lead_id)
                ->count();
            $result['is_duplicate'] = $duplicates > 0;
        }

        // Categorize by source
        if ($this->lead->source) {
            $result['category'] = $this->categorizeBySource($this->lead->source);
        }

        // Update status if requested
        if (isset($this->options['update_status'])) {
            $this->lead->update(['status' => $this->options['update_status']]);
        }

        $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    protected function calculateLeadScore(Lead $lead): int
    {
        $score = 0;

        // Basic scoring logic
        if ($lead->email) $score += 20;
        if ($lead->phone) $score += 20;
        if ($lead->name) $score += 10;
        if ($lead->source) $score += 10;

        // Existing score
        if ($lead->score) {
            $score = max($score, $lead->score);
        }

        return min($score, 100);
    }

    protected function categorizeBySource(string $source): string
    {
        $socialSources = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok'];

        if (in_array(strtolower($source), $socialSources)) {
            return 'social_media';
        }

        return 'other';
    }
}
