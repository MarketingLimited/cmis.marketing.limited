<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $campaign;

    public function __construct($campaign)
    {
        $this->campaign = $campaign;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Campaign Completed')
            ->line('Your campaign has been completed.')
            ->action('View Campaign', url('/campaigns/' . $this->campaign->campaign_id));
    }

    public function toArray($notifiable)
    {
        return [
            'campaign_id' => $this->campaign->campaign_id,
            'title' => 'Campaign Completed',
            'message' => 'Your campaign has been completed successfully',
        ];
    }
}
