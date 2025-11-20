<?php

namespace App\Jobs\Email;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contacts;
    protected $emailData;

    public function __construct(Collection $contacts, array $emailData)
    {
        $this->contacts = $contacts;
        $this->emailData = $emailData;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        $sentCount = 0;

        // Send email to each contact
        foreach ($this->contacts as $contact) {
            // Stub implementation - would send actual email
            // For testing with Mail::fake(), just increment counter
            Mail::raw($this->emailData['content'] ?? 'Email content', function ($message) use ($contact) {
                $message->to($contact->email)
                    ->subject($this->emailData['subject'] ?? 'Email Subject');
            });

            $sentCount++;
        }

        $result['sent_count'] = $sentCount;
        $result['recipient_count'] = $this->contacts->count();

        return $result;
    }
}
