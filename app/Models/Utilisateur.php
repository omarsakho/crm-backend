<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'nom', 
        'email', 
        'motDePasse', 
        'role',
        'isBlocked'
    ];

    protected $hidden = [
        'motDePasse',
    ];

    // Pour utiliser bcrypt lors de la création/mise à jour du mot de passe
    public function setMotDePasseAttribute($value)
    {
        $this->attributes['motDePasse'] = bcrypt($value);
    }

    // Vérifier si l'utilisateur est un client
    public function isClient()
    {
        return $this->role === 'client';
    }

    // Vérifier si l'utilisateur est un agent
    public function isAgent()
    {
        return $this->role === 'agent';
    }

    // Vérifier si l'utilisateur est un administrateur
    public function isAdmin()
    {
        return $this->role === 'administrateur';
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }
}
