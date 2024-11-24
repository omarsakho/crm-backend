<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendezVous extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'agent_id',
        'client_id',
        'date_rendezvous',
        'lieu',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agent()
    {
        return $this->belongsTo(Utilisateur::class, 'agent_id'); // Corrigé ici
    }

    public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id'); // Corrigé ici
    }
}
