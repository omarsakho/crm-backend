<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    // Lister tous les utilisateurs
    public function index()
    {
        $utilisateurs = Utilisateur::all();
        return response()->json($utilisateurs);
    }

    // Afficher les détails d'un utilisateur
    public function show($id)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($utilisateur);
    }

    // Créer un nouvel utilisateur (Ajout)
    public function store(Request $request)
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

    // Mettre à jour un utilisateur
    public function update(Request $request, $id)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $validatedData = $request->validate([
            'nom' => 'string|max:255',
            'email' => 'string|email|max:255|unique:utilisateurs,email,' . $id,
            'motDePasse' => 'string|min:8|nullable',
            'role' => 'in:client,agent,administrateur|nullable',
        ]);

        $utilisateur->update($validatedData);

        return response()->json(['message' => 'Utilisateur mis à jour avec succès', 'utilisateur' => $utilisateur]);
    }

    // Supprimer un utilisateur
    public function destroy($id)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $utilisateur->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    // Afficher le profil de l'utilisateur authentifié
    public function profil(Request $request)
    {
        $utilisateur = $request->user(); // Récupérer l'utilisateur authentifié
        return response()->json($utilisateur);
    }

    // Mettre à jour le profil de l'utilisateur authentifié
    public function updateProfil(Request $request)
    {
        $utilisateur = $request->user(); // Récupérer l'utilisateur authentifié

        $validatedData = $request->validate([
            'nom' => 'string|max:255',
            'email' => 'string|email|max:255|unique:utilisateurs,email,' . $utilisateur->id,
            'motDePasse' => 'string|min:8|nullable',
        ]);

        $utilisateur->update($validatedData);

        return response()->json(['message' => 'Profil mis à jour avec succès', 'utilisateur' => $utilisateur]);
    }

    // Bloquer un utilisateur
    public function block($id)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $utilisateur->is_active = false;
        $utilisateur->save();

        return response()->json(['message' => 'Utilisateur bloqué avec succès', 'utilisateur' => $utilisateur]);
    }

    // Débloquer un utilisateur
    public function unblock($id)
    {
        $utilisateur = Utilisateur::find($id);

        if (!$utilisateur) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $utilisateur->is_active = true;
        $utilisateur->save();

        return response()->json(['message' => 'Utilisateur débloqué avec succès', 'utilisateur' => $utilisateur]);
    }

}
