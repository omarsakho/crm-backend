<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FactureController extends Controller
{
    // Générer une facture pour un ticket spécifique (administrateur)
    public function genererFacture($ticketId, Request $request)
    {
        // Vérification que l'utilisateur est administrateur
        if (auth()->user()->role != 'administrateur') {
            return response()->json(['message' => 'Accès refusé : Vous devez être administrateur pour générer une facture.'], 403);
        }

        // Validation du montant
        $validatedData = $request->validate([
            'montant' => 'required|numeric|min:0',
        ]);

        // Récupération du ticket
        $ticket = Ticket::findOrFail($ticketId);

        // Vérification si une facture existe déjà pour ce ticket
        if (Facture::where('ticket_id', $ticketId)->exists()) {
            return response()->json(['message' => 'Une facture a déjà été générée pour ce ticket.'], 403);
        }

        // Génération de la facture
        $facture = Facture::create([
            'ticket_id' => $ticket->id,
            'client_id' => $ticket->client_id,
            'montant' => $validatedData['montant'],
            'statut' => 'en_attente', // Par défaut, la facture est en attente
            'date_creation' => now(),
            'date_limite_paiement' => Carbon::now()->addDays(30), // La date limite de paiement est 30 jours après
        ]);

        return response()->json(['message' => 'Facture générée avec succès', 'facture' => $facture], 201);
    }

    // Suivi des paiements (administrateur et client)
    public function suiviPaiements(Request $request)
    {
        $user = auth()->user();

        // Récupérer les factures pour le client connecté ou toutes les factures pour un administrateur
        $facturesQuery = Facture::with(['ticket', 'client']);

        if ($user->role === 'client') {
            $facturesQuery->where('client_id', $user->id);
        }

        // Appliquer des filtres sur le statut du paiement si fourni
        $factures = $facturesQuery
            ->when($request->statut, function ($query) use ($request) {
                return $query->where('statut', $request->statut);
            })
            ->get();

        return response()->json(['factures' => $factures]);
    }

    // Mettre à jour le statut du paiement d'une facture
    public function mettreAJourStatutPaiement($factureId, Request $request)
    {
        $facture = Facture::findOrFail($factureId);

        // Vérification des permissions : seul un administrateur peut mettre à jour le statut
        if (auth()->user()->role != 'administrateur') {
            return response()->json(['message' => 'Accès refusé : Seul un administrateur peut mettre à jour le statut du paiement.'], 403);
        }

        $validatedData = $request->validate([
            'statut' => 'required|in:payée,en_attente,en_retard',
        ]);

        // Mise à jour du statut et de la date de paiement si nécessaire
        $facture->update([
            'statut' => $validatedData['statut'],
            'date_paiement' => $validatedData['statut'] == 'payée' ? now() : null,
        ]);

        return response()->json(['message' => 'Statut du paiement mis à jour', 'facture' => $facture]);
    }
}
