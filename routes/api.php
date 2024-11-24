<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\RendezVousController;
use App\Http\Controllers\PaiementController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/inscription', [AuthController::class, 'inscrire']);
Route::post('/connexion', [AuthController::class, 'seConnecter']);
Route::post('/deconnexion', [AuthController::class, 'seDeconnecter'])->middleware('auth:sanctum');
Route::post('/mot-de-passe-oublie', [AuthController::class, 'envoyerLienReinitialisation']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/utilisateurs', [UtilisateurController::class, 'index']); // Lister tous les utilisateurs
    Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'show']); // Afficher les détails d'un utilisateur
    Route::put('/utilisateurs/{id}', [UtilisateurController::class, 'update']); // Mettre à jour un utilisateur
    Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'destroy']); // Supprimer un utilisateur
    Route::post('/utilisateurs', [UtilisateurController::class, 'store']);
    Route::get('/profil', [UtilisateurController::class, 'profil']);
    Route::put('/profil', [UtilisateurController::class, 'updateProfil']);
    Route::get('/agents', [UtilisateurController::class, 'obtenirAgents']);

    Route::put('utilisateurs/{id}/block', [UtilisateurController::class, 'block']);
    Route::put('utilisateurs/{id}/unblock', [UtilisateurController::class, 'unblock']);

    // Tickets
    Route::delete('/tickets/{ticketId}', [TicketController::class, 'supprimerTicket']);
    Route::post('/tickets', [TicketController::class, 'soumettreDemande']);
    Route::put('/tickets/{ticketId}/affecter', [TicketController::class, 'affecterTicket']);
    Route::put('/tickets/{ticketId}/statut', [TicketController::class, 'mettreAJourStatut']);
    Route::get('/tickets', [TicketController::class, 'visualiserTickets']);
    Route::get('/tickets/{ticketId}', [TicketController::class, 'voirHistorique']);
    Route::get('/agents', [TicketController::class, 'listerAgents']);
    Route::put('tickets/{id}/archiver', [TicketController::class, 'archiver']);

    // Rendez-vous
    Route::post('/tickets/{ticketId}/rendezvous', [RendezVousController::class, 'planifierRendezVous']);
    Route::put('/rendezvous/{rendezVousId}', [RendezVousController::class, 'modifierRendezVous']);
    Route::delete('/rendezvous/{rendezVousId}', [RendezVousController::class, 'annulerRendezVous']);
    Route::get('/rendezvous', [RendezVousController::class, 'listeRendezVous']);
    Route::get('/rendezvous/{rendezVousId}', [RendezVousController::class, 'show']);


    // Créer une facture pour un ticket
    Route::post('/tickets/{ticketId}/facture', [TicketController::class, 'creerFacture']);
    Route::put('/factures/{factureId}/payer', [TicketController::class, 'marquerFactureCommePayee']);
    Route::get('/factures', [TicketController::class, 'listerFactures']);

    Route::post('/paiement-intent', [PaiementController::class, 'creerPaymentIntent']);

    Route::get('/statistiques', [TicketController::class, 'statistiques']);

    
});
