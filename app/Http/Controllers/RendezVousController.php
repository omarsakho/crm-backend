<?php

namespace App\Http\Controllers;

use App\Models\RendezVous;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Notifications\RendezVousNotification;

class RendezVousController extends Controller
{
    // Planifier un rendez-vous
    public function planifierRendezVous($ticketId, Request $request)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Vérifier que l'agent assigné est celui qui planifie le rendez-vous
        if (auth()->user()->id !== $ticket->agent_id) {
            return response()->json(['message' => 'Accès refusé : Seul l\'agent assigné peut planifier un rendez-vous.'], 403);
        }

        $validatedData = $request->validate([
            'date_rendezvous' => 'required|date|after:now',
            'lieu' => 'required|string|max:255',
        ]);

        // Vérifier s'il y a un conflit avec un autre rendez-vous pour l'agent ou le client
        $existingRendezvous = RendezVous::where('date_rendezvous', $validatedData['date_rendezvous'])
            ->where(function ($query) use ($ticket) {
                $query->where('agent_id', $ticket->agent_id)
                      ->orWhere('client_id', $ticket->client_id);
            })
            ->first();

        if ($existingRendezvous) {
            return response()->json(['message' => 'Conflit détecté : Un autre rendez-vous est déjà planifié à cette date et heure.'], 409);
        }

        // Créer le rendez-vous
        $rendezvous = RendezVous::create([
            'ticket_id' => $ticket->id,
            'agent_id' => auth()->user()->id,
            'client_id' => $ticket->client_id,
            'date_rendezvous' => $validatedData['date_rendezvous'],
            'lieu' => $validatedData['lieu'],
        ]);

        // Notifications pour l'agent et le client
        $ticket->client->notify(new RendezVousNotification($rendezvous, 'planifié'));
        $ticket->agent->notify(new RendezVousNotification($rendezvous, 'planifié'));

        return response()->json(['message' => 'Rendez-vous planifié avec succès', 'rendezvous' => $rendezvous], 201);
    }

    // Modifier un rendez-vous
    public function modifierRendezVous($rendezVousId, Request $request)
    {
        $rendezvous = RendezVous::findOrFail($rendezVousId);

        // Seul l'agent assigné ou le client peuvent modifier le rendez-vous
        if (auth()->user()->id !== $rendezvous->agent_id && auth()->user()->id !== $rendezvous->client_id) {
            return response()->json(['message' => 'Accès refusé : Vous ne pouvez pas modifier ce rendez-vous.'], 403);
        }

        $validatedData = $request->validate([
            'date_rendezvous' => 'required|date|after:now',
            'lieu' => 'required|string|max:255',
        ]);

        // Vérification de conflit de rendez-vous pour l'agent ou le client
        $existingRendezvous = RendezVous::where('date_rendezvous', $validatedData['date_rendezvous'])
            ->where(function ($query) {
                $query->where('agent_id', auth()->user()->id)
                      ->orWhere('client_id', auth()->user()->id);
            })
            ->where('id', '!=', $rendezVousId) // Exclure le rendez-vous actuel
            ->first();

        if ($existingRendezvous) {
            return response()->json(['message' => 'Conflit : Un autre rendez-vous est déjà planifié à cette date et heure.'], 409);
        }

        // Mise à jour des informations du rendez-vous
        $rendezvous->update($validatedData);

        // Notifications pour l'agent et le client
        $rendezvous->client->notify(new RendezVousNotification($rendezvous, 'modifié'));
        $rendezvous->agent->notify(new RendezVousNotification($rendezvous, 'modifié'));

        return response()->json(['message' => 'Rendez-vous modifié avec succès', 'rendezvous' => $rendezvous]);
    }

    // Annuler un rendez-vous
    public function annulerRendezVous($rendezVousId)
    {
        $rendezvous = RendezVous::findOrFail($rendezVousId);

        // Seul l'agent assigné ou le client peuvent annuler le rendez-vous
        if (auth()->user()->id !== $rendezvous->agent_id && auth()->user()->id !== $rendezvous->client_id) {
            return response()->json(['message' => 'Accès refusé : Vous ne pouvez pas annuler ce rendez-vous.'], 403);
        }

        // Mettre à jour le statut du rendez-vous comme "annulé"
        $rendezvous->statut = 'annulé'; // Assurez-vous que le champ 'statut' existe dans votre table de rendez-vous
        $rendezvous->save();

        // Notifications pour l'agent et le client
        $rendezvous->client->notify(new RendezVousNotification($rendezvous, 'annulé'));
        $rendezvous->agent->notify(new RendezVousNotification($rendezvous, 'annulé'));

        return response()->json(['message' => 'Rendez-vous annulé avec succès', 'rendezvous' => $rendezvous]);
    }


    // Lister les rendez-vous
    public function listeRendezVous()
    {
        $rendezvousQuery = RendezVous::with(['client:id,nom,email', 'agent:id,nom,email']);
        
        if (auth()->user()->role == 'agent') {
            $rendezvous = $rendezvousQuery->where('agent_id', auth()->user()->id)->get();
        } elseif (auth()->user()->role == 'client') {
            $rendezvous = $rendezvousQuery->where('client_id', auth()->user()->id)->get();
        } else {
            $rendezvous = $rendezvousQuery->get(); // Pour l'administrateur
        }

        return response()->json(['rendezvous' => $rendezvous], 200);
    }

    // Obtenir les détails d'un rendez-vous
    public function show($rendezVousId)
    {
        $rendezvous = RendezVous::with(['client:id,nom,email', 'agent:id,nom,email'])->findOrFail($rendezVousId);

        return response()->json($rendezvous, 200);
    }
}
