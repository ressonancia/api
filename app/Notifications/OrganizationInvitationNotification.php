<?php

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Organization $organization,
        public User $inviter,
        public Invitation $invitation,
        public string $invitationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').'::Organization Invitation')
            ->greeting('You were invited to join an organization')
            ->line("{$this->inviter->name} invited you to join {$this->organization->name}.")
            ->line('This invitation link expires at '.$this->invitation->expires_at?->format('Y-m-d H:i:s').'.')
            ->action('Accept Invitation', $this->invitationUrl);
    }
}
