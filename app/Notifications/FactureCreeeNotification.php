<?php

namespace App\Notifications;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FactureCreeeNotification extends Notification
{
    use Queueable;

    protected $facture;

    /**
     * Créer une nouvelle notification.
     *
     * @param Facture $facture
     */
    public function __construct(Facture $facture)
    {
        $this->facture = $facture;
    }

    /**
     * Déterminer les canaux de notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail']; // Utiliser le canal 'mail'
    }

    /**
     * Créer le message de notification par email.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Facture créée pour votre ticket de support')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Une nouvelle facture a été générée pour le ticket numéro ' . $this->facture->ticket->id)
            ->line('Description du ticket : ' . $this->facture->ticket->description) // Ajout de la description du ticket
            ->line('Montant : ' . $this->facture->montant . ' €')
            ->line('Date limite de paiement : ' . $this->facture->dateLimite->format('d/m/Y'))
            ->action('Voir la facture', url('/factures/' . $this->facture->id))
            ->line('Merci d\'avoir utilisé notre service de support!');
    }
}
