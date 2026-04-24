<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TeamInvitation $invitation) {}

    public function envelope(): Envelope
    {
        $teamName = $this->invitation->team?->name ?? 'a team';

        return new Envelope(
            subject: __('You have been invited to join :team', ['team' => $teamName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.team-invitation',
            with: [
                'acceptUrl' => route('invitations.accept', ['token' => $this->invitation->token]),
                'teamName' => $this->invitation->team?->name ?? 'a team',
                'inviterName' => $this->invitation->invitedBy?->name ?? 'A team member',
                'role' => $this->invitation->role,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
