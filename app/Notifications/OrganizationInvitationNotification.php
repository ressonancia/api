<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OrganizationInvitation $invitation
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = config('ressonance.spa_url')
            .'/organization-invite?invitation='.$this->invitation->id;

        return (new MailMessage)
            ->subject(config('app.name').'::Organization Invitation')
            ->line('You have been invited to join an organization.')
            ->line('Role: '.$this->invitation->role)
            ->action('Accept invitation', $acceptUrl);
    }
}
