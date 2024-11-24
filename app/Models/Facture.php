<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'client_id', // Ajouté ici
        'montant',
        'etat_paiement',
        'dateEmission',
        'dateLimite',
        'datePaiement',
    ];

    // Relation avec le ticket (une facture appartient à un ticket)
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Relation avec l'utilisateur (une facture appartient à un client)
    public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id'); // La facture appartient à un client
    }
}
