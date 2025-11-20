<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\Role;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $invitedUser,
        public Org $organization,
        public Role $role,
        public User $invitedBy,
        public string $invitationToken
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->organization->name} on CMIS",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-invitation',
            with: [
                'organizationName' => $this->organization->name,
                'roleName' => $this->role->role_name,
                'inviterName' => $this->invitedBy->display_name ?? $this->invitedBy->name,
                'inviterEmail' => $this->invitedBy->email,
                'acceptUrl' => url('/invitations/accept/' . $this->invitationToken),
                'expiresAt' => now()->addDays(7)->format('F j, Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
