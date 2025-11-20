<?php

namespace App\Jobs\Communications;

use App\Models\Contact\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;
    protected $message;

    public function __construct(Contact $contact, string $message)
    {
        $this->contact = $contact;
        $this->message = $message;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        // Stub implementation - would call SMS provider API (Twilio, Unifonic, etc.)
        $result['recipient'] = $this->contact->phone;
        $result['message_length'] = mb_strlen($this->message);
        $result['sent_at'] = now()->toIso8601String();

        // Support for Arabic messages
        if (preg_match('/\p{Arabic}/u', $this->message)) {
            $result['encoding'] = 'unicode';
        } else {
            $result['encoding'] = 'gsm';
        }

        return $result;
    }
}
