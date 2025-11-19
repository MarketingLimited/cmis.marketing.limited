<?php

namespace App\Jobs\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;
    protected $recipient;

    public function __construct($notification, $recipient)
    {
        $this->notification = $notification;
        $this->recipient = $recipient;
    }

    public function handle()
    {
        // TODO: Implement notification sending logic
    }
}
