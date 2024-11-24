<?php

namespace App\Notifications;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FacturePayeeNotification extends Notification
{
    use Queueable;

    protected $facture;

    public function __construct(Facture $facture)
    {
        $this->facture = $facture;
    }

    public function via($notifiable)
    {
        return ['mail']; // Vous pouvez ajouter d'autres canaux si nécessaire
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reçu de paiement pour votre facture')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Nous avons bien reçu votre paiement pour la facture #' . $this->facture->id . '.')
            ->line('Montant payé : ' . number_format($this->facture->montant, 2) . ' €')
            ->line('Date de paiement : ' . $this->facture->datePaiement->format('d/m/Y'))
            ->action('Voir vos factures', url('/factures'))
            ->line('Merci pour votre confiance !');
    }
}
