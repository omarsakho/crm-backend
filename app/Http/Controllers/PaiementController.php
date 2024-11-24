<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;
use App\Models\Facture;

class PaiementController extends Controller
{
    public function creerPaymentIntent(Request $request)
    {
        // Récupérer la facture et valider qu'elle existe
        $facture = Facture::findOrFail($request->facture_id);

        // Configuration de la clé API Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        // Créer un PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $facture->montant * 100, // Stripe attend le montant en centimes
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }
}
