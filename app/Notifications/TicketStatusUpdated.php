<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusUpdated extends Notification
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new notification instance.
     *
     * @param \App\Models\Ticket $ticket
     * @return void
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];  // Utiliser le canal email
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Mise à jour du statut de votre ticket')
                    ->greeting('Bonjour ' . $notifiable->name . ',')
                    ->line('Le statut de votre ticket #' . $this->ticket->id . ' a été mis à jour.')
                    ->line('Statut actuel: ' . $this->ticket->statut)
                    ->action('Voir le ticket', url('/tickets/' . $this->ticket->id))
                    ->line('Merci de nous faire confiance !');
    }
}
