<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Organization $organization, private readonly string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invitation à rejoindre {$this->organization->name}")
            ->greeting('Bonjour,')
            ->line("Vous êtes invité à rejoindre {$this->organization->name} sur EduXora.")
            ->action('Accepter l’invitation', route('invitations.show', $this->token))
            ->line('Ce lien expire dans 72 heures et ne peut être utilisé qu’une fois.');
    }
}
