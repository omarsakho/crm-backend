<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'agent_id',
        'description',
        'priorite',
        'type_demande',
        'statut',
        'dateCreation',
        'dateResolution',
    ];

    // Relation avec le client
    public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id');
    }

    // Relation avec l'agent
    public function agent()
    {
        return $this->belongsTo(Utilisateur::class, 'agent_id');
    }

    // Relation avec les rendez-vous
    public function rendezvous()
    {
        return $this->hasMany(Rendezvous::class);
    }

    // Relation avec la facture (un ticket a une seule facture)
    public function facture()
    {
        return $this->hasOne(Facture::class);
    }
}
