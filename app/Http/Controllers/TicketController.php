<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Utilisateur;
use App\Models\Facture;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // Soumettre une demande de support (client)
    public function soumettreDemande(Request $request)
    {
        $validatedData = $request->validate([
            'description' => 'required|string',
            'priorite' => 'required|in:faible,moyenne,haute',
            'type_demande' => 'required|in:technique,commercial,autre',
        ]);

        // Créer un ticket pour le client connecté
        $ticket = Ticket::create([
            'client_id' => auth()->id(),
            'description' => $validatedData['description'],
            'priorite' => $validatedData['priorite'],
            'type_demande' => $validatedData['type_demande'],
            'statut' => 'en_attente', // Le ticket est en attente par défaut
        ]);

        return response()->json(['message' => 'Demande soumise avec succès', 'ticket' => $ticket], 201);
    }

    // Affecter un ticket à un agent (administrateur ou agent)
    public function affecterTicket($ticketId, Request $request)
    {
        // Récupérer le ticket
        $ticket = Ticket::findOrFail($ticketId);

        // Vérifier que l'utilisateur est administrateur
        if (auth()->user()->role != 'administrateur') {
            return response()->json(['message' => 'Accès refusé : Vous devez être administrateur pour affecter des tickets.'], 403);
        }

        // Validation de l'agent ID
        $validatedData = $request->validate([
            'agent_id' => 'required|exists:utilisateurs,id',
        ]);

        // Vérifier si le ticket a déjà été assigné à un agent
        if ($ticket->agent_id !== null) {
            return response()->json(['message' => 'Ce ticket est déjà assigné à un agent.'], 403);
        }

        // Assigner le ticket à l'agent et mettre à jour son statut
        $ticket->update([
            'agent_id' => $validatedData['agent_id'],
            'statut' => 'en_cours', // Le ticket passe en cours de traitement
        ]);

        return response()->json(['message' => 'Ticket affecté avec succès', 'ticket' => $ticket]);
    }

    // Mettre à jour le statut d'un ticket (résolu ou en cours)
    public function mettreAJourStatut($ticketId, Request $request)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Vérification des permissions
        if (auth()->user()->id != $ticket->agent_id && auth()->user()->role != 'administrateur') {
            return response()->json(['message' => 'Accès refusé : Seul l\'agent assigné ou un administrateur peut mettre à jour ce ticket.'], 403);
        }

        $validatedData = $request->validate([
            'statut' => 'required|in:en_cours,résolu,en_attente',
        ]);

        // Mettre à jour le statut et la date de résolution si nécessaire
        $ticket->update([
            'statut' => $validatedData['statut'],
            'dateResolution' => $validatedData['statut'] == 'résolu' ? now() : null,
        ]);

        // Envoyer la notification de mise à jour du statut au client
        $ticket->client->notify(new \App\Notifications\TicketStatusUpdated($ticket));

        return response()->json(['message' => 'Statut du ticket mis à jour', 'ticket' => $ticket]);
    }

    // Visualiser les tickets (filtrage par statut, priorité, etc.)
    public function visualiserTickets(Request $request)
    {
        $user = auth()->user(); // Récupérer l'utilisateur connecté

        $ticketsQuery = Ticket::with(['client', 'agent']);

        // Si l'utilisateur est un client, il ne voit que ses propres tickets
        if ($user->role === 'client') {
            $ticketsQuery->where('client_id', $user->id);
        }

        // Si l'utilisateur est un agent, il voit uniquement les tickets qui lui sont assignés
        if ($user->role === 'agent') {
            $ticketsQuery->where('agent_id', $user->id);
        }

        // Appliquer les filtres de statut et de priorité
        $tickets = $ticketsQuery
            ->when($request->statut, function ($query) use ($request) {
                return $query->where('statut', $request->statut);
            })
            ->when($request->priorite, function ($query) use ($request) {
                return $query->where('priorite', $request->priorite);
            })
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    // Visualiser l'historique d'un ticket spécifique
    public function voirHistorique($ticketId)
    {
        $ticket = Ticket::with('client', 'agent')->findOrFail($ticketId);

        return response()->json(['ticket' => $ticket]);
    }

    // Récupérer la liste des agents
    public function listerAgents()
    {
        // Récupérer les utilisateurs ayant le rôle 'agent'
        $agents = Utilisateur::where('role', 'agent')->get();

        return response()->json(['agents' => $agents]);
    }

    // Supprimer un ticket
    public function supprimerTicket($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Vérifiez que l'utilisateur est administrateur ou agent assigné
        if (auth()->user()->role != 'administrateur' && auth()->user()->id != $ticket->agent_id) {
            return response()->json(['message' => 'Accès refusé : Vous devez être administrateur ou l\'agent assigné pour supprimer ce ticket.'], 403);
        }

        // Supprimer le ticket
        $ticket->delete();

        return response()->json(['message' => 'Ticket supprimé avec succès.']);
    }

    // Créer une facture pour un ticket
    public function creerFacture($ticketId, Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (auth()->user()->role != 'administrateur') {
            return response()->json(['message' => 'Accès refusé : Vous devez être administrateur pour créer une facture.'], 403);
        }

        // Récupérer le ticket
        $ticket = Ticket::findOrFail($ticketId);

        // Vérifier si le ticket a déjà une facture
        if ($ticket->facture) {
            return response()->json(['message' => 'Ce ticket a déjà une facture.'], 400);
        }

        // Validation des données de la facture
        $validatedData = $request->validate([
            'montant' => 'required|numeric|min:0',
        ]);

        // Récupérer le client associé au ticket
        $client = $ticket->client;

        // Créer la facture
        $facture = Facture::create([
            'ticket_id' => $ticket->id,
            'client_id' => $client->id,
            'montant' => $validatedData['montant'],
            'etat_paiement' => 'en attente',
            'dateEmission' => now(),
            'dateLimite' => now()->addDays(15), // Date limite = 15 jours après l'émission
        ]);

        // Envoyer un email de notification au client
        $client->notify(new \App\Notifications\FactureCreeeNotification($facture));

        return response()->json(['message' => 'Facture créée avec succès et email envoyé', 'facture' => $facture], 201);
    }


    // Lister les factures du client ou de l'agent
    public function listerFactures(Request $request)
    {
        $user = auth()->user();

        $facturesQuery = Facture::with('ticket', 'client'); // Charger aussi le client

        // Si l'utilisateur est un client, il voit uniquement ses factures
        if ($user->role === 'client') {
            $facturesQuery->whereHas('ticket', function ($query) use ($user) {
                return $query->where('client_id', $user->id);
            });
        }

        // Si l'utilisateur est un agent, il voit uniquement les factures liées aux tickets qu'il gère
        if ($user->role === 'agent') {
            $facturesQuery->whereHas('ticket', function ($query) use ($user) {
                return $query->where('agent_id', $user->id);
            });
        }

        // Appliquer les filtres d'état de paiement
        if ($request->etat_paiement) {
            $facturesQuery->where('etat_paiement', $request->etat_paiement);
        }

        $factures = $facturesQuery->get();

        return response()->json(['factures' => $factures]);
    }

    // Marquer une facture comme payée
    public function marquerFactureCommePayee($factureId, Request $request)
    {
        // Vérifier que l'utilisateur est soit un administrateur, soit le client associé à la facture
        $user = auth()->user();
        $facture = Facture::findOrFail($factureId);

        if ($user->role !== 'administrateur' && $user->id !== $facture->client_id) {
            return response()->json(['message' => 'Accès refusé : Vous devez être administrateur ou le client associé à cette facture pour la marquer comme payée.'], 403);
        }

        // Vérifier que la facture n'est pas déjà payée
        if ($facture->etat_paiement === 'payée') {
            return response()->json(['message' => 'La facture est déjà payée.'], 400);
        }

        // Mettre à jour l'état de paiement de la facture et la date de paiement
        $facture->update([
            'etat_paiement' => 'payée',
            'datePaiement' => now(),
        ]);

        // Envoyer une notification au client pour l'informer que la facture a été payée
        $facture->client->notify(new \App\Notifications\FacturePayeeNotification($facture));

        return response()->json(['message' => 'Facture marquée comme payée avec succès.', 'facture' => $facture]);
    }

    // Statistiques pour le tableau de bord
    public function statistiques()
    {
        // Comptage des tickets selon leur statut
        $totalTickets = Ticket::count();
        $ticketsEnAttente = Ticket::where('statut', 'en_attente')->count();
        $ticketsEnCours = Ticket::where('statut', 'en_cours')->count();
        $ticketsResolu = Ticket::where('statut', 'résolu')->count();

        // Comptage des factures selon leur état de paiement
        $totalFactures = Facture::count();
        $facturesEnAttente = Facture::where('etat_paiement', 'en attente')->count();
        $facturesPayees = Facture::where('etat_paiement', 'payée')->count();
        $facturesEnRetard = Facture::where('etat_paiement', 'en retard')->count();

        
        // Montant total des factures payées
        $montantTotalFactures = Facture::where('etat_paiement', 'payée')->sum('montant');

        // Montant total des factures payées pour aujourd'hui
        $montantTotalJournalier = Facture::whereDate('dateEmission', now())
                                                ->where('etat_paiement', 'payée')
                                                ->sum('montant');

        // Montant total des factures payées pour le mois en cours
        $montantTotalMensuel = Facture::whereYear('dateEmission', now()->year)
                                            ->whereMonth('dateEmission', now()->month)
                                            ->where('etat_paiement', 'payée')
                                            ->sum('montant');

        

        // Regrouper les résultats dans un tableau
        $data = [
            'tickets' => [
                'total' => $totalTickets,
                'en_attente' => $ticketsEnAttente,
                'en_cours' => $ticketsEnCours,
                'resolu' => $ticketsResolu,
            ],
            'factures' => [
                'total' => $totalFactures,
                'en_attente' => $facturesEnAttente,
                'payees' => $facturesPayees,
                'en_retard' => $facturesEnRetard,
                'montant_total' => $montantTotalFactures,
                'montant_total_journalier' => $montantTotalJournalier,
                'montant_total_mensuel' => $montantTotalMensuel,
            ]
        ];

        return response()->json($data);
    }

    public function archiver($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->archived = true;
        $ticket->save();

        return response()->json(['message' => 'Ticket archivé avec succès']);
    }

}
