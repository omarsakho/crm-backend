<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Méthode d'inscription
    public function inscrire(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:utilisateurs',
            'motDePasse' => 'required|string|min:8',
        ]);

        // Créer l'utilisateur avec rôle par défaut 'client'
        $utilisateur = Utilisateur::create([
            'nom' => $validatedData['nom'],
            'email' => $validatedData['email'],
            'motDePasse' => $validatedData['motDePasse'],
            'role' => $request->role ?? 'client', // rôle par défaut client
        ]);

        return response()->json(['message' => 'Inscription réussie', 'utilisateur' => $utilisateur], 201);
    }

    public function seConnecter(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email',
            'motDePasse' => 'required|string',
        ]);

        $utilisateur = Utilisateur::where('email', $validatedData['email'])->first();

        if (!$utilisateur || !Hash::check($validatedData['motDePasse'], $utilisateur->motDePasse)) {
            return response()->json(['message' => 'Échec de la connexion'], 401);
        }

        // Créer un jeton de session (par exemple avec Laravel Sanctum)
        $token = $utilisateur->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token, 
            'utilisateur' => [
                'id' => $utilisateur->id,
                'nom' => $utilisateur->nom,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role, // Ajouter le rôle ici
            ]
        ]);
    }


    // Méthode de déconnexion
    public function seDeconnecter(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    // Méthode pour envoyer un lien de réinitialisation de mot de passe
    public function envoyerLienReinitialisation(Request $request)
    {
        // Vous pouvez utiliser le système de réinitialisation de Laravel
        return response()->json(['message' => 'Lien de réinitialisation envoyé']);
    }
}
