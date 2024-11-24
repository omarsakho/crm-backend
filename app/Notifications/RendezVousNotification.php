<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RendezVousNotification extends Notification
{
    use Queueable;

    protected $rendezvous;
    protected $action;

    public function __construct(RendezVous $rendezvous, $action)
    {
        $this->rendezvous = $rendezvous;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Mise à jour de votre rendez-vous : ' . ucfirst($this->action))
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous vous informons que votre rendez-vous a été ' . $this->action . '.')
            ->line('Voici les détails de votre rendez-vous :')
            ->line('**Date :** ' . \Carbon\Carbon::parse($this->rendezvous->date_rendezvous)->format('d/m/Y à H:i'))
            ->line('**Lieu :** ' . $this->rendezvous->lieu)
            ->line('**Agent responsable :** ' . $this->rendezvous->agent->nom . ' (Email : ' . $this->rendezvous->agent->email . ')')
            ->line('**Client :** ' . $this->rendezvous->client->nom . ' (Email : ' . $this->rendezvous->client->email . ')')
            ->action('Voir les détails du rendez-vous', url('/tickets/' . $this->rendezvous->ticket_id))
            ->line('Si vous avez des questions ou souhaitez modifier le rendez-vous, n\'hésitez pas à nous contacter.')
            ->line('Nous vous remercions pour votre confiance et restons à votre disposition.')
            ->salutation('Cordialement, L\'équipe de gestion des rendez-vous.');

        return $message;
    }

}
