<?php

namespace App\Mail;

use App\Models\Core\Org;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Team Invitation Email
 *
 * Sent when a user is invited to join an organization.
 * Works for both existing users and new user invitations.
 */
class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $email,
        public Org $organization,
        public string $roleName,
        public string $inviterName,
        public string $invitationToken,
        public bool $isNewUser = true
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
        $acceptUrl = $this->isNewUser
            ? url('/register?invitation=' . $this->invitationToken)
            : url('/invitations/accept/' . $this->invitationToken);

        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'organizationName' => $this->organization->name,
                'organizationLogo' => $this->organization->logo_url,
                'roleName' => $this->roleName,
                'inviterName' => $this->inviterName,
                'acceptUrl' => $acceptUrl,
                'isNewUser' => $this->isNewUser,
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
