<?php
namespace App\Notifications;

use App\Models\Correspondence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrespondenciaRecibida extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Correspondence $correspondence)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('access.notifications.email', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'envelope',
            'title' => 'Correspondencia recibida',
            'message' => 'Tienes una nueva correspondencia ('.strtoupper($this->correspondence->package_type).')'.($this->correspondence->carrier ? ' de '.$this->correspondence->carrier : '').'.',
            'url' => route('resident.correspondence.show', $this->correspondence),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva correspondencia registrada')
            ->line('Tienes una nueva correspondencia esperándote en la portería.')
            ->action('Ver correspondencia', route('resident.correspondence.show', $this->correspondence));
    }
}